<?php
namespace SkdSeo\Services;

use SkdSeo\Services\Llms\LlmsService;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;

/**
 * Dựng nội dung robots.txt.
 *
 * Nội dung chính vẫn do người quản trị tự viết (option `skd_seo_robots`), phần
 * bổ sung ở đây chỉ gồm hai thứ mà admin không tự gõ được:
 *
 * 1. Dòng trỏ tới llms.txt — dạng CHÚ THÍCH, vì robots.txt không có chỉ thị
 *    chuẩn nào cho llms.txt. Người và mô hình đọc được, crawler bỏ qua.
 * 2. Khối chặn crawler AI khi quản trị chọn "Không cho phép" — việc này bắt buộc
 *    phải ghi thêm `User-agent` cho từng bot, không thể suy ra từ `User-agent: *`.
 *
 * Khi quản trị để mặc định "Cho phép" thì KHÔNG chèn gì thêm: các bot AI đã được
 * `User-agent: *` cho phép sẵn, viết thêm chỉ làm file rối.
 */
class RobotsService
{
    /**
     * Các crawler thu thập dữ liệu cho mô hình ngôn ngữ / công cụ tìm kiếm AI.
     */
    static function aiAgents(): array
    {
        return apply_filters('skd_seo_ai_agents', [
            'GPTBot',
            'ChatGPT-User',
            'OAI-SearchBot',
            'ClaudeBot',
            'Claude-User',
            'Claude-SearchBot',
            'anthropic-ai',
            'PerplexityBot',
            'Perplexity-User',
            'Google-Extended',
            'Applebot-Extended',
            'Meta-ExternalAgent',
            'Bytespider',
            'Amazonbot',
            'CCBot',
            'cohere-ai',
        ]);
    }

    /**
     * Quản trị có cho phép crawler AI thu thập nội dung không
     */
    static function allowAi(): bool
    {
        return (int)LlmsService::config('ai_bots', 1) === 1;
    }

    static function content(): string
    {
        $robots = trim((string)Option::get('skd_seo_robots'));

        if($robots === '')
        {
            $robots = 'User-agent: *'."\n"
                .'Disallow: /admin'."\n"
                .'Disallow: /cgi-bin/'."\n"
                .'Sitemap: '.Url::base('sitemap.xml');
        }

        $robots .= "\n";

        if(!self::allowAi())
        {
            $robots .= "\n".'# Không cho phép crawler AI thu thập nội dung'."\n";

            foreach (self::aiAgents() as $agent)
            {
                $robots .= 'User-agent: '.$agent."\n";
            }

            $robots .= 'Disallow: /'."\n";
        }

        $robots .= "\n".'# llms.txt: '.Url::base('llms.txt')."\n";

        if(LlmsService::fullEnabled())
        {
            $robots .= '# llms-full.txt: '.Url::base('llms-full.txt')."\n";
        }

        return apply_filters('skd_seo_robots_content', $robots);
    }
}
