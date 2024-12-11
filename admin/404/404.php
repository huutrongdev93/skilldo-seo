<?php
use SkillDo\Validate\Rule;

include_once 'model.php';
include_once 'table.php';
include_once 'button.php';
include_once 'helper.php';
include_once 'ajax.php';

Class Log404_Admin {

    static function register($tabs) {
        $tabs['log404'] = [
            'group' => 'marketing',
            'label' => 'Log 404',
            'description' => 'Quản lý log link 404',
            'callback' => 'Log404_Admin::render',
            'icon' => '<i class="fa-duotone fa-road-barrier"></i>',
            'form' => false,
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
        $table = new Log404_Table([
            'items' => [],
            'table' => 'log404',
            'model' => model('log404'),
            'module'=> 'log404',
        ]);

        Admin::view('components/page-default/page-index', [
            'module'    => 'log404',
            'name'      => trans('Log 404'),
            'table'     => $table,
            'tableId'     => 'admin_table_log404_list',
            'limitKey'    => 'admin_log404_limit',
            'ajax'        => 'AjaxAdminLog404::load',
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

        Plugin::view('skd-seo', 'views/404/script', [
            'form' => $form
        ]);
    }

    static function pageAdd(\SkillDo\Http\Request $request): void
    {
        Admin::creatForm('log404');

        Admin::view('components/page-default/page-save', [
            'module'  => 'log404',
            'object' => []
        ]);
    }

    static function pageEdit(\SkillDo\Http\Request $request): void
    {
        $id     = (int)$request->segment(5) ?? 0;

        $object = Log404::get($id);

        if(have_posts($object)) {

            Admin::creatForm('log404', $object);

            Admin::view('components/page-default/page-save', [
                'module'  => 'log404',
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
                    'handlerValue' => function ($value) {
                        $value = str_replace(Url::base(), '', $value);
                        return trim($value, '/');
                    }
                ])
            ])
            ->addField('to', 'text', [
                'label'         => 'Url đích',
                'note'          => 'Để trống sẽ tự động lấy từ cấu hình seo',
                'validations'   => Rule::make()->notEmpty()->string()->url()
            ]);

        return $form;
    }

    static function save($id, $insertData): int|SKD_Error
    {
        $insertData['path'] = str_replace(Url::base(), '', $insertData['path']);

        $insertData['path'] = trim($insertData['path'], '/');

        return Log404::insert($insertData);
    }
}
add_filter('skd_system_tab', 'Log404_Admin::register', 50);
add_action('action_bar_system_right', 'Log404_Admin::button');
add_filter('manage_log404_input', 'Log404_Admin::form');
add_filter('form_submit_log404', 'Log404_Admin::save', 10, 2);