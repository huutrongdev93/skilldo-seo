<?php
namespace SkdSeo\Modules\Log404;

use SkillDo\Cms\Plugin\Plugin;
use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Url;
use SkillDo\Validate\Rule;

Class AdminLog404
{
    static function register($tabs)
    {
        $tabs['log404'] = [
            'group' => 'marketing',
            'label' => 'Log 404',
            'description' => 'Quản lý log link 404',
            'callback' => 'SkdSeo\Modules\Log404\AdminLog404::render',
            'icon' => '<i class="fa-duotone fa-road-barrier"></i>',
            'form' => false,
        ];
        return $tabs;
    }

    static function render(\SkillDo\Http\Request $request, $params): void
    {
        static::pageList($request);
    }

    static function pageList(\SkillDo\Http\Request $request): void
    {
        $table = new Table();

        Admin::view('resources/page-default/page-index', [
            'module'    => 'log404',
            'name'      => trans('Log 404'),
            'table'     => $table,
            'tableId'     => 'admin_table_log404_list',
            'limitKey'    => 'admin_log404_limit',
        ]);

        $form = form();

        $form->setIsValid(true);

        $form->setCallbackValidJs('log404_submit');

        $form->radio('redirect', ['Mặc định', 'Url tự điền'], [
            'label' => 'Chuyển hướng',
            'validations' => Rule::make()->notEmpty()
        ]);

        $form->url('redirect_to', [
            'label' => 'Chuyển hướng đến',
        ]);

        Plugin::view('skd-seo', '404/script', [
            'form' => $form
        ]);
    }

    static function save($insertData)
    {
        $insertData['path'] = str_replace(Url::base(), '', $insertData['path']);

        $insertData['path'] = trim($insertData['path'], '/');

        return $insertData;
    }
}