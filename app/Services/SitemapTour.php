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
    static function support(): bool
    {
        return class_exists(Tour::class);
    }

    static function register($listSiteMap)
    {
        if(self::support())
        {
            $listSiteMap['tour'] = ['date' => DATE_ATOM];
        }

        return $listSiteMap;
    }

    static function sitemap($sitemap, $type, $paging)
    {
        if(!self::support())
        {
            return $sitemap;
        }

        $limit = 200;

        if (empty($paging))
        {
            $total = self::query()->count();

            $pagingTotal = ceil($total / $limit);

            if ($pagingTotal > 1)
            {
                $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

                for ($page = 1; $page <= $pagingTotal; $page++)
                {
                    $sitemap->item('sitemap.xml?p=tour-' . $page, DATE_ATOM);
                }

                $sitemap->setXml('</sitemapindex>');
            }
            else
            {
                $paging = 1;
            }
        }

        if ($paging != 0)
        {
            $object = self::query()
                ->orderBy('id', 'desc')
                ->offset(($paging - 1) * $limit)
                ->limit($limit)
                ->get();

            $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

            //Trang danh sách tour tổng chỉ khai báo ở trang sitemap đầu tiên
            if ($paging == 1)
            {
                $sitemap->itemUrl(config('travel::config.slug', 'tour'), DATE_ATOM, 'daily', 0.9);
            }

            foreach ($object as $item)
            {
                $sitemap->itemUrl(Url::permalink((string) $item->slug), DATE_ATOM, 'weekly', 0.8);
            }

            $sitemap->setXml('</urlset>');
        }

        return $sitemap;
    }

    /**
     * Chỉ tour đang bán được: public (global scope), không thùng rác, đã xuất bản.
     */
    protected static function query()
    {
        return Tour::where('trash', 0)->where('status', 'public');
    }
}
