<?php

use SkillDo\Validate\Rule;

include_once 'model.php';
include_once 'table.php';
include_once 'button.php';
include_once 'ajax.php';
include_once 'helper.php';

Class Seo_Redirect_Admin {

    static function register($tabs) {
        $tabs['redirect'] = [
            'label'         => 'Chuyển Hướng',
            'group'         => 'marketing',
            'description'   => 'Quản lý chuyển hướng đường dẫn website',
            'callback'      => 'Seo_Redirect_Admin::render',
            'icon'          => '<i class="fa-light fa-diamond-turn-right"></i>',
            'form'          => false,
        ];
        return $tabs;
    }

    static function render(\SkillDo\Http\Request $request, $params): void
    {
        $view = $request->segment(4) ?? '';

        switch ($view) {
            case 'add':
                self::pageAdd($request);
                break;
            case 'edit':
                self::pageEdit($request);
                break;
            default:
                self::pageList($request);
                break;
        }
    }

    static function pageList(\SkillDo\Http\Request $request): void
    {
        $table = new Seo_Redirect_Table([
            'items' => [],
            'table' => 'redirect',
            'model' => model('redirect'),
            'module'=> 'seo_redirect',
        ]);

        Admin::view('components/page-default/page-index', [
            'module'    => 'seo_redirect',
            'name'      => trans('Chuyển hướng'),
            'table'     => $table,
            'tableId'     => 'admin_table_seo_redirect_list',
            'limitKey'    => 'admin_seo_redirect_limit',
            'ajax'        => 'AjaxAdminSeoRedirect::load',
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

        Plugin::view('skd-seo', 'views/redirect/script', [
            'form' => $form
        ]);
    }

    static function pageAdd(\SkillDo\Http\Request $request): void
    {
        Admin::creatForm('seo_redirect');

        Admin::view('components/page-default/page-save', [
            'module'  => 'seo_redirect',
            'object' => []
        ]);
    }

    static function pageEdit(\SkillDo\Http\Request $request): void
    {
        $id     = (int)$request->segment(5) ?? 0;

        $object = Seo_Redirect::get($id);

        if(have_posts($object)) {

            Admin::creatForm('seo_redirect', $object);

            Admin::view('components/page-default/page-save', [
                'module'  => 'seo_redirect',
                'object' => $object
            ]);
        }
    }

    static function form($form) {

        $form->leftBottom
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

    static function save($id, $insertData): int|SKD_Error
    {
        $insertData['path'] = str_replace(Url::base(), '', $insertData['path']);

        $insertData['path'] = trim($insertData['path'], '/');

        return Seo_Redirect::insert($insertData);
    }

    static function afterSave($id, $module): void
    {
        $module = Str::lower($module);

        if($module == 'seo_redirect') {

            $object = Seo_Redirect::get($id);

            Seo_Redirect_Helper::build($object);
        }
    }

    static function afterDelete($module, $data): void
    {
        $module = Str::lower($module);

        if($module == 'seo_redirect') {

            $object = Seo_Redirect::get($data);

            Seo_Redirect_Helper::buildRemove($object->path);
        }
    }
}
add_filter('skd_system_tab', 'Seo_Redirect_Admin::register', 50);
add_action('action_bar_system_right', 'Seo_Redirect_Admin::button');
add_filter('manage_seo_redirect_input', 'Seo_Redirect_Admin::form');
add_filter('form_submit_seo_redirect', 'Seo_Redirect_Admin::save', 10, 2);
add_action('save_object', 'Seo_Redirect_Admin::afterSave', 10, 2);
add_action('ajax_delete_after_success', 'Seo_Redirect_Admin::afterDelete', 10, 2);