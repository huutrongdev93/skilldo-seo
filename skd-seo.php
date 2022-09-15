<?php
/**
Plugin name     : Tùy Chỉnh Seo
Plugin class    : skd_seo
Plugin uri      : https://sikido.vn
Description     : Ứng dụng Tùy chỉnh SEO sẽ giúp bạn tự SEO hiệu quả cho website của mình
Author          : SKDSoftware Dev Team
Version         : 3.3.1
 */
const SKD_SEO_NAME = 'skd-seo';

const SKD_SEO_VERSION = '3.3.1';

define('SKD_SEO_PATH', Path::plugin(SKD_SEO_NAME).'/');

class Skd_Seo {

    private $name = 'skd_seo';

    function __construct() {}

    public function active() {

        $model = model('routes');

        //add sitemap to router
        $count = Routes::count(Qr::set('slug','sitemap.xml')->where('plugin', 'skd_seo'));
        if($count == 0) {
            $model->add(array(
                'slug'        => 'sitemap.xml',
                'controller'  => 'frontend/home/page/',
                'plugin'      => 'skd_seo',
                'object_type' => 'skd_seo',
                'directional' => 'skd_seo_sitemap',
                'callback' 	  => 'skd_seo_sitemap',
            ));
        }

        //add robots to router
        $count = Routes::count(Qr::set('slug','robots.txt')->where('plugin', 'skd_seo'));
        if($count == 0) {
            $model->add(array(
                'slug'        => 'robots.txt',
                'controller'  => 'frontend/home/page/',
                'plugin'      => 'skd_seo',
                'object_type' => 'skd_seo',
                'directional' => 'skd_seo_robots',
                'callback' 	  => 'skd_seo_robots',
            ));
        }

        //add setting
        Option::update('skd_seo_robots', '');

        if(!model()::schema()->hasTable('redirect')) {
            model()::schema()->create('redirect', function ($table) {
                $table->increments('id');
                $table->string('path', 255)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('to', 255)->collation('utf8mb4_unicode_ci');
                $table->string('type', 100)->collation('utf8mb4_unicode_ci')->default('301');
                $table->integer('redirect')->default(0);
                $table->integer('order')->default(0);
                $table->integer('user_created')->default(0);
                $table->integer('user_updated')->default(0);
                $table->datetime('created');
                $table->datetime('updated')->nullable();
            });
        }
    }

    public function uninstall() {
        model('routes')->delete(Qr::set('plugin', 'skd_seo'));
        Option::delete('skd_seo_robots');
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

function skd_seo_robots($ci , $model): void {
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