<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Models\Post;

class SitemapPost
{
    static function register($listSiteMap)
    {
        $listSiteMap['post'] = ['date' => DATE_ATOM];

        return $listSiteMap;
    }

    static function sitemap($sitemap, $type, $paging)
    {
        $limit = 200;

        if (empty($paging))
        {
            $total = Post::count();

            $pagingTotal = ceil($total / $limit);

            if ($pagingTotal > 1)
            {
                $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

                for ($page = 1; $page <= $pagingTotal; $page++)
                {
                    $sitemap->item('sitemap.xml?p=post-' . $page, DATE_ATOM);
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
            $object = Post::offset(($paging - 1) * $limit)->limit($limit)->get();

            $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

            foreach ($object as $item)
            {
                $property = 0.6;

                if ($item->post_type == 'post') $property = 0.8;

                $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', $property);
            }

            $sitemap->setXml('</urlset>');
        }

        return $sitemap;
    }
}