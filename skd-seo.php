<?php
/**
Plugin name     : Tùy Chỉnh Seo
Plugin class    : skd_seo
Plugin uri      : https://sikido.vn
Description     : Ứng dụng Tùy chỉnh SEO sẽ giúp bạn tự SEO hiệu quả cho website của mình
Author          : SKDSoftware Dev Team
Version         : 4.0.0
 */
const SKD_SEO_NAME = 'skd-seo';

const SKD_SEO_VERSION = '4.0.0';

define('SKD_SEO_PATH', Path::plugin(SKD_SEO_NAME).'/');

class Skd_Seo {

    private string $name = 'skd_seo';

    function __construct() {}

    public function active(): void
    {
        //add setting
        Option::update('skd_seo_robots', '');

        $database = include_once 'database/database.php';

        $database->up();
    }

    public function uninstall(): void
    {
        Option::delete('skd_seo_robots');

        $database = include_once 'database/database.php';

        $database->down();
    }

    static function bodyTags(): void
    {
        $output = 'itemscope ';
        $output .= 'prefix="og: http://ogp.me/ns#"';
        echo $output;
    }

    static function header(): void
    {
        $seo_helper = new SeoHelper();

        $title          = '';

        $description    = '';

        $keyword        = '';

        $image          = '';

        if(Template::isMethod('index')) {
            $category = get_object_current('category');
            if(!empty($category->name)) {
                $title 		= (!empty($category->seo_title)) ? $category->seo_title : $category->name;
                $description= (!empty($category->seo_description)) ? $category->seo_description : Str::clear($category->excerpt);
                $keyword 	= (!empty($category->seo_keywords)) ? $category->seo_keywords : '';
                $image 		= (!empty($category->image)) ? $category->image : '';
            }
        }

        if(Template::isMethod('detail')) {
            $object = get_object_current('object');
            if(!empty($object->title)) {
                $title 		= (!empty($object->seo_title)) ? $object->seo_title : $object->title;
                $description= (!empty($object->seo_description)) ? $object->seo_description : Str::clear($object->excerpt);
                $keyword 	= (!empty($object->seo_keywords)) ? $object->seo_keywords : '';
                $image 		= (!empty($object->image)) ? $object->image : '';
            }
        }

        $seo_helper
            ->setTitle($title)
            ->setDescription($description)
            ->setKeyword($keyword)
            ->setImage($image);

        //OpenGraph
        $seo_helper
            ->addProperty('og:title', $seo_helper->title)
            ->addProperty('og:description', $seo_helper->description)
            ->addProperty('og:image', $seo_helper->image)
            ->addProperty('og:type', 'website')
            ->addProperty('og:url', Url::current());

        if(!empty(option::get('facebook_app_id'))) {
            $seo_helper->addProperty('fb:app_id', option::get('facebook_app_id'));
        }
        if(!empty(option::get('facebook_admins'))) {
            $seo_helper->addProperty('fb:admins', option::get('facebook_admins'));
        }

        //twitter
        $seo_helper
            ->addMeta('twitter:card', 'summary')
            ->addMeta('twitter:title', $seo_helper->title)
            ->addMeta('twitter:description', $seo_helper->description)
            ->addMeta('twitter:image', $seo_helper->image);

        //Meta
        $seo_helper
            ->addMeta('Area', 'Vietnam')
            ->addMeta('geo.region', 'VN')
            ->addMeta('author', $seo_helper->auth);

        //itemprop
        if(!empty(option::get('seo_google_masterkey'))) {
            $seo_helper->addMeta('google-site-verification', option::get('seo_google_masterkey'));
        }

        //Add canonical
        $seo_helper
            ->addCode('canonical', '<link rel="canonical" href="'.Url::current().'" />');

        $seo_helper = apply_filters('seo_render', $seo_helper, Template::getPage());

        $seo_helper->render();
    }
}

require_once 'admin/admin.php';

if(Admin::is()) {

    require_once 'update.php';
}
else {
    require_once 'skd-seo-helper.php';

    require_once 'skd-seo-schema.php';

    require_once 'skd-seo-sitemap.php';

    require_once 'skd-seo-breadcrumb.php';

    add_action('cle_header', 'skd_seo::header', 1);

    add_action('in_tag_html', 'skd_seo::bodyTags', 1);
}

function skd_seo_robots($request): void {

    $robots = trim(option::get('skd_seo_robots'));

    if(!empty($robots)) {
        echo $robots;
    }
    else {
        echo 'User-agent: *'."\n";
        echo 'Disallow:/admin'."\n";
        echo 'Disallow: /cgi-bin/'."\n";
        echo 'Sitemap: '.Url::base('sitemap.xml')."\n";
    }

    response()->header('Content-Type', 'text/plain')->send();
}