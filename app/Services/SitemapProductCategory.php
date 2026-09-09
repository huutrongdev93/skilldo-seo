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
            $listSiteMap['product-category'] = ['date' => SitemapService::maxDate(ProductCategory::query())];
        }
        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        $object = ProductCategory::all();

        $sitemap->openUrlset();

        /*
        | Slug rong, KHONG phai '/': itemUrl() ghep chuoi nay sau tien to ngon ngu,
        | nen dau gach cheo o day sinh ra domain.com/en// tren site da ngu.
        */
        $sitemap->itemUrl('', SitemapService::maxDate(ProductCategory::query()), 'daily', 1.0);

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