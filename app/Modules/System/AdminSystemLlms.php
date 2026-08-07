<?php
namespace SkdSeo\Modules\System;

use Admin\Supports\Component;
use Admin\Supports\Components\BlockSystem;
use SkdSeo\Services\Llms\LlmsService;
use SkdSeo\Services\RobotsService;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;

/**
 * Khối cấu hình cho công cụ tìm kiếm AI (Cấu hình > Seo).
 *
 * Ba thứ quản trị viên cần quyết định:
 * - Có cho phép mô hình ngôn ngữ thu thập nội dung site không (ghi vào robots.txt).
 * - Có xuất bản toàn văn llms-full.txt không.
 * - Mỗi nhóm trong llms.txt liệt kê tối đa bao nhiêu mục.
 */
class AdminSystemLlms
{
    static function render(): void
    {
        $config = Option::get(LlmsService::OPTION);

        $config = (is_array($config)) ? $config : [];

        $form = form();

        $form->radio('seo_llms[ai_bots]', [
            1 => '<strong>Cho phép:</strong> mô hình ngôn ngữ được đọc và trích dẫn nội dung website.',
            0 => '<strong>Không cho phép:</strong> chặn '.count(RobotsService::aiAgents()).' crawler AI trong robots.txt.',
        ], [
            'label' => 'Crawler AI',
        ], $config['ai_bots'] ?? 1);

        $form->switch('seo_llms[full]', [
            'label' => 'Xuất bản llms-full.txt',
            'note'  => 'Bản toàn văn tại '.Url::base('llms-full.txt').' — dán luôn nội dung trang và bài viết để mô hình đọc trong một lần nạp.',
        ], $config['full'] ?? 1);

        $form->number('seo_llms[limit]', [
            'label' => 'Số mục tối đa mỗi nhóm',
            'note'  => 'Áp dụng cho bài viết và sản phẩm trong llms.txt. Mặc định '.LlmsService::LIMIT_DEFAULT.'. Bản toàn văn luôn bị giới hạn thêm ở '.LlmsService::LIMIT_FULL.' bài.',
        ], $config['limit'] ?? LlmsService::LIMIT_DEFAULT);

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Công cụ tìm kiếm AI', 'Quản lý cách mô hình ngôn ngữ đọc nội dung website (llms.txt, robots.txt)');
            $blockSystem->content($form);
        });
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        $seoLlms = $request->input('seo_llms');

        if(!is_array($seoLlms))
        {
            return;
        }

        Option::update(LlmsService::OPTION, [
            'ai_bots' => (isset($seoLlms['ai_bots']) && (int)$seoLlms['ai_bots'] === 0) ? 0 : 1,
            'full'    => (!empty($seoLlms['full'])) ? 1 : 0,
            'limit'   => max(1, (int)($seoLlms['limit'] ?? LlmsService::LIMIT_DEFAULT)),
        ]);
    }
}
