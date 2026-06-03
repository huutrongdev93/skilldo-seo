<?php
namespace SkdSeo\Modules\Redirect;

use Admin\Supports\FormAdminHelper;
use SkillDo\Cache\Cache;
use SkillDo\Cms\Plugin\Plugin;
use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Url;
use SkillDo\Validate\Rule;

Class AdminRedirect
{
    static function register($tabs)
    {
        $tabs['redirect'] = [
            'label'         => 'Chuyển Hướng',
            'group'         => 'marketing',
            'description'   => 'Quản lý chuyển hướng đường dẫn website',
            'callback'      => 'SkdSeo\Modules\Redirect\AdminRedirect::render',
            'icon'          => '<i class="fa-light fa-diamond-turn-right"></i>',
            'form'          => false,
        ];
        return $tabs;
    }

    static function render(\SkillDo\Http\Request $request, $params): void
    {
        $view = $request->segment(4) ?? '';

        switch ($view)
        {
            case 'add':
                static::pageAdd($request);
                break;
            default:
                static::pageList($request);
                break;
        }
    }

    static function pageList(\SkillDo\Http\Request $request): void
    {
        $table = new Table();

        Admin::view('resources/page-default/page-index', [
            'name'      => trans('Chuyển hướng'),
            'module'    => 'seo_redirect',
            'table'     => $table,
            'tableId'     => 'admin_table_seo_redirect_list',
            'limitKey'    => 'admin_seo_redirect_limit',
        ]);

        $form = form();

        $form->setIsValid(true);

        $form->setCallbackValidJs('seo_redirect_submit');

        $form->radio('redirect', ['Bật', 'Tắt'], [
            'label' => 'Chuyển hướng',
            'validations' => Rule::make()->notEmpty()
        ]);

        $form->url('redirect_to', [
            'label' => 'Chuyển hướng đến',
            'validations' => Rule::make()->notEmpty()
        ]);

        Plugin::view('skd-seo', 'redirect/script', [
            'form' => $form
        ]);
    }

    static function pageAdd(\SkillDo\Http\Request $request): void
    {
        Admin::view('resources/page-default/page-save', [
            'module'    => 'seo_redirect',
            'form'      => FormAdminHelper::getForm('seo_redirect'),
            'object'    => []
        ]);
    }

    static function save($insertData)
    {
        $insertData['path'] = str_replace(Url::base(), '', $insertData['path']);

        $insertData['path'] = trim($insertData['path'], '/');

        return $insertData;
    }

    static function afterSave($id, $request): void
    {
        Cache::delete('seo_redirect_'.md5($request->input('path')));
    }

    static function afterDelete($data, $request): void
    {
        Cache::delete('seo_redirect_', true);
    }
}