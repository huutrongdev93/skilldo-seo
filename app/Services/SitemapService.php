<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Language;
use SkillDo\Cms\Support\Url;

class SitemapService
{
    private string $xml = '';

    static function sitemap(): void
    {
        $request = request();

        header('Content-type: application/xml');

        $sitemap = new SitemapService();

        $sitemap
            ->setXml('<?xml version="1.0" encoding="UTF-8"?>')
            ->setXml('<?xml-stylesheet type="text/xsl" href="'.Url::base().SKD_SEO_PATH.'assets/main-sitemap.xsl"?>');

        $type = $request->input('p');

        if(empty($type))
        {
            $sitemapList = apply_filters('seo_sitemap_list', []);

            $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

            if(hasItems($sitemapList))
            {
                foreach ($sitemapList as $sitemapKey => $item)
                {
                    $sitemap->item('sitemap.xml?p='.$sitemapKey, $item['date']);
                }
            }

            $sitemap->setXml('</sitemapindex>');
        }
        else
        {
            $number = 0;

            if(str_contains($type, '-'))
            {
                $parts = explode('-', $type);

                $last = end($parts);

                if (is_numeric($last))
                {
                    $number = $last;

                    array_pop($parts);
                }

                // Nối lại chuỗi
                $type = implode('-', $parts);
            }

            $sitemap = apply_filters('seo_sitemap_'.str_replace('-', '_', trim($type)).'_xml', $sitemap, $type, $number, $request);
        }



        $sitemap->render();
    }

    public function render(): void
    {
        echo trim($this->xml, "\n");
    }

    public function setXml($xml): SitemapService
    {
        $this->xml .= $xml."\n";;
        return $this;
    }

    public function item($url, $date): SitemapService
    {
        if(!Language::isMulti())
        {
            $item = '<sitemap>'."\n";
            $item .= '<loc>'.Url::base($url).'</loc>'."\n";
            $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
            $item .= '</sitemap>'."\n";
            $this->xml .= $item;
        }
        else
        {
            foreach (Language::listKey() as $lang)
            {
                $item = '<url>'."\n";
                $item .= '<loc>'.Url::base($lang.'/'.$url).'</loc>'."\n";
                foreach (Language::listKey() as $langKey)
                {
                    $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base($langKey.'/'.$url).'"/>'."\n";
                }
                $item .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.Url::base(Language::default().'/'.$url).'"/>'."\n";
                $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
                $item .= '</url>'."\n";
                $this->xml .= $item;
            }
        }

        return $this;
    }

    public function itemHome($date, $change, $priority): SitemapService
    {
        if(!Language::isMulti())
        {
            $item = '<url>'."\n";
            $item .= '<loc>'.Url::base().'</loc>'."\n";
            $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
            $item .= '<changefreq>'.$change.'</changefreq>'."\n";
            $item .= '<priority>'.$priority.'</priority>'."\n";
            $item .= '</url>'."\n";
            $this->xml .= $item;
        }
        else
        {
            foreach (Language::listKey() as $lang)
            {
                $item = '<url>'."\n";
                if($lang == Language::default())
                {
                    $item .= '<loc>'.Url::base().'</loc>'."\n";
                }
                else
                {
                    $item .= '<loc>'.Url::base($lang).'</loc>'."\n";
                }
                foreach (Language::listKey() as $langKey)
                {
                    if($langKey == Language::default())
                    {
                        $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base().'"/>'."\n";
                    }
                    else
                    {
                        $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base($langKey).'"/>'."\n";
                    }
                }
                $item .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.Url::base().'"/>'."\n";
                $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
                $item .= '<changefreq>'.$change.'</changefreq>'."\n";
                $item .= '<priority>'.$priority.'</priority>'."\n";
                $item .= '</url>'."\n";
                $this->xml .= $item;
            }
        }

        return $this;
    }

    /**
     * @param string|array<string, string> $url slug KHÔNG kèm tiền tố ngôn ngữ.
     *        Từ CMS 8.2.0 mỗi ngôn ngữ có thể mang slug riêng, nên nhận thêm dạng
     *        mảng ['vi' => 'gioi-thieu', 'en' => 'about-us'] — dựng bằng
     *        `Url::localizedSlugs($object)`. Truyền chuỗi thì mọi ngôn ngữ dùng
     *        chung slug đó, đúng như trước.
     */
    public function itemUrl($url, $date, $change, $priority): SitemapService
    {
        if(!Language::isMulti())
        {
            //Site một ngôn ngữ: lấy slug của ngôn ngữ mặc định nếu được truyền mảng.
            $single = is_array($url) ? ($url[Language::default()] ?? reset($url)) : $url;

            $item = '<url>'."\n";
            $item .= '<loc>'.Url::base($single).'</loc>'."\n";
            $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
            $item .= '<changefreq>'.$change.'</changefreq>'."\n";
            $item .= '<priority>'.$priority.'</priority>'."\n";
            $item .= '</url>'."\n";
            $this->xml .= $item;
        }
        else
        {
            $slugOf = function (string $language) use ($url): string {

                if(!is_array($url)) return (string) $url;

                //Ngôn ngữ chưa dịch slug thì dùng slug mặc định — URL đó vẫn phục vụ được.
                return (string) ($url[$language] ?? $url[Language::default()] ?? reset($url));
            };

            foreach (Language::listKey() as $lang)
            {
                $item = '<url>'."\n";
                $item .= '<loc>'.Url::base($lang.'/'.$slugOf($lang)).'</loc>'."\n";
                foreach (Language::listKey() as $langKey)
                {
                    $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base($langKey.'/'.$slugOf($langKey)).'"/>'."\n";
                }
                $item .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.Url::base(Language::default().'/'.$slugOf(Language::default())).'"/>'."\n";
                $item .= '<lastmod>'.date($date).'</lastmod>'."\n";
                $item .= '<changefreq>'.$change.'</changefreq>'."\n";
                $item .= '<priority>'.$priority.'</priority>'."\n";
                $item .= '</url>'."\n";
                $this->xml .= $item;
            }
        }

        return $this;
    }
}