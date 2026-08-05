<?php
namespace SkdSeo\Modules\System;

use Admin\Supports\Component;
use Admin\Supports\Components\BlockSystem;
use SkillDo\Cms\Support\Option;
use Travel\Models\Tour;

/**
 * Khối cấu hình seo cho plugin travel trong Cấu hình > Seo.
 *
 * Trang danh sách tour tổng (/tour) không gắn với danh mục nào nên không có
 * nguồn seo_title/seo_description — phải khai báo tay, giống trang danh sách
 * sản phẩm của sicommerce.
 */
class AdminSystemTravel
{
    static function support(): bool
    {
        return class_exists(Tour::class);
    }

    static function render(): void
    {
        if(!self::support())
        {
            return;
        }

        $form = form();

        $form
            ->text('tour_title', ['label' => 'Meta title (Danh sách tour)'], Option::get('tour_title'))
            ->textarea('tour_description', ['label' => 'Meta description (Danh sách tour)'], Option::get('tour_description'))
            ->textarea('tour_keyword', ['label' => 'Meta keyword (Danh sách tour)'], Option::get('tour_keyword'));

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Trang Danh Sách Tour', 'Quản lý thông tin seo trang danh sách tour tổng');
            $blockSystem->content($form);
        });
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        if(!self::support())
        {
            return;
        }

        Option::update('tour_title', $request->input('tour_title'));

        Option::update('tour_description', $request->input('tour_description'));

        Option::update('tour_keyword', $request->input('tour_keyword'));
    }
}
