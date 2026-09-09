<?php
namespace SkdSeo\Services;

use Illuminate\Support\Str;
use SkillDo\Cms\Models\Tag;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\TagService;
use SkillDo\Cms\Support\Url;

/**
 * Hỗ trợ seo cho chức năng Thẻ (tag) — có từ CMS 8.1.3.
 *
 * Vì sao phải có lớp này:
 *
 * 1. Trang lưu trữ theo thẻ dùng CHUNG template với trang danh mục bài viết
 *    (TagController tự đăng ký filter `template_get_page` trả về `post_index`),
 *    nên Theme::getPage() KHÔNG phân biệt được thẻ với chuyên mục. Cách nhận
 *    diện duy nhất đáng tin là data-bag `tag` — chỉ TagController đặt biến này.
 *
 * 2. Thẻ không ghi vào bảng `routes` mà dùng chung một route `/tag/{slug}`,
 *    nên URL phải dựng từ `cms.tag.prefix` chứ không lấy được từ Router.
 *
 * Toàn bộ hook đăng ký ở bootstrap/tag.php và luôn tự kiểm tra sự tồn tại của
 * model Tag trước khi chạy — skd-seo phải dùng được trên bản CMS cũ chưa có thẻ.
 */
class SeoTag
{
    /**
     * Khóa option chứa cấu hình seo của thẻ (Cấu hình > Seo > Trang thẻ)
     */
    const OPTION = 'seo_tag';

    static function support(): bool
    {
        return class_exists(Tag::class);
    }

    /**
     * Thẻ đang hiển thị, hoặc null nếu trang hiện tại không phải trang thẻ.
     */
    static function object($page = null)
    {
        if(!self::support())
        {
            return null;
        }

        $tag = Cms::getData('tag');

        return (hasItems($tag)) ? $tag : null;
    }

    /**
     * Một khóa trong option cấu hình seo của thẻ
     */
    static function config($key, $default = null)
    {
        $config = Option::get(self::OPTION);

        if(!is_array($config))
        {
            $config = [];
        }

        return (isset($config[$key]) && $config[$key] !== '') ? $config[$key] : $default;
    }

    /**
     * Số bài tối thiểu để thẻ được cho index. 0 = index tất cả.
     */
    static function minCount(): int
    {
        return (int)self::config('min_count', 1);
    }

    static function inSitemap(): bool
    {
        return !empty(self::config('sitemap', 1));
    }

    /**
     * Tiền tố url trang thẻ (`/tag/{slug}`)
     */
    static function prefix(): string
    {
        return trim((string)config('cms.tag.prefix', 'tag'), '/');
    }

    /**
     * Filter: seo_head_base — title / description / keyword / ảnh share.
     *
     * SkdSeo::header() đã lấy được nội dung cơ bản của thẻ qua data-bag
     * `category` (TagController cấp cả hai biến để dùng chung giao diện), ở đây
     * chỉ bổ sung phần thẻ hay bị bỏ trống: mô tả mặc định và tiêu đề trang 2+.
     */
    static function headBase($headService, $page)
    {
        $tag = self::object($page);

        if(!hasItems($tag))
        {
            return $headService;
        }

        $name = Str::clear((string)($tag->name ?? ''));

        /*
        | Kiểm tra trên BẢN THÂN thẻ chứ không phải trên $headService: khi thẻ
        | trống mô tả, HeadService đã kịp lấy mô tả chung của website làm giá trị
        | mặc định — để nguyên thì mọi trang thẻ đều dùng chung một description.
        |
        | Thẻ hầu hết được tạo tự động từ ô nhập thẻ của bài viết nên gần như
        | luôn trống, vì vậy trường hợp này là phổ biến chứ không phải ngoại lệ.
        */
        $description = (!empty($tag->seo_description)) ? $tag->seo_description : ($tag->excerpt ?? '');

        if(trim(Str::clear((string)$description)) === '')
        {
            $headService->setDescription(apply_filters(
                'seo_tag_description_default',
                'Tổng hợp bài viết, tin tức mới nhất về '.$name.'. Cập nhật liên tục tại '.Str::clear((string)Option::get('general_label', '')),
                $tag
            ));
        }

        if(empty($tag->seo_keywords) && !empty($name))
        {
            $headService->setKeyword($name);
        }

        /*
        | Hậu tố " - Trang N" KHÔNG còn ở đây. `SkdSeo::header()` đã cộng cho mọi
        | loại trang phân trang ngay sau filter này; để lại thì trang thẻ bị cộng
        | hai lần thành "... - Trang 2 - Trang 2".
        */

        return $headService;
    }

    /**
     * Filter: seo_render — robots của trang thẻ và og article:tag của bài viết.
     */
    static function render($headService, $page)
    {
        if(!self::support())
        {
            return $headService;
        }

        $tag = self::object($page);

        if(hasItems($tag))
        {
            return self::renderArchive($headService, $tag);
        }

        if($page == 'post_detail')
        {
            return self::renderPostTags($headService);
        }

        return $headService;
    }

