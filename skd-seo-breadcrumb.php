<?php
if(!function_exists('skd_seo_schema_breadcrumb')) {
    function skd_seo_schema_breadcrumb () {
        add_filter('breadcrumb_open', 		'skd_seo_schema_breadcrumb_open', 10 );
        add_filter('breadcrumb_first', 		'skd_seo_schema_breadcrumb_first', 10 );
        add_filter('breadcrumb_item', 		'skd_seo_schema_breadcrumb_item', 10, 3 );
        add_filter('breadcrumb_item_last', 	'skd_seo_schema_breadcrumb_item', 10, 3 );
        add_filter('breadcrumb_icon', 		'skd_seo_schema_breadcrumb_icon', 10, 1 );
        add_action('theme_custom_css', 		'skd_seo_schema_breadcrumb_css', 10 );
    }
    add_action('init', 'skd_seo_schema_breadcrumb' );
}
if(!function_exists('skd_seo_schema_breadcrumb_open') ) {
    function skd_seo_schema_breadcrumb_open ( $bre ) {
        $bre = '<div itemscope itemtype="https://schema.org/BreadcrumbList" class="btn-group btn-breadcrumb">';
        return $bre;
    }
}
if(!function_exists('skd_seo_schema_breadcrumb_first') ) {

    function skd_seo_schema_breadcrumb_first ( $bre ) {
        $bre = '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="'.Url::base().'" class="btn btn-default" itemprop="item"><span itemprop="name">'.__('Trang chủ','trang-chu').'</span></a>
                    <meta itemprop="position" content="1" />
                </span>';
        return $bre;
    }
}
if(!function_exists('skd_seo_schema_breadcrumb_item')) {

    function skd_seo_schema_breadcrumb_item ( $bre, $val, $key ) {

        $ci =& get_instance();

        if( have_posts($val) ) {

            $slug = '';

            if(!empty($val->slug) ) $slug = $val->slug;
            else if(!empty( $ci->data['object']->slug)) $slug =  $ci->data['object']->slug;
            else if(!empty( $ci->data['category']->slug)) $slug =  $ci->data['category']->slug;

            $bre = '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="'.Url::permalink($slug).'" class="btn btn-default" itemprop="item"><span itemprop="name">'.$val->name.'</span></a>
            <meta itemprop="position" content="'.($key+1).'" />
            </span>';
        }

        return $bre;
    }
}
if(!function_exists('skd_seo_schema_breadcrumb_icon')) {
    function skd_seo_schema_breadcrumb_icon ($bre) {
        $bre = '<span><a class="btn btn-default btn-next"><i class="fal fa-angle-right"></i></a></span>';
        return $bre;
    }
}
if(!function_exists('skd_seo_schema_breadcrumb_css')) {
    function skd_seo_schema_breadcrumb_css () {
        ?>
        .breadcrumb span a.btn.btn-default { color: #333; background-color: #fff; border-color: #ccc;height:37px; position: relative; float: left;border: 0; border-radius: 0; }
        .btn-breadcrumb a.btn.btn-default { color:#fff;background-color: transparent;border-color: transparent;height:37px; position: relative; float: left;border: 0; border-radius: 0; } <?php
    }
}