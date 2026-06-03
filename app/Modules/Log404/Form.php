<?php
namespace SkdSeo\Modules\Log404;

use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Url;
use SkillDo\Validate\Rule;

class Form
{
    static function fields(\SkillDo\Cms\FormAdmin\FormAdmin $form): \SkillDo\Cms\FormAdmin\FormAdmin
    {
        $form
            ->setModel(\SkdSeo\Models\Log404::class);

        $form->leftBottom()
            ->addGroup('add', 'Thông tin')
            ->text('path', [
                'label' => 'Url chuyển hướng',
                'note' => 'Không bao gồm tên miền',
                'validations' => Rule::make()->notEmpty()->unique('redirect', 'path', [
                    'handlerValue' => function ($value) {
                        $value = str_replace(Url::base(), '', $value);
                        return trim($value, '/');
                    }
                ])
            ])
            ->text('to', [
                'label'         => 'Url đích',
                'note'          => 'Để trống sẽ tự động lấy từ cấu hình seo',
                'validations'   => Rule::make()->notEmpty()->string()->url()
            ]);

        return $form;
    }

    static function buttons(\SkillDo\Cms\FormAdmin\FormAdmin $form, $object = null): \SkillDo\Cms\FormAdmin\FormAdmin
    {
        $buttons = [];

        $view = request()->segment(4);

        if($view === 'add')
        {
            $buttons[] = Admin::button('save', ['type' => 'submit']);
            $buttons[] = Admin::button('back', [
                'href' => Url::admin('system/log404'),
                'class'     => 'btn-back-to-redirect',
                'data-redirect' => 'admin_table_log404_list',
            ]);
        }

        if($view === 'edit')
        {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('add', ['href' => Url::admin('system/log404/add'), 'text' => '', 'tooltip' => trans('button.add')]);
            $buttons[] = Admin::button('back', ['href' => Url::admin('system/log404'), 'text' => '', 'tooltip' => trans('button.back')]);
        }

        $buttons = apply_filters('log404_form_buttons', $buttons);

        return $form->setButtons($buttons);
    }
}