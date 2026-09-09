<?php

use Illuminate\Support\Str;
use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Theme;
use SkillDo\Cms\Support\Url;

const SKD_SEO_NAME = 'skd-seo';

const SKD_SEO_VERSION = '4.0.8';

const SKD_SEO_PATH = 'plugins/' . SKD_SEO_NAME . '/';

class SkdSeo
{
    private string $name = 'skd_seo';

    function __construct() {}

    public function active(): void
    {
        //add setting
        Option::update('skd_seo_robots', '');

        (include_once 'database/database.php')->up();
    }

    public function uninstall(): void
    {
        Option::delete('skd_seo_robots');

        Option::delete(\SkdSeo\Services\NoIndexService::OPTION);

        (include_once 'database/database.php')->down();
    }

    static function bodyTags(): void
    {
        $output = 'itemscope ';
        $output .= 'prefix="og: http://ogp.me/ns#"';
        echo $output;
    }

    /**
     * Xây dựng danh sách URL alternate cho từng ngôn ngữ.
     *
     * Quy tắc:
     *  - Trang chủ + ngôn ngữ mặc định  → domain.com/
     *  - Trang chủ + ngôn ngữ khác      → domain.com/en/
     *  - Trang con  + mọi ngôn ngữ      → domain.com/vi/slug, domain.com/en/slug
     *
     * @return array<string, string>  ['vi' => 'https://domain.com/', 'en' => 'https://domain.com/en/']
     */
    static function buildAlternateLinks(): array
    {
        /*
        | CMS 8.2.0: mỗi ngôn ngữ có thể mang slug riêng, nên với trang gắn với một
        | đối tượng (bài viết, trang, danh mục, sản phẩm...) phải tra theo đối tượng
        | đó. Đổi tiền tố trên URL hiện tại như bên dưới chỉ đúng khi mọi ngôn ngữ
        | dùng chung một slug — làm vậy sẽ khai báo hreflang trỏ tới URL không tồn
        | tại ở ngôn ngữ đã dịch, đúng thứ Google phạt.
        |
        | Trang không gắn đối tượng (trang chủ, tìm kiếm, tài khoản) rơi xuống nhánh
        | cũ, vốn vẫn đúng vì chúng dùng chung đường dẫn ở mọi ngôn ngữ.
        */
        $localized = Url::localized(Url::currentObject());

        if(!empty($localized)) return $localized;

        $languages = \SkillDo\Cms\Support\Language::listKey();

        $default   = \SkillDo\Cms\Support\Language::default();

        $segments  = request()->segments();

        // Bỏ prefix ngôn ngữ ở đầu nếu có (vi/san-pham → ['san-pham'])
        if(!empty($segments) && in_array($segments[0], $languages))
        {
            array_shift($segments);
        }

        $isHome = empty($segments);

        $slug   = implode('/', $segments); // 'san-pham' | 'category/sub' | ''

        $links  = [];

        foreach ($languages as $lang)
        {
            if($isHome)
            {
                // Trang chủ: ngôn ngữ mặc định không có prefix → domain.com/
                // Ngôn ngữ khác có prefix                      → domain.com/en/
                $links[$lang] = ($lang === $default)
                    ? Url::base()
                    : Url::base($lang.'/');
            }
            else
            {
                // Trang con: mọi ngôn ngữ đều có prefix → domain.com/vi/slug
                $links[$lang] = Url::base($lang.'/'.$slug);
            }
        }

        return $links;
    }

    /**
     * Số trang đang xem, hoặc 0 nếu đang ở trang đầu.
     *
     * Toàn bộ CMS phân trang bằng query string: `?page=N` là quy ước hiện hành,
     * `?paging=N` là dạng cũ mà helper `pagination()` vẫn còn nhận.
     *
     * Ép sang số nguyên là có chủ ý, không phải cẩu thả: khu vực tài khoản dùng
     * lại đúng tên `?page=` nhưng để mang SLUG mục con (`?page=don-hang`). Chuỗi
     * đó ép ra 0 nên không bị nhầm thành số trang.
     */
    static function pagedNumber(): int
    {
        foreach (['paging', 'page'] as $key)
        {
            $value = (int) request()->query($key);

            if($value > 1) return $value;
        }

        return 0;
    }

    /**
     * URL canonical của trang hiện tại.
     *
     * `request()->url()` cắt sạch query string, nên trang 2, trang 3 đều tự khai
     * mình là trang 1. Google gộp chúng lại và bỏ qua nội dung ở các trang sau —
     * với danh mục nhiều bài thì phần lớn bài không bao giờ được thu thập qua
     * đường phân trang.
     *
     * Trang phân trang phải canonical về CHÍNH NÓ. Các tham số khác (bộ lọc, sắp
     * xếp, UTM) vẫn bị cắt, đó mới là thứ cần gộp.
     */
    static function canonicalUrl(): string
    {
        $url = request()->url();

        /*
        | `request()->url()` của Illuminate cắt dấu gạch chéo cuối, nên ở trang chủ
        | nó trả `https://site.com` trong khi URL thật là `https://site.com/`.
        | Google tự chuẩn hoá hai dạng đó nên đây không phải lỗi nặng, nhưng khai
        | canonical khác URL thật thì mọi công cụ soi SEO đều báo, và sửa chỉ tốn
        | một nhánh if.
        */
        if(trim(request()->getPathInfo(), '/') === '')
        {
            $url = rtrim(Url::base(), '/').'/';
        }

        $paged = static::pagedNumber();

        if($paged > 1)
        {
            $key = ((int) request()->query('paging') > 1) ? 'paging' : 'page';

            $url .= '?'.$key.'='.$paged;
        }

        return apply_filters('seo_canonical_url', $url, $paged);
    }

    /**
     * Các tên miền đáng báo trước cho trình duyệt bằng `preconnect`.
     *
     * Danh sách do provider dựng từ chính ô script của quản trị (xem
     * `SkdSeoServiceProvider`), nên không khai cứng tên miền nào.
     *
     * CỐ Ý GIỚI HẠN 4. Mỗi thẻ preconnect mở sẵn một kết nối TCP + TLS; khai
     * chục cái thì băng thông và socket dành cho việc dựng trang bị chia nhỏ,
     * trang chậm đi chứ không nhanh lên. Bốn cái đầu là nơi đáng đầu tư nhất vì
     * script dán trước thường là thứ nạp sớm nhất.
     */
    static function preconnectDomains(): array
    {
        $domains = config('skd-seo::preconnect', []);

        if(!is_array($domains) || empty($domains)) return [];

        return array_slice(apply_filters('seo_preconnect_domains', $domains), 0, 4);
    }

    static function header(): void
    {
        $headService = new \SkdSeo\Services\HeadService();

        $title          = '';

        $description    = '';

        $keyword        = '';

        $image          = '';

        if(Theme::isMethod('index'))
        {
            $category = Cms::getData('category');

            if(!empty($category->name))
            {
                $title 		= (!empty($category->seo_title)) ? $category->seo_title : $category->name;
                $description= (!empty($category->seo_description)) ? $category->seo_description : Str::clear($category->excerpt);
                $keyword 	= (!empty($category->seo_keywords)) ? $category->seo_keywords : '';
                $image 		= (!empty($category->image)) ? $category->image : '';
            }
        }

        if(Theme::isMethod('detail'))
        {
            $object = Cms::getData('object');

            if(!empty($object->title))
            {
                $title 		= (!empty($object->seo_title)) ? $object->seo_title : $object->title;
                $description= (!empty($object->seo_description)) ? $object->seo_description : Str::clear($object->excerpt);
                $keyword 	= (!empty($object->seo_keywords)) ? $object->seo_keywords : '';
                $image 		= (!empty($object->image)) ? $object->image : '';
            }
        }

        $title = apply_filters('seo_title', $title);

        $description = apply_filters('seo_description', $description);

        $keyword = apply_filters('seo_keyword', $keyword);

        $image = apply_filters('seo_image', $image);

        if(empty($image))
        {
            $image = Option::get('site_social_image');

            if(!empty($image))
            {
                $image = Url::base().Image::large($image)->link();
            }
        }

        $headService
            ->setTitle($title)
            ->setDescription($description)
            ->setKeyword($keyword)
            ->setImage($image);

        $headService = apply_filters('seo_head_base', $headService, Theme::getPage());

        /*
        | Trang 2 trở đi phải có tiêu đề khác trang 1. Để nguyên thì mọi trang
        | phân trang của cùng một danh mục dùng chung một title, Google gộp lại
        | và coi là trùng lặp.
        |
        | Chạy SAU `seo_head_base` để cộng vào đúng tiêu đề cuối cùng, kể cả khi
        | một plugin vừa ghi đè nó ở filter đó.
        */
        $paged = static::pagedNumber();

        if($paged > 1)
        {
            $headService->setTitle($headService->title.' - Trang '.$paged);
        }

        //OpenGraph
        $isArticle = Theme::isPage('post_detail');

        $headService
            ->addProperty('og:title', $headService->title)
            ->addProperty('og:description', $headService->description)
            ->addProperty('og:image', $headService->image)
            ->addProperty('og:type', ($isArticle) ? 'article' : 'website')
            ->addProperty('og:url', Url::current());

        /*
        | Thời điểm xuất bản / cập nhật của bài viết. Không có hai thẻ này thì
        | mạng xã hội và công cụ tổng hợp nội dung không biết bài mới hay cũ.
        */
        if($isArticle)
        {
            $object = Cms::getData('object');

            if(hasItems($object))
            {
                if(!empty($object->created))
                {
                    $headService->addProperty('article:published_time', date(DATE_ATOM, strtotime($object->created)));
                }

                $modified = (!empty($object->updated)) ? $object->updated : ($object->created ?? '');

                if(!empty($modified))
                {
                    $headService->addProperty('article:modified_time', date(DATE_ATOM, strtotime($modified)));
                }
            }
        }

        if(!empty(Option::get('facebook_app_id')))
        {
            $headService->addProperty('fb:app_id', Option::get('facebook_app_id'));
        }

        if(!empty(Option::get('facebook_admins')))
        {
            $headService->addProperty('fb:admins', Option::get('facebook_admins'));
        }

        //twitter
        $headService
            ->addMeta('twitter:card', 'summary')
            ->addMeta('twitter:title', $headService->title)
            ->addMeta('twitter:description', $headService->description)
            ->addMeta('twitter:image', $headService->image);

        //Meta
        $headService
            ->addMeta('Area', 'Vietnam')
            ->addMeta('geo.region', 'VN')
            ->addMeta('author', $headService->auth);

        //itemprop
        if(!empty(Option::get('seo_google_masterkey')))
        {
            $headService->addMeta('google-site-verification', Option::get('seo_google_masterkey'));
        }

        if(\SkillDo\Cms\Support\Language::isMulti())
        {
            $alternateLinks = static::buildAlternateLinks();

            $defaultLang    = \SkillDo\Cms\Support\Language::default();

            foreach ($alternateLinks as $lang => $url)
            {
                $headService->addCode('alternate-'.$lang, '<link rel="alternate" href="'.$url.'" hreflang="'.$lang.'" />');
            }

            // x-default trỏ về ngôn ngữ mặc định
            if(isset($alternateLinks[$defaultLang]))
            {
                $headService->addCode('alternate', '<link rel="alternate" href="'.$alternateLinks[$defaultLang].'" hreflang="x-default" />');
            }
        }

        //Báo trước tên miền bên thứ ba để trình duyệt bắt tay sớm
        foreach (static::preconnectDomains() as $index => $domain)
        {
            $headService->addCode('preconnect-'.$index, '<link rel="preconnect" href="'.$domain.'" />');
        }

        //Add canonical
        $headService
            ->addCode('canonical', '<link rel="canonical" href="'.static::canonicalUrl().'" />');

        $headService = apply_filters('seo_render', $headService, Theme::getPage());

        $headService->render();
    }
}

if(Admin::is())
{
    Admin::config()->booted('skd-seo', function () {
        /*
        |--------------------------------------------------------------------------
        | Đăng ký marketing
        |--------------------------------------------------------------------------
        | navigation: tạo menu marketing trên navigation admin
        | systemGroup: đăng ký nhóm cấu hình marketing vào hệ thống
        */
        add_action('admin_navigation', 'SkdSeo\Services\Admin\SeoMarketing::navigation');
        add_filter('admin_system_groups', 'SkdSeo\Services\Admin\SeoMarketing::systemGroup');
        add_action('admin_footer', 'SkdSeo\Services\Admin\SeoMarketing::script');
    });
}
else
{
    Theme::config()->booted('skd-seo', function ()
    {
        new \SkdSeo\Supports\SKDSeoSchemaBreadcrumb();

        add_action('cle_header', 'SkdSeo::header', 1);
        add_action('in_tag_html', 'SkdSeo::bodyTags', 1);

        //sitemap
        //Trang nội dung
        add_filter('seo_sitemap_list', 'SkdSeo\Services\SitemapPage::register');
        add_filter('seo_sitemap_page_xml', 'SkdSeo\Services\SitemapPage::sitemap');
        //Trang danh mục tin tức
        add_filter('seo_sitemap_list', 'SkdSeo\Services\SitemapPostCategory::register');
        add_filter('seo_sitemap_post_category_xml', 'SkdSeo\Services\SitemapPostCategory::sitemap', 10, 3);
        //trang chi tiết tin tức
        add_filter('seo_sitemap_list', 'SkdSeo\Services\SitemapPost::register');
        add_filter('seo_sitemap_post_xml', 'SkdSeo\Services\SitemapPost::sitemap', 10, 3);
        //
        add_filter('seo_sitemap_list', 'SkdSeo\Services\SitemapProductCategory::register');
        add_filter('seo_sitemap_product_category_xml', 'SkdSeo\Services\SitemapProductCategory::sitemap');

        add_filter('seo_sitemap_list', 'SkdSeo\Services\SitemapProduct::register');
        add_filter('seo_sitemap_product_xml', 'SkdSeo\Services\SitemapProduct::sitemap', 10, 3);
    });
}