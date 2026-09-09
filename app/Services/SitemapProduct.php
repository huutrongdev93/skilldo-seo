<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;

use Ecommerce\Models\Product;

class SitemapProduct
{
    const LIMIT = 200;

    /**
     * Truy vấn dùng CHUNG cho cả register() lẫn sitemap() — xem chú thích đầy đủ
     * ở SitemapPost::query().
     *
     * Điều kiện `type = product` trước đây chỉ có ở nhánh dựng danh sách, còn
     * chỗ đếm số trang lại đếm toàn bảng, nên số trang khai ra nhiều hơn số
     * trang thật sự có nội dung.
     */
    protected static function query()
    {
        return Product::where('type', 'product')->orderBy('id');
    }

    static function register($listSiteMap)
    {
        if(class_exists('Product')) {
            $listSiteMap['product'] = [
                'date'  => SitemapService::maxDate(self::query()),
                'pages' => SitemapService::pageDates(self::query(), self::LIMIT),
            ];
        }
        return $listSiteMap;
    }

    //Không còn sitemapindex lồng nhau — xem chú thích ở SitemapPost::sitemap().
    static function sitemap($sitemap, $type, $paging)
    {
        if (empty($paging)) $paging = 1;

        $object = self::query()
            ->offset(($paging - 1) * self::LIMIT)
            ->limit(self::LIMIT)
            ->get();

        $sitemap->openUrlset();

        foreach ($object as $item)
        {
            //Slug theo tung ngon ngu (CMS 8.2.0): moi ngon ngu mot slug rieng,
            //ngon ngu chua dich thi Url::localizedSlugs() lui ve slug mac dinh.
            $sitemap->itemUrl(Url::localizedSlugs($item), SitemapService::itemDate($item), 'weekly', 1.0, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }
}
