<?php
namespace SkdSeo\Supports;

use SkillDo\Cms\Support\Metabox;
use SkillDo\Cms\Support\Option;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeoPoint
{
    static function module($key = '')
    {
        $module = [
            'post' => [
                'class' => \SkdSeo\Modules\Point\Modules\Post::class,
            ],
            'post_categories' => [
                'class' => \SkdSeo\Modules\Point\Modules\Category::class,
            ],
            'page' => [
                'class' => \SkdSeo\Modules\Point\Modules\Page::class,
            ],
        ];

        /*
        | Sản phẩm nằm ở plugin sicommerce nhưng danh sách module hỗ trợ trong
        | Cấu hình > Seo lại khai cứng hai key này, nên đăng ký luôn ở đây thay
        | vì đi qua filter. Bắt buộc kiểm tra class_exists: hai lớp bên dưới gọi
        | thẳng model của sicommerce, site không cài bán hàng mà vẫn đăng ký thì
        | AdminPoint sẽ fatal khi khởi tạo chúng.
        */
        if(class_exists(\Ecommerce\Models\Product::class))
        {
            $module['products'] = [
                'class' => \SkdSeo\Modules\Point\Modules\Product::class,
            ];

            $module['products_categories'] = [
                'class' => \SkdSeo\Modules\Point\Modules\ProductCategory::class,
            ];
        }

        $module = apply_filters('seo_point_admin_module_enable', $module);

        return (!empty($key)) ? Arr::get($module, $key) : $module;
    }

    static function listCriteria($key = '') {

        $listCriteria = [
            'keywordNotUsed' => 'Đặt Từ khóa tập trung cho nội dung này.',
            'keywordInTitle' => 'Thêm từ khóa chính vào tiêu đề SEO.',
            'titleStartWithKeyword' => 'Sử dụng từ khóa chính gần đầu tiêu đề SEO.',
            'lengthTitle' => 'Tiêu đề của bài viết phải lớn hơn 40 ký tự và khuyến cáo nhỏ hơn 70 ký tự',
            'keywordInMetaDescription' => 'Thêm Từ khóa tập trung vào Mô tả meta SEO của bạn.',
            'lengthMetaDescription' => 'Mô tả meta SEO của bạn nên có từ 155 đến 160 ký tự.',
            'keywordInPermalink' => 'Sử dụng từ khóa chính trong URL.',
            'lengthPermalink' => 'URL không khả dụng. Thêm URL ngắn.',
            'keywordIn10Percent' => 'Sử dụng từ khóa chính ở đầu nội dung của bạn.',
            'keywordInContent' => 'Sử dụng từ khóa chính trong nội dung.',
            'lengthContent' => 'Nội dung phải dài 600-2500 từ.',
            'linksHasInternal' => 'Thêm liên kết nội bộ vào nội dung của bạn.',
            'keywordInSubheadings' => 'Sử dụng từ khóa chính trong (các) tiêu đề phụ như H2, H3, H4, v.v..',
            'keywordInImageAlt' => 'Thêm từ khóa vào thuộc tính alt của hình ảnh',
            'keywordDensity' => 'Mật độ từ khóa là 0. Nhắm đến khoảng 1% Mật độ từ khóa.',
            'contentHasShortParagraphs' => 'Thêm các đoạn văn ngắn và súc tích để dễ đọc và UX hơn.',
            'contentHasAssets' => 'Thêm một vài hình ảnh để làm cho nội dung của bạn hấp dẫn.',
        ];

        if(!empty($key)) return Arr::get($listCriteria, $key);

        return $listCriteria;
    }

    /**
     * Bộ tiêu chí chấm điểm áp dụng cho MỘT module.
     *
     * Không phải form nào cũng có đủ trường để chấm 17 tiêu chí — form thẻ chẳng
     * hạn, không có trình soạn thảo nội dung nên mọi tiêu chí về nội dung,
     * heading, ảnh đều không bao giờ đạt. Module tự rút gọn danh sách qua filter
     * `seo_point_criteria`, điểm số được chia lại theo số tiêu chí còn lại.
     */
    static function criteria(string $module = ''): array
    {
        $criteria = apply_filters('seo_point_criteria', static::listCriteria(), $module);

        return (is_array($criteria) && !empty($criteria)) ? $criteria : static::listCriteria();
    }

    static function registerMetabox(): void
    {
        $seoPointSupport = Option::get('seo_point_support');

        foreach ($seoPointSupport as $seoPoint)
        {
            $module = $seoPoint;

            if(Str::startsWith($seoPoint, 'post_categories_'))
            {
                $index = str_replace('post_categories_', '', $seoPoint);

                if($index == request()->input('cate_type'))
                {
                    $module = 'post_categories';
                }
            }
            else if(Str::startsWith($seoPoint, 'post_'))
            {
                $index = str_replace('post_', '', $seoPoint);

                if($index == request()->input('post_type'))
                {
                    $module = 'post';
                }
            }

            if(!empty(SeoPoint::module($module)))
            {
                Metabox::add('SKD_Seo_Point_'.$module, 'Seo', 'SkdSeo\Modules\Point\AdminPoint::metaBox', ['module' => $module]);
            }
        }
    }
}