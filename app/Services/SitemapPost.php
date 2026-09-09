<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;

use SkillDo\Cms\Models\Post;

class SitemapPost
{
    const LIMIT = 200;

    /**
     * Truy vấn dùng CHUNG cho cả register() lẫn sitemap().
     *
     * Thứ tự sắp xếp nằm ở đây chứ không ở chỗ gọi, vì hai lý do:
     *
     *   - Phân trang bằng offset mà không sắp xếp thì thứ tự do cơ sở dữ liệu tự
     *     quyết và có thể khác nhau giữa hai lần truy vấn — một bài vừa lọt hai
     *     trang sitemap vừa vắng mặt ở trang khác.
     *   - `pageDates()` phải cắt đúng những lát mà `sitemap()` sẽ dựng, nếu không
     *     ngày sẽ gắn nhầm trang.
     */
    protected static function query()
    {
        return Post::query()->orderBy('id');
    }

    static function register($listSiteMap)
    {
        $listSiteMap['post'] = [
            'date'  => SitemapService::maxDate(self::query()),
            'pages' => SitemapService::pageDates(self::query(), self::LIMIT),
        ];

        return $listSiteMap;
    }

    /**
     * KHÔNG còn nhánh dựng sitemapindex lồng nhau.
     *
     * Trang index gốc nay khai thẳng từng trang con qua khoá `pages` ở
     * `register()`. Chuẩn sitemap không cho phép một sitemapindex trỏ tới một
     * sitemapindex khác, nên trước đây Google dừng ở tầng hai và không bao giờ
     * đọc tới các trang từ 1 trở đi.
     *
     * `?p=post` không kèm số trang vẫn chạy và trả về trang 1, để đường dẫn cũ
     * Google đã lưu không thành 404.
     */
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
            $property = 0.6;

            if ($item->post_type == 'post') $property = 0.8;

            //Slug theo tung ngon ngu (CMS 8.2.0): moi ngon ngu mot slug rieng,
            //ngon ngu chua dich thi Url::localizedSlugs() lui ve slug mac dinh.
            $sitemap->itemUrl(Url::localizedSlugs($item), SitemapService::itemDate($item), 'weekly', $property, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }
}
