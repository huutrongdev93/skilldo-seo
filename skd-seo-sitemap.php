<?php
class SKDSeoSitemap {

    private string $xml = '';

    static function sitemap(): void
    {
        $request = request();
        header('Content-type: application/xml');
        $sitemap = new SKDSeoSitemap();
        $sitemap
            ->setXml('<?xml version="1.0" encoding="UTF-8"?>')
            ->setXml('<?xml-stylesheet type="text/xsl" href="'.Url::base().SKD_SEO_PATH.'assets/main-sitemap.xsl"?>');
        $type = $request->input('p');
        if(empty($type)) {
            $sitemapList = apply_filters('seo_sitemap_list', []);
            $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
            if(have_posts($sitemapList)) {
                foreach ($sitemapList as $sitemapKey => $item) {
                    $sitemap->item('sitemap.xml?p='.$sitemapKey, $item['date']);
                }
            }
            $sitemap->setXml('</sitemapindex>');
        }
        else {

            if(str_contains($type, '-')) {
                $type = explode('-', $type);
                $type = $type[0];
            }

            $sitemap = apply_filters('seo_sitemap_'.str_replace('-', '_', trim($type)).'_xml', $sitemap, $request);
        }
        $sitemap->render();
    }

    public function render(): void
    {
        echo trim($this->xml, "\n");
    }

    public function setXml($xml): SKDSeoSitemap
    {
        $this->xml .= $xml."\n";;
        return $this;
    }

    public function item($url, $date): SKDSeoSitemap
    {
        $item = '<sitemap>'."\n";
        $item .= '<loc>'.Url::base($url).'</loc>'."\n";
        $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
        $item .= '</sitemap>'."\n";
        $this->xml .= $item;
        return $this;
    }

    public function itemUrl($url, $date, $change, $priority): SKDSeoSitemap
    {
        $item = '<url>'."\n";
        $item .= '<loc>'.Url::base($url).'</loc>'."\n";
        $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
        $item .= '<changefreq>'.$change.'</changefreq>'."\n";
        $item .= '<priority>'.$priority.'</priority>'."\n";
        $item .= '</url>'."\n";
        $this->xml .= $item;
        return $this;
    }
}

function skd_seo_sitemap($request): void
{
    SKDSeoSitemap::sitemap();
    die;
}

class SiteMapPage {
    static function register($listSiteMap) {
        $listSiteMap['page'] = ['date' => DATE_ATOM];
        return $listSiteMap;
    }
    static function sitemap($sitemap) {
        $object = Pages::gets(Qr::set());
        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        $sitemap->itemUrl('/', DATE_ATOM, 'daily', 1.0);
        foreach ($object as $item) {
            $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', 0.5);
        }
        $sitemap->setXml('</urlset>');
        return $sitemap;
    }
}
add_filter('seo_sitemap_list', 'SiteMapPage::register');
add_filter('seo_sitemap_page_xml', 'SiteMapPage::sitemap');

class SiteMapPostCategory {
    static function register($listSiteMap) {
        $listSiteMap['post-category'] = ['date' => DATE_ATOM];
        return $listSiteMap;
    }
    static function sitemap($sitemap) {
        $object = PostCategory::gets(Qr::set());
        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        $sitemap->itemUrl('/', DATE_ATOM, 'daily', 1.0);
        foreach ($object as $item) {
            $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', 0.5);
        }
        $sitemap->setXml('</urlset>');
        return $sitemap;
    }
}
add_filter('seo_sitemap_list', 'SiteMapPostCategory::register');
add_filter('seo_sitemap_post_category_xml', 'SiteMapPostCategory::sitemap');

class SiteMapPost
{
    static function register($listSiteMap)
    {
        $listSiteMap['post'] = ['date' => DATE_ATOM];
        return $listSiteMap;
    }

    static function sitemap($sitemap, \SkillDo\Http\Request $request)
    {
        $type = $request->input('p');
        $paging = 0;
        if(str_contains($type, '-')) {
            $type = explode('-', $type);
            if(count($type) == 3 && $type[1] == 'paging' && is_numeric($type[2])) {
                $paging = $type[2];
            }
        }

        $limit = 200;
        if ($paging == 0) {
            $total = Posts::count();
            $pagingTotal = ceil($total / $limit);
            if ($pagingTotal > 1) {
                $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
                for ($page = 1; $page <= $pagingTotal; $page++) {
                    $sitemap->item('sitemap.xml?p=post-paging-' . $page, DATE_ATOM);
                }
                $sitemap->setXml('</sitemapindex>');
            } else {
                $paging = 1;
            }
        }
        if ($paging != 0) {
            $object = Posts::gets(Qr::set()->offset(($paging - 1) * $limit)->limit($limit));
            $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
            foreach ($object as $item) {
                $property = 0.6;
                if ($item->post_type == 'post') $property = 0.8;
                $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', $property);
            }
            $sitemap->setXml('</urlset>');
        }
        return $sitemap;
    }
}
add_filter('seo_sitemap_list', 'SiteMapPost::register');
add_filter('seo_sitemap_post_xml', 'SiteMapPost::sitemap', 10, 2);

class SiteMapProductCategory
{
    static function register($listSiteMap)
    {
        if(class_exists('ProductCategory')) {
            $listSiteMap['product-category'] = ['date' => DATE_ATOM];
        }
        return $listSiteMap;
    }

    static function sitemap($sitemap)
    {
        $object = ProductCategory::gets(Qr::set());
        $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        $sitemap->itemUrl('/', DATE_ATOM, 'daily', 1.0);
        foreach ($object as $item) {
            $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', 0.5);
        }
        $sitemap->setXml('</urlset>');
        return $sitemap;
    }
}

add_filter('seo_sitemap_list', 'SiteMapProductCategory::register');
add_filter('seo_sitemap_product_category_xml', 'SiteMapProductCategory::sitemap');

class SiteMapProduct
{
    static function register($listSiteMap)
    {
        if(class_exists('Product')) {
            $listSiteMap['product'] = ['date' => DATE_ATOM];
        }
        return $listSiteMap;
    }

    static function sitemap($sitemap, \SkillDo\Http\Request $request)
    {
        $limit = 200;
        $type = $request->input('p');
        $paging = 0;
        if(str_contains($type, '-')) {
            $type = explode('-', $type);
            if(count($type) == 3 && $type[1] == 'paging' && is_numeric($type[2])) {
                $paging = $type[2];
            }
        }
        if ($paging == 0) {
            $total = Product::count(Qr::set('type', 'product')->where('trash', 0)->where('public', 1));
            $pagingTotal = ceil($total / $limit);
            if ($pagingTotal > 1) {
                $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
                for ($page = 1; $page <= $pagingTotal; $page++) {
                    $sitemap->item('sitemap.xml?p=product-paging-' . $page, DATE_ATOM);
                }
                $sitemap->setXml('</sitemapindex>');
            } else {
                $paging = 1;
            }
        }

        if ($paging != 0) {
            $object = Product::gets(Qr::set('type', 'product')->where('trash', 0)->where('public', 1)->offset(($paging - 1) * $limit)->limit($limit));
            $sitemap->setXml('<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
            foreach ($object as $item) {
                $sitemap->itemUrl($item->slug, DATE_ATOM, 'weekly', 1.0);
            }
            $sitemap->setXml('</urlset>');
        }

        return $sitemap;
    }
}

add_filter('seo_sitemap_list', 'SiteMapProduct::register');
add_filter('seo_sitemap_product_xml', 'SiteMapProduct::sitemap', 10, 2);
