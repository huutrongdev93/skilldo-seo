<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;
use Travel\Models\Tour;

/**
 * Sitemap tour — plugin travel.
 *
 * Điều kiện lọc bám đúng Travel\Search\TourQuery::make(): tour trong thùng rác
 * hoặc chưa xuất bản mà lọt vào sitemap sẽ đẩy Google vào trang 404.
 */
class SitemapTour
{
    const LIMIT = 200;

    static function support(): bool
    {
        return class_exists(Tour::class);
    }

    static function register($listSiteMap)
    {
        if(self::support())
        {
            $listSiteMap['tour'] = [
                'date'  => SitemapService::maxDate(self::query()),
                'pages' => SitemapService::pageDates(self::query(), self::LIMIT),
            ];
        }

        return $listSiteMap;
    }

    //Không còn sitemapindex lồng nhau — xem chú thích ở SitemapPost::sitemap().
    static function sitemap($sitemap, $type, $paging)
    {
        if(!self::support())
        {
            return $sitemap;
        }

        if (empty($paging)) $paging = 1;

        $object = self::query()
            ->offset(($paging - 1) * self::LIMIT)
            ->limit(self::LIMIT)
            ->get();

        $sitemap->openUrlset();

        //Trang danh sách tour tổng chỉ khai báo ở trang sitemap đầu tiên
        if ($paging == 1)
        {
            $sitemap->itemUrl(config('travel::config.slug', 'tour'), SitemapService::maxDate(self::query()), 'daily', 0.9);
        }

        foreach ($object as $item)
        {
            $sitemap->itemUrl(Url::permalink((string) $item->slug), SitemapService::itemDate($item), 'weekly', 0.8, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }

    /**
     * Chỉ tour đang bán được: public (global scope), không thùng rác, đã xuất bản.
     *
     * Sắp xếp nằm ở đây, không ở chỗ gọi — xem chú thích ở SitemapPost::query().
     */
    protected static function query()
    {
        return Tour::where('trash', 0)->where('status', 'public')->orderBy('id', 'desc');
    }
}
