<?php
class SKDSeoSchemaBreadcrumb {
    public function __construct() {
        add_filter('breadcrumb_open', 		'SKDSeoSchemaBreadcrumb::open', 10 );
        add_filter('breadcrumb_first', 		'SKDSeoSchemaBreadcrumb::first', 10 );
        add_filter('breadcrumb_item', 		'SKDSeoSchemaBreadcrumb::item', 10, 3 );
        add_filter('breadcrumb_item_last', 	'SKDSeoSchemaBreadcrumb::item', 10, 3 );
        add_filter('breadcrumb_icon', 		'SKDSeoSchemaBreadcrumb::icon', 10, 1 );
        add_action('theme_custom_css', 		'SKDSeoSchemaBreadcrumb::css', 10 );
    }
    static function open ($bre): string {
        $bre = '<div itemscope itemtype="https://schema.org/BreadcrumbList" class="btn-group btn-breadcrumb">';
        return $bre;
    }
    static function first ($bre): string {
        $bre = '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a href="'.Url::base().'" class="btn btn-default" itemprop="item"><span itemprop="name">'.__('Trang chủ','trang-chu').'</span></a><meta itemprop="position" content="1" /></span>';
        return $bre;
    }
    static function item ($bre, $item, $key): string {

        $ci =& get_instance();

        if(have_posts($item)) {

            $slug = '';

            if(!empty($item->slug)) $slug = $item->slug;
            else if(!empty($ci->data['object']->slug)) $slug = $ci->data['object']->slug;
            else if(!empty($ci->data['category']->slug)) $slug = $ci->data['category']->slug;

            $bre = '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="'.Url::permalink($slug).'" class="btn btn-default" itemprop="item"><span itemprop="name">'.$item->name.'</span></a>
            <meta itemprop="position" content="'.($key+1).'" />
            </span>';
        }

        return $bre;
    }
    static function icon ($bre): string {
        $bre = '<span><a class="btn btn-default btn-next">/</a></span>';
        return $bre;
    }
    static function css (): void {
        ?>
        .breadcrumb span a.btn.btn-default { color: #333; background-color: #fff; border-color: #ccc;height:37px; position: relative; float: left;border: 0; border-radius: 0; }
        .btn-breadcrumb a.btn.btn-default { color:#fff;background-color: transparent;border-color: transparent;height:37px; position: relative; float: left;border: 0; border-radius: 0; } <?php
    }
}

new SKDSeoSchemaBreadcrumb();