<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Models\PostCategory;

class SitemapPostCategory
{
    static function register($listSiteMap)
    {
        $listSiteMap['post-category'] = ['date' => DATE_ATOM];

        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        $object = PostCategory::all();

        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

        foreach ($object as $item)
        {
            $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', 0.5);
        }

        $sitemap->setXml('</urlset>');

        return $sitemap;
    }
}