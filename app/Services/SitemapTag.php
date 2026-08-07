<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Models\Tag;

/**
 * Sitemap trang lưu trữ theo thẻ.
 *
 * Thẻ không ghi vào bảng `routes` (dùng chung route `/tag/{slug}`) nên url phải
 * tự ghép từ tiền tố cấu hình, không lấy được từ Router như danh mục.
 *
 * Chỉ đưa thẻ ĐÃ có bài vào sitemap: thẻ rỗng là trang không có nội dung, khai
 * báo cho Google chỉ tốn crawl budget.
 */
class SitemapTag
{
    const LIMIT = 200;

    static function support(): bool
    {
        return SeoTag::support() && SeoTag::inSitemap();
    }

    static function register($listSiteMap)
    {
        if(self::support())
        {
            $listSiteMap['tag'] = ['date' => DATE_ATOM];
        }

        return $listSiteMap;
    }

    static protected function query()
    {
        //Global scope của model đã giới hạn public = 1 ở frontend
        return Tag::where('count', '>', 0);
    }

    static function sitemap($sitemap, $type = 'tag', $paging = 0)
    {
        if(!self::support())
        {
            return $sitemap;
        }

        $prefix = SeoTag::prefix();

        if(empty($paging))
        {
            $total = self::query()->count();

            $pagingTotal = ceil($total / self::LIMIT);

            if($pagingTotal > 1)
            {
                $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

                for ($page = 1; $page <= $pagingTotal; $page++)
                {
                    $sitemap->item('sitemap.xml?p=tag-'.$page, DATE_ATOM);
                }

                $sitemap->setXml('</sitemapindex>');
            }
            else
            {
                $paging = 1;
            }
        }

        if($paging != 0)
        {
            $objects = self::query()
                ->orderBy('id')
                ->offset(($paging - 1) * self::LIMIT)
                ->limit(self::LIMIT)
                ->get();

            $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

            foreach ($objects as $item)
            {
                if(empty($item->slug))
                {
                    continue;
                }

                /*
                | Dùng slug thô chứ không dùng Url::tag(): itemUrl() tự thêm tiền
                | tố ngôn ngữ cho site đa ngữ, đi qua Url::tag() sẽ bị thêm hai lần.
                */
                $sitemap->itemUrl($prefix.'/'.$item->slug, DATE_ATOM, 'weekly', 0.4);
            }

            $sitemap->setXml('</urlset>');
        }

        return $sitemap;
    }
}
