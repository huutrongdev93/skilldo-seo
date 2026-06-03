<?php
namespace SkdSeo\Modules\Redirect;

use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Url;
use SkillDo\Validate\Rule;

class Form
{
    static function fields(\SkillDo\Cms\FormAdmin\FormAdmin $form): \SkillDo\Cms\FormAdmin\FormAdmin
    {
        $form
            ->setModel(\SkdSeo\Models\Redirect::class);

        $form->leftBottom()
            ->addGroup('add', 'Thông tin')
            ->addField('path', 'text', [
                'label' => 'Url chuyển hướng',
                'note' => 'Không bao gồm tên miền',
                'validations' => Rule::make()->notEmpty()->unique('redirect', 'path', [
                    'handleValue' => function ($value) {
                        $value = str_replace(Url::base(), '', $value);
                        return trim($value, '/');
                    }
                ])
            ])
            ->addField('to', 'text', [
                'label'         => 'Url đích',
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
                'href' => Url::admin('system/redirect'),
                'class'     => 'btn-back-to-redirect',
                'data-redirect' => 'admin_table_seo_redirect_list',
            ]);
        }

        if($view === 'edit')
        {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('add', ['href' => Url::admin('system/redirect/add'), 'text' => '', 'tooltip' => trans('button.add')]);
            $buttons[] = Admin::button('back', ['href' => Url::admin('system/redirect'), 'text' => '', 'tooltip' => trans('button.back')]);
        }

        $buttons = apply_filters('seo_redirect_form_buttons', $buttons);

        return $form->setButtons($buttons);
    }
}