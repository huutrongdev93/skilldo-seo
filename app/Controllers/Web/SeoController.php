<?php

namespace SkdSeo\Controllers\Web;

use SkdSeo\Services\SitemapService;
use SkillDo\Cms\Controller;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;
use SkillDo\Http\Request;

class SeoController extends Controller
{
    public function sitemap(Request $request): void
    {
        SitemapService::sitemap();
        die;
    }

    public function robots(Request $request): void
    {
        $robots = trim(Option::get('skd_seo_robots'));

        if(!empty($robots))
        {
            echo $robots;
        }
        else
        {
            echo 'User-agent: *'."\n";
            echo 'Disallow:/admin'."\n";
            echo 'Disallow: /cgi-bin/'."\n";
            echo 'Sitemap: '.Url::base('sitemap.xml')."\n";
        }

        response()->header('Content-Type', 'text/plain')->send();
        die;
    }
}
