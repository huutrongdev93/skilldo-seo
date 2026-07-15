<?php

namespace SkdSeo\Controllers\Web;

use Illuminate\Support\Str;
use SkdSeo\Services\Llms\LlmsContent;
use SkdSeo\Services\SitemapService;
use SkillDo\Cms\Controller;
use SkillDo\Cms\Models\Page;
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

    public function llms(Request $request): void
    {
        $llms = new LlmsContent();

        $llms->group('main')->setDescription(Option::get('general_description'));

        //Page
        $llms->group('page')->addItem('Trang chủ', Url::base(), Option::get('general_description'));

        $pages = Page::all();

        foreach ($pages as $page)
        {
            if($page->slug == 'lien-he' || $page->slug == 'contact')
            {
                $llms->group('help')->addItem($page->title, Url::base($page->slug), 'Liên hệ với chúng tôi');
                continue;
            }

            $llms->group('page')->addItem($page->title, Url::base($page->slug));
        }

        //Post
        $postCategories = \SkillDo\Cms\Models\PostCategory::all();

        foreach ($postCategories as $category)
        {
            $llms->group('category')->addItem($category->name, Url::base($category->slug));
        }

        //Product
        if(class_exists('\Ecommerce\Models\Product'))
        {
            $llms->group('product')->addItem('All products', Url::base(URL_PRODUCT));

            $productCategories = \Ecommerce\Models\ProductCategory::all();

            foreach ($productCategories as $category)
            {
                $description = trim(Str::clear($category->excerpt ?? ''));

                $llms->group('product_category')
                    ->addItem($category->name, Url::base($category->slug), $description);
            }
        }

        //FAQ
        if(class_exists('QuestionAnswer'))
        {
            $llms->group('help')->addItem('FAQ', Url::base('faq'), 'Câu hỏi thường gặp');
        }

        $llms = apply_filters('skd_seo_llms_content', $llms);

        echo $llms->render();

        echo '## Sitemap'."\n";

        echo '- [Sitemap]('.Url::base('sitemap.xml').')';

        response()->header('Content-Type', 'text/plain')->send();
        die;
    }
}
