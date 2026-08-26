<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Url;

use Ecommerce\Models\ProductCategory;

class SitemapProductCategory
{
    static function register($listSiteMap)
    {
        if(class_exists('ProductCategory'))
        {
            $listSiteMap['product-category'] = ['date' => DATE_ATOM];
        }
        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        $object = ProductCategory::all();

        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">');

        $sitemap->itemUrl('/', DATE_ATOM, 'daily', 1.0);

        foreach ($object as $item)
        {
            //Slug theo tung ngon ngu (CMS 8.2.0): moi ngon ngu mot slug rieng,
            //ngon ngu chua dich thi Url::localizedSlugs() lui ve slug mac dinh.
            $sitemap->itemUrl(Url::localizedSlugs($item), DATE_ATOM, 'weekly', 0.5);
        }

        $sitemap->setXml('</urlset>');

        return $sitemap;
    }
}