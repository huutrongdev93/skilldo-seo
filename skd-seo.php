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

        //OpenGraph
        $headService
            ->addProperty('og:title', $headService->title)
            ->addProperty('og:description', $headService->description)
            ->addProperty('og:image', $headService->image)
            ->addProperty('og:type', 'website')
            ->addProperty('og:url', Url::current());

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

        //Add canonical
        $headService
            ->addCode('canonical', '<link rel="canonical" href="'.request()->url().'" />');

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