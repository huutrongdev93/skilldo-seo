<?php
namespace SkdSeo\Services;

use Travel\Models\Archive;

/**
 * Sitemap trang lọc / landing page SEO của plugin travel (travel_archive).
 *
 * Đây là các trang "tour giá rẻ", "tour 3 ngày 2 đêm"… nội dung biên tập tay
 * nhưng danh sách chạy bằng bộ lọc. Đường dẫn: /{slug_archive}/{slug}.
 */
class SitemapTourArchive
{
    static function support(): bool
    {
        return class_exists(Archive::class);
    }

    static function register($listSiteMap)
    {
        if(self::support())
        {
            $listSiteMap['tour-archive'] = ['date' => DATE_ATOM];
        }

        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        if(!self::support())
        {
            return $sitemap;
        }

        $prefix = trim((string) config('travel::config.slug_archive', 'du-lich'), '/');

        $object = Archive::where('public', 1)->orderBy('order')->get();

        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

        foreach ($object as $item)
        {
            if(empty($item->slug))
            {
                continue;
            }

            $sitemap->itemUrl($prefix.'/'.$item->slug, DATE_ATOM, 'weekly', 0.7);
        }

        $sitemap->setXml('</urlset>');

        return $sitemap;
    }
}
