<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;

use Ecommerce\Models\Product;

class SitemapProduct
{
    static function register($listSiteMap)
    {
        if(class_exists('Product')) {
            $listSiteMap['product'] = ['date' => DATE_ATOM];
        }
        return $listSiteMap;
    }

    static function sitemap($sitemap, $type, $paging)
    {
        $limit = 200;

        if (empty($paging))
        {
            $total = Product::count();

            $pagingTotal = ceil($total / $limit);

            if ($pagingTotal > 1)
            {
                $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

                for ($page = 1; $page <= $pagingTotal; $page++)
                {
                    $sitemap->item('sitemap.xml?p=product-' . $page, DATE_ATOM);
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
            $object = Product::where('type', 'product')
                ->offset(($paging - 1) * $limit)
                ->limit($limit)
                ->get();

            $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

            foreach ($object as $item)
            {
                //Slug theo tung ngon ngu (CMS 8.2.0): moi ngon ngu mot slug rieng,
                //ngon ngu chua dich thi Url::localizedSlugs() lui ve slug mac dinh.
                $sitemap->itemUrl(Url::localizedSlugs($item), DATE_ATOM, 'weekly', 1.0);
            }

            $sitemap->setXml('</urlset>');
        }

        return $sitemap;
    }
}