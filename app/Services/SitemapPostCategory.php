<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;

use SkillDo\Cms\Models\PostCategory;

class SitemapPostCategory
{
    static function register($listSiteMap)
    {
        $listSiteMap['post-category'] = ['date' => SitemapService::maxDate(PostCategory::query())];

        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        $object = PostCategory::all();

        $sitemap->openUrlset();

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