<?php
namespace SkdSeo\Supports;

use Theme\Supports\ThemeBreadcrumbItem;

class SKDSeoSchemaBreadcrumb
{
    public function __construct()
    {
        add_filter('breadcrumb_html_open', 		'SkdSeo\Supports\SKDSeoSchemaBreadcrumb::itemScope', 10 );
        add_filter('breadcrumb_html_list_attribute', 		'SkdSeo\Supports\SKDSeoSchemaBreadcrumb::itemListElement', 10 );
        add_filter('breadcrumb_item_data', 		'SkdSeo\Supports\SKDSeoSchemaBreadcrumb::itemProp', 10, 3 );
        add_filter('breadcrumb_html_list_after', 	'SkdSeo\Supports\SKDSeoSchemaBreadcrumb::itemMeta', 10, 3 );
    }

    static function itemScope ($bre): string
    {
        return 'itemscope itemtype="https://schema.org/BreadcrumbList"';
    }

    static function itemListElement ($bre): string
    {
        return 'itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"';
    }

    static function itemProp (ThemeBreadcrumbItem $item): ThemeBreadcrumbItem
    {
        $item->attribute('itemprop', 'item');
        $item->label('<span itemprop="name">'. $item->getLabel().'</span>');
        return $item;
    }

    static function itemMeta ($bre, ThemeBreadcrumbItem $item, $position): string
    {
        $bre .= '<meta itemprop="position" content="'.($position+1).'" />';
        return $bre;
    }
}