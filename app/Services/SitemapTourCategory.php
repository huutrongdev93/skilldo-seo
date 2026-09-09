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
            $listSiteMap['tour-category'] = ['date' => SitemapService::maxDate(Category::query())];
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

        $sitemap->openUrlset();

        foreach ($object as $item)
        {
            if(empty($item->slug))
            {
                continue;
            }

            $sitemap->itemUrl(Url::permalink((string) $item->slug), SitemapService::itemDate($item), 'weekly', 0.6, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }
}
