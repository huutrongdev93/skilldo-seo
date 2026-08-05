<?php
namespace SkdSeo\Services;

use Illuminate\Support\Str;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;
use Travel\Models\Archive;
use Travel\Models\Category;
use Travel\Models\Tour;

/**
 * Cầu nối giữa skd-seo và plugin travel (Tour & Lữ hành).
 *
 * Lý do phải có lớp này: SkdSeo::header() lấy đối tượng đang hiển thị từ
 * Cms::getData('object') / Cms::getData('category') — quy ước của post/page.
 * Travel đặt dữ liệu ở 'tour' / 'category' / 'archive', nên nếu không ánh xạ
 * lại thì trang chi tiết tour sẽ ra <title> mặc định của website.
 *
 * Toàn bộ hook đăng ký ở bootstrap/travel.php và luôn tự kiểm tra sự tồn tại
 * của plugin travel trước khi chạy — skd-seo phải dùng được khi không cài tour.
 */
class SeoTravel
{
    static function support(): bool
    {
        return class_exists(Tour::class);
    }

    /**
     * Đối tượng đang hiển thị của các trang do plugin travel dựng.
     *
     * Trang lọc (travel_archive) nhận diện bằng data-bag chứ không bằng tên
     * trang: controller của nó không có alias nên Theme::getPage() trả về
     * chuỗi sinh từ tên class, không đáng để phụ thuộc.
     */
    static function object($page = null)
    {
        if(!self::support())
        {
            return null;
        }

        $archive = Cms::getData('archive');

        if(hasItems($archive))
        {
            return $archive;
        }

        if($page == 'tour_detail')
        {
            return Cms::getData('tour');
        }

        if($page == 'tour_index')
        {
            return Cms::getData('category');
        }

        return null;
    }

    /**
     * Filter: seo_head_base — title / description / keyword / ảnh share.
     */
    static function headBase($headService, $page)
    {
        if(!self::support())
        {
            return $headService;
        }

        $object = self::object($page);

        /*
        | Trang danh sách tour tổng (/tour) không có danh mục nào đứng sau,
        | nên lấy nội dung khai báo trong Cấu hình > Seo.
        */
        if(!hasItems($object))
        {
            if($page == 'tour_index')
            {
                $headService
                    ->setTitle(Option::get('tour_title'))
                    ->setDescription(Option::get('tour_description'))
                    ->setKeyword(Option::get('tour_keyword'));
            }

            return $headService;
        }

        $name = $object->name ?? '';

        $title = (!empty($object->seo_title)) ? $object->seo_title : $name;

        $description = (!empty($object->seo_description))
            ? $object->seo_description
            : Str::clear($object->excerpt ?? '');

        $headService
            ->setTitle($title)
            ->setDescription($description)
            ->setKeyword($object->seo_keywords ?? '');

        if(!empty($object->image))
        {
            $headService->setImage($object->image);
        }

        return $headService;
    }

    /**
     * Filter: seo_point_object — đối tượng để lấy robots / canonical / schema
     * thủ công đã lưu trong metabox Seo.
     */
    static function pointObject($object, $page)
    {
        if(hasItems($object))
        {
            return $object;
        }

        return self::object($page) ?: $object;
    }

    /**
     * Filter: seo_point_support_module — thêm tour vào danh sách module được
     * chấm điểm seo (Cấu hình > Seo > Chấm điểm seo).
     */
    static function pointSupport($modules)
    {
        if(self::support())
        {
            $modules['tour'] = 'Tour';

            $modules['travel_category'] = 'Danh mục tour';
        }

        return $modules;
    }

    /**
     * Filter: seo_point_admin_module_enable — lớp đọc/ghi metadata seo.
     */
    static function pointModule($modules)
    {
        if(self::support())
        {
            $modules['tour'] = [
                'class' => \SkdSeo\Modules\Point\Modules\Tour::class,
            ];

            $modules['travel_category'] = [
                'class' => \SkdSeo\Modules\Point\Modules\TourCategory::class,
            ];
        }

        return $modules;
    }

    /**
     * Filter: skd_seo_llms_content — bổ sung tour vào llms.txt.
     */
    static function llms($llms)
    {
        if(!self::support())
        {
            return $llms;
        }

        //LlmsContent::group() trả null với key lạ — phải khai báo nhóm trước
        if(empty($llms->group('tour')))
        {
            $llms->addGroup('tour', '## Tour');
        }

        if(empty($llms->group('tour_category')))
        {
            $llms->addGroup('tour_category', '## Danh mục tour');
        }

        if(empty($llms->group('tour_archive')))
        {
            $llms->addGroup('tour_archive', '## Trang lọc tour');
        }

        $llms->group('tour')->addItem(
            'Tất cả tour',
            Url::base(config('travel::config.slug', 'tour')),
            Str::clear(Option::get('tour_description') ?: '')
        );

        $tours = Tour::where('trash', 0)->where('status', 'public')
            ->orderBy('featured', 'desc')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        foreach ($tours as $tour)
        {
            $llms->group('tour')->addItem(
                $tour->name,
                Url::base(Url::permalink((string) $tour->slug)),
                trim(Str::clear($tour->excerpt ?? ''))
            );
        }

        $categories = Category::orderBy('lft')->get();

        foreach ($categories as $category)
        {
            if(empty($category->slug))
            {
                continue;
            }

            $llms->group('tour_category')->addItem(
                $category->name,
                Url::base(Url::permalink((string) $category->slug)),
                trim(Str::clear($category->excerpt ?? ''))
            );
        }

        if(class_exists(Archive::class))
        {
            $prefix = trim((string) config('travel::config.slug_archive', 'du-lich'), '/');

            $archives = Archive::where('public', 1)->orderBy('order')->get();

            foreach ($archives as $archive)
            {
                if(empty($archive->slug))
                {
                    continue;
                }

                $llms->group('tour_archive')->addItem(
                    $archive->name,
                    Url::base($prefix.'/'.$archive->slug),
                    trim(Str::clear($archive->excerpt ?? ''))
                );
            }
        }

        return $llms;
    }
}
