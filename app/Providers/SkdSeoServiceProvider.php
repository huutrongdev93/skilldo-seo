<?php

namespace SkdSeo\Providers;

use SkillDo\Cms\Http\Middleware\SetLanguage;
use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Option;
use SkillDo\ServiceProvider;

class SkdSeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        SetLanguage::exclude([
            'robots.txt',
            'llms.txt',
            'llms-full.txt',
            'sitemap.xml',
            'sitemap*.xml',
        ]);

        $this->mergeConfig(Option::get('seo_404'), "skd-seo::log404");

        if(!Admin::is())
        {
            $content = Option::get('header_script').Option::get('body_script').Option::get('footer_script');

            if(!empty($content))
            {
                $pattern = '/https?:\/\/[^\s"\'<>]+|\/[^\s"\'<>]+/i';

                preg_match_all($pattern, $content, $matches);

                $urls = $matches[0] ?? [];

                $domains = [];

                foreach ($urls as $url)
                {
                    // Nếu là URL tuyệt đối
                    if (preg_match('#^https?://#', $url))
                    {
                        $parsed = parse_url($url);

                        if (!empty($parsed['scheme']) && !empty($parsed['host']))
                        {
                            $domain = "{$parsed['scheme']}://{$parsed['host']}";

                            $domains[] = $domain;
                        }
                    }
                }

                $content_security_policy = config('security-headers.content_security_policy');

                foreach ($content_security_policy as $type => $policy)
                {
                    $content_security_policy[$type] = array_merge($policy, $domains);
                }

                app('config')->set('security-headers.content_security_policy', $content_security_policy);

                /*
                | Cùng danh sách tên miền đó dùng luôn cho `preconnect`.
                |
                | Mã analytics/chat do quản trị dán vào thường nằm cuối chuỗi tải:
                | trình duyệt chỉ biết tới tên miền của chúng khi đã parse tới thẻ
                | script, rồi mới bắt đầu DNS + TCP + TLS. Báo trước ở <head> cắt
                | được cả ba bước đó khỏi đường găng.
                |
                | Lấy từ chính ô script nên không phải khai cứng tên miền nào —
                | site không dán gì thì không có thẻ preconnect nào.
                */
                app('config')->set('skd-seo::preconnect', array_values(array_unique($domains)));
            }
        }
        else
        {
            $this->mergeConfig([
                ...config('request-sanitizer.excluded_fields'),
                'body_script', 'footer_script', 'header_script'
            ], 'request-sanitizer.excluded_fields', true);
        }
    }
}
