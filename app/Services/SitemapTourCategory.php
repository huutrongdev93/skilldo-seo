<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;
use Travel\Models\Category;

/**
 * Sitemap danh mục tour — plugin travel.
 *
 * Một bảng travel_category chứa cả 4 trục phân loại (category / destination /
 * theme / season), tất cả đều có route riêng nên đều vào sitemap.
 */
class SitemapTourCategory
{
    static function support(): bool
    {
        return class_exists(Category::class);
    }

    static function register($listSiteMap)
    {
        if(self::support())
        {
            $listSiteMap['tour-category'] = ['date' => DATE_ATOM];
        }

        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        if(!self::support())
        {
            return $sitemap;
        }

        //Global scope của model đã giới hạn public = 1 ở frontend
        $object = Category::orderBy('lft')->get();

        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

        foreach ($object as $item)
        {
            if(empty($item->slug))
            {
                continue;
            }

            $sitemap->itemUrl(Url::permalink((string) $item->slug), DATE_ATOM, 'weekly', 0.6);
        }

        $sitemap->setXml('</urlset>');

        return $sitemap;
    }
}
