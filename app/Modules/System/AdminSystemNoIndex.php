<?php
namespace SkdSeo\Modules\System;

use Admin\Supports\Component;
use Admin\Supports\Components\BlockSystem;
use SkdSeo\Services\NoIndexService;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;

/**
 * Khối "Chặn lập chỉ mục" (Cấu hình > Seo).
 *
 * Một công tắc duy nhất cho site demo / site đang dựng. Đặt ở đầu tab vì nó ghi
 * đè mọi thiết lập seo bên dưới — để lẫn xuống cuối thì lúc bàn giao site không
 * ai nhớ ra mà tắt.
 */
class AdminSystemNoIndex
{
    static function render(): void
    {
        $enabled = NoIndexService::enabled();

        $form = form();

        $form->switch(NoIndexService::OPTION, [
            'label' => 'Chặn lập chỉ mục toàn website',
            'note'  => 'Bật khi đây là site demo hoặc site đang dựng: mọi trang xuất meta robots <b>'.NoIndexService::directive().'</b>, gửi kèm header X-Robots-Tag và '.Url::base('robots.txt').' trả về <b>Disallow: /</b>.',
        ], ($enabled) ? 1 : 0);

        if($enabled)
        {
            $form->none('<p style="color:#d63939"><b>Website đang bị chặn khỏi Google.</b> Nhớ tắt mục này trước khi bàn giao site chính thức, nếu không site sẽ không bao giờ lên kết quả tìm kiếm.</p>');
        }

        $form->none('<p><b>Lưu ý:</b> khi bật, robots.txt chặn luôn việc thu thập nên crawler không đọc được thẻ noindex nữa. Với website đã từng được index và muốn gỡ hẳn khỏi kết quả tìm kiếm, phải để crawler vào đọc thẻ noindex — trường hợp đó dùng thiết lập No Index của từng trang thay vì công tắc này.</p>');

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Chặn lập chỉ mục', 'Chặn toàn bộ website khỏi công cụ tìm kiếm — dùng cho site demo, site đang dựng');
            $blockSystem->content($form);
        });
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        Option::update(NoIndexService::OPTION, (!empty($request->input(NoIndexService::OPTION))) ? 1 : 0);
    }
}