    /**
     * Thẻ mỏng (chưa có bài, hoặc quá ít bài) là trang gần như rỗng — để index
     * chỉ làm loãng chất lượng index của site.
     *
     * Quy tắc: nếu thẻ ĐÃ có thiết lập robots thủ công trong metabox Seo thì tôn
     * trọng thiết lập đó, chỉ tự động noindex khi chưa ai đụng tới.
     */
    static protected function renderArchive($headService, $tag)
    {
        $min = self::minCount();

        if($min <= 0 || (int)($tag->count ?? 0) >= $min)
        {
            return $headService;
        }

        $robots = Tag::getMeta($tag->id, 'seo_robots', true);

        if(hasItems($robots))
        {
            return $headService;
        }

        $headService->addMeta('robots', apply_filters('seo_tag_robots_thin', 'noindex,follow', $tag));

        return $headService;
    }

    /**
     * Tên các thẻ của một bài viết.
     *
     * Nhớ lại kết quả trong request: cùng một trang chi tiết bài viết vừa cần
     * cho meta `article:tag` vừa cần cho `keywords` của schema BlogPosting.
     */
    static function postTagNames(int $postId): array
    {
        static $cache = [];

        if(!$postId || !class_exists(TagService::class))
        {
            return [];
        }

        if(!array_key_exists($postId, $cache))
        {
            $cache[$postId] = TagService::namesByObject($postId, 'post');
        }

        return $cache[$postId];
    }

    /**
     * Bài viết có thẻ thì khai báo `article:tag` để mạng xã hội và bộ máy tìm
     * kiếm biết chủ đề của bài; keyword để trống thì lấy luôn danh sách thẻ.
     */
    static protected function renderPostTags($headService)
    {
        $object = Cms::getData('object');

        if(noItems($object) || empty($object->id))
        {
            return $headService;
        }

        $names = self::postTagNames((int)$object->id);

        if(!hasItems($names))
        {
            return $headService;
        }

        foreach ($names as $name)
        {
            $headService->addProperty('article:tag', Str::clear((string)$name));
        }

        if(empty($headService->keyword))
        {
            $headService->setKeyword(implode(', ', array_map(function ($name) {
                return Str::clear((string)$name);
            }, $names)));
        }

        return $headService;
    }

    /**
     * Filter: seo_point_object — đối tượng để lấy robots / canonical / schema
     * thủ công đã lưu trong metabox Seo.
     *
     * Trang thẻ không đặt data-bag `object` (biến đó dành cho trang chi tiết)
     * nên nếu không ánh xạ lại thì metabox Seo của thẻ không bao giờ được đọc.
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
     * Filter: seo_point_support_module — thêm thẻ vào danh sách module được
     * chấm điểm seo (Cấu hình > Seo > Chấm điểm seo).
     */
    static function pointSupport($modules)
    {
        if(self::support())
        {
            $modules['tag'] = 'Thẻ';
        }

        return $modules;
    }

    /**
     * Filter: seo_point_admin_module_enable — lớp đọc/ghi metadata seo của thẻ.
     */
    static function pointModule($modules)
    {
        if(self::support())
        {
            $modules['tag'] = [
                'class' => \SkdSeo\Modules\Point\Modules\Tag::class,
            ];
        }

        return $modules;
    }

    /**
     * Filter: seo_point_criteria — tiêu chí chấm điểm của form thẻ.
     *
     * Form thẻ không có trình soạn thảo nội dung, giữ nguyên bộ tiêu chí đầy đủ
     * thì mọi thẻ đều bị trừ điểm ở các mục về nội dung / heading / ảnh và điểm
     * seo không bao giờ vượt quá ~35.
     */
    static function pointCriteria($criteria, $module)
    {
        if($module != 'tag')
        {
            return $criteria;
        }

        $keys = [
            'keywordNotUsed',
            'keywordInTitle',
            'titleStartWithKeyword',
            'lengthTitle',
            'keywordInMetaDescription',
            'lengthMetaDescription',
            'keywordInPermalink',
            'lengthPermalink',
        ];

        return array_intersect_key($criteria, array_flip($keys));
    }

    /**
     * Filter: skd_seo_llms_content — bổ sung thẻ vào llms.txt.
     */
    static function llms($llms)
    {
        if(!self::support())
        {
            return $llms;
        }

        if(empty($llms->group('tag')))
        {
            $llms->addGroup('tag', '## Thẻ bài viết');
        }

        //Thẻ chưa có bài nào là trang rỗng, không đưa vào danh sách cho LLM
        $tags = Tag::where('tag_type', 'post')
            ->where('count', '>', 0)
            ->orderBy('count', 'desc')
            ->limit(100)
            ->get();

        foreach ($tags as $tag)
        {
            if(empty($tag->slug))
            {
                continue;
            }

            $llms->group('tag')->addItem(
                $tag->name,
                Url::base(Url::tag((string)$tag->slug)),
                trim(Str::clear($tag->excerpt ?? ''))
            );
        }

        return $llms;
    }
}
