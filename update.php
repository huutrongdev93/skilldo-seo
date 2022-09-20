<?php
if(!Admin::is()) return;
function Seo_update_core() {
    if(Admin::is() && Auth::check() ) {
        $version = Option::get('seo_version');
        $version = (empty($version)) ? '3.0.2' : $version;
        if (version_compare( SKD_SEO_VERSION, $version ) === 1 ) {
            $update = new Seo_Update_Version();
            $update->runUpdate($version);
        }
    }
}
add_action('admin_init', 'Seo_update_core');
Class Seo_Update_Version {
    public function runUpdate($seoVersion) {
        $listVersion    = ['3.1.0', '3.3.2'];
        $model          = get_model();
        foreach ($listVersion as $version ) {
            if(version_compare( $version, $seoVersion ) == 1) {
                $function = 'update_Version_'.str_replace('.','_',$version);
                if(method_exists($this, $function)) $this->$function($model);
            }
        }
        Option::update('seo_version', SKD_SEO_VERSION );
    }
    public function update_Version_3_1_0($model) {
        Seo_Update_Database::Version_3_1_0($model);
    }
    public function update_Version_3_3_2($model) {
        Seo_Update_Database::Version_3_3_2($model);
    }
}
Class Seo_Update_Database {
    public static function Version_3_1_0($model) {
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
    public static function Version_3_3_2($model) {
        Option::update('seo_point_support', [
            'post_post', 'post_categories_post_categories', 'page', 'products', 'products_categories'
        ]);
    }
}