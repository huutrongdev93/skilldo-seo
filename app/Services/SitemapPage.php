<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;

use SkillDo\Cms\Models\Page;

class SitemapPage
{
    static function register($listSiteMap)
    {
        $listSiteMap['page'] = ['date' => SitemapService::maxDate(Page::query())];

        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        $object = Page::all();

        $sitemap->openUrlset();

        //Trang chu doi theo noi dung cua no; moc dung nhat la lan sua trang gan nhat.
        $sitemap->itemHome(SitemapService::maxDate(Page::query()), 'daily', 1.0);

        foreach ($object as $item)
        {
            //Slug theo tung ngon ngu (CMS 8.2.0): moi ngon ngu mot slug rieng,
            //ngon ngu chua dich thi Url::localizedSlugs() lui ve slug mac dinh.
            $sitemap->itemUrl(Url::localizedSlugs($item), SitemapService::itemDate($item), 'weekly', 0.5, SitemapService::itemImages($item));
        }

        $sitemap->closeUrlset();

        return $sitemap;
    }
}