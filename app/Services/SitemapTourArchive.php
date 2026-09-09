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
            $listSiteMap['tour-archive'] = ['date' => SitemapService::maxDate(Archive::where('public', 1))];
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

        $sitemap->openUrlset();

        foreach ($object as $item)
        {
            if(empty($item->slug))
            {
                continue;
            }

            $sitemap->itemUrl($prefix.'/'.$item->slug, SitemapService::itemDate($item), 'weekly', 0.7, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }
}
