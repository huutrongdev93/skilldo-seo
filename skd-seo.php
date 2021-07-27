<?php
/**
Plugin name     : Tùy Chỉnh Seo
Plugin class    : skd_seo
Plugin uri      : https://sikido.vn
Description     : Ứng dụng Tùy chỉnh SEO sẽ giúp bạn tự SEO hiệu quả cho website của mình
Author          : SKDSoftware Dev Team
Version         : 3.1.0
 */
define('SKD_SEO_NAME', 'skd-seo');

define('SKD_SEO_PATH', Path::plugin(SKD_SEO_NAME).'/');

define('SKD_SEO_VERSION', '3.1.0');

class skd_seo {

    private $name = 'skd_seo';

    function __construct() {}

    public function active() {
        $model = get_model()->settable('routes');
        //add sitemap to router
        $count = $model->count_where(array('slug' => 'sitemap.xml', 'plugin' => 'skd_seo'));

        if($count == 0) {
            $model->add(array(
                'slug'        => 'sitemap.xml',
                'controller'  => 'frontend_home/home/page/',
                'plugin'      => 'skd_seo',
                'object_type' => 'skd_seo',
                'directional' => 'skd_seo_sitemap',
                'callback' 	  => 'skd_seo_sitemap',
            ));
        }
        //add robots to router
        $count = $model->count_where(array('slug' => 'robots.txt', 'plugin' => 'skd_seo'));

        if($count == 0) {
            $model->add(array(
                'slug'        => 'robots.txt',
                'controller'  => 'frontend_home/home/page/',
                'plugin'      => 'skd_seo',
                'object_type' => 'skd_seo',
                'directional' => 'skd_seo_robots',
                'callback' 	  => 'skd_seo_robots',
            ));
        }
        //add setting
        Option::update( 'skd_seo_robots', '');

        $model->query("CREATE TABLE IF NOT EXISTS `".CLE_PREFIX."redirect` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `path` varchar(255) COLLATE utf8mb4_unicode_ci NULL,
            `to` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `type`  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '301',
            `redirect` int(11) NOT NULL DEFAULT '0',
            `order` int(11) NOT NULL DEFAULT '0',
            `user_created` int(11) DEFAULT NULL,
            `user_updated` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL,
            `updated` datetime DEFAULT NULL
        ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    }

    public function uninstall() {
        get_model()->settable('routes')->delete_where(array('plugin' => 'skd_seo'));
        //add setting
        option::delete( 'skd_seo_robots' );
    }

    static public function bodyTags() {
        $output = 'itemscope ';
        $output .= 'prefix="og: http://ogp.me/ns#"';
        echo $output;
    }

    static public function header() {

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

include 'admin/point/skd-seo-point.php';

include 'admin/404/404.php';

if(Admin::is()) {
    require_once 'update.php';
    require_once 'admin/index.php';
}
else {
    require_once 'skd-seo-helper.php';
    require_once 'skd-seo-schema.php';
    require_once 'skd-seo-sitemap.php';
    require_once 'skd-seo-breadcrumb.php';
    add_action('cle_header', 'skd_seo::header', 1);
    add_action('in_tag_html', 'skd_seo::bodyTags', 1);
}

function skd_seo_robots($ci , $model) {
    header('Content-type: text/plain');
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
}