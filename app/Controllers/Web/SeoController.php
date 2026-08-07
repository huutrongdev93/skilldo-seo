<?php

namespace SkdSeo\Controllers\Web;

use SkdSeo\Services\Llms\LlmsService;
use SkdSeo\Services\RobotsService;
use SkdSeo\Services\SitemapService;
use SkillDo\Cms\Controller;
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
        echo RobotsService::content();

        response()->header('Content-Type', 'text/plain')->send();
        die;
    }

    /**
     * Mục lục nội dung cho mô hình ngôn ngữ.
     *
     * Toàn bộ phần dựng dữ liệu nằm ở LlmsService — plugin khác bổ sung nội dung
     * qua filter `skd_seo_llms_content`.
     */
    public function llms(Request $request): void
    {
        echo LlmsService::build()->render();

        echo '## Sitemap'."\n";

        echo '- [Sitemap]('.Url::base('sitemap.xml').')'."\n";

        if(LlmsService::fullEnabled())
        {
            echo '- [Toàn văn]('.Url::base('llms-full.txt').')'."\n";
        }

        response()->header('Content-Type', 'text/plain')->send();
        die;
    }

    /**
     * Bản toàn văn: nội dung trang và bài viết đã bóc thẻ HTML.
     */
    public function llmsFull(Request $request): void
    {
        if(!LlmsService::fullEnabled())
        {
            response()->setStatusCode(404)->header('Content-Type', 'text/plain')->send();
            die;
        }

        echo LlmsService::full();

        response()->header('Content-Type', 'text/plain')->send();
        die;
    }
}
