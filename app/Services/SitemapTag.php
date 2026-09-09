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
            $listSiteMap['tag'] = [
                'date'  => SitemapService::maxDate(self::query()),
                'pages' => SitemapService::pageDates(self::query(), self::LIMIT),
            ];
        }

        return $listSiteMap;
    }

    static protected function query()
    {
        //Global scope của model đã giới hạn public = 1 ở frontend.
        //Sắp xếp nằm ở đây, không ở chỗ gọi — xem chú thích ở SitemapPost::query().
        return Tag::where('count', '>', 0)->orderBy('id');
    }

    static function sitemap($sitemap, $type = 'tag', $paging = 0)
    {
        if(!self::support())
        {
            return $sitemap;
        }

        $prefix = SeoTag::prefix();

        //Không còn sitemapindex lồng nhau — xem chú thích ở SitemapPost::sitemap().
        if(empty($paging)) $paging = 1;

        $objects = self::query()
            ->offset(($paging - 1) * self::LIMIT)
            ->limit(self::LIMIT)
            ->get();

        $sitemap->openUrlset();

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
            $sitemap->itemUrl($prefix.'/'.$item->slug, SitemapService::itemDate($item), 'weekly', 0.4, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }
}
