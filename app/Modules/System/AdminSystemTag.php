<?php
namespace SkdSeo\Modules\System;

use Admin\Supports\Component;
use Admin\Supports\Components\BlockSystem;
use SkdSeo\Services\SeoTag;
use SkillDo\Cms\Support\Option;

/**
 * Khối cấu hình seo cho trang lưu trữ theo thẻ trong Cấu hình > Seo.
 *
 * Thẻ được sinh tự động khi biên tập viên gõ vào ô nhập thẻ của bài viết nên số
 * lượng phình rất nhanh và phần lớn chỉ có 1-2 bài. Hai tùy chọn ở đây để giữ
 * cho phần thẻ không làm loãng chất lượng index của website.
 */
class AdminSystemTag
{
    static function support(): bool
    {
        return SeoTag::support();
    }

    static function render(): void
    {
        if(!self::support())
        {
            return;
        }

        $config = Option::get(SeoTag::OPTION);

        $config = (is_array($config)) ? $config : [];

        $form = form();

        $form->switch('seo_tag[sitemap]', [
            'label' => 'Đưa thẻ vào sitemap',
            'note'  => 'Chỉ những thẻ đã có bài viết mới được khai báo trong sitemap.xml',
        ], $config['sitemap'] ?? 1);

        $form->number('seo_tag[min_count]', [
            'label' => 'Số bài tối thiểu để index',
            'note'  => 'Thẻ có ít bài hơn mức này sẽ được gắn noindex. Nhập 0 để cho index tất cả các thẻ.',
        ], $config['min_count'] ?? 1);

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Trang Thẻ', 'Quản lý thông tin seo cho trang lưu trữ theo thẻ');
            $blockSystem->content($form);
        });
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        if(!self::support())
        {
            return;
        }

        $seoTag = $request->input('seo_tag');

        if(!is_array($seoTag))
        {
            return;
        }

        Option::update(SeoTag::OPTION, [
            'sitemap'   => (!empty($seoTag['sitemap'])) ? 1 : 0,
            'min_count' => max(0, (int)($seoTag['min_count'] ?? 1)),
        ]);
    }
}
