<?php
class AdminSeoRedirectButton {
    /**
     * Thêm buttons action cho header của table
     * @param $buttons
     * @return array
     */
    static function tableHeaderButton($buttons): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::admin('system/redirect/add')]);
        $buttons[] = Admin::button('reload');
        return $buttons;
    }

    /**
     * Thêm buttons cho hành dộng hàng loạt
     * @param array $actionList
     * @return array
     */
    static function bulkAction(array $actionList): array
    {
        return $actionList;
    }

    /**
     * Thêm button cho trang thêm mới edit
     * @param $module
     * @return void
     */
    static function formButton($module): void
    {
        $buttons = [];

        $view = Url::segment(4);

        if($view == 'add') {
            $buttons[] = Admin::button('save', ['type' => 'submit']);
            $buttons[] = Admin::button('back', [
                'href' => Url::admin('system/redirect'),
                'class'     => 'btn-back-to-redirect',
                'data-redirect' => 'admin_table_seo_redirect_list',
            ]);
        }

        if($view == 'edit') {
            $buttons[] = Admin::button('save');
            $buttons[] = Admin::button('add', ['href' => Url::admin('system/redirect/add'), 'text' => '', 'tooltip' => trans('button.add')]);
            $buttons[] = Admin::button('back', ['href' => Url::admin('system/redirect'), 'text' => '', 'tooltip' => trans('button.back')]);
        }

        $buttons = apply_filters('seo_redirect_form_buttons', $buttons);

        Admin::view('include/form/form-action', ['buttons' => $buttons, 'module' => $module]);
    }
}
add_filter('table_seo_redirect_header_buttons', 'AdminSeoRedirectButton::tableHeaderButton');
add_filter('table_seo_redirect_bulk_action_buttons', 'AdminSeoRedirectButton::bulkAction', 30);
add_action('form_seo_redirect_action_button', 'AdminSeoRedirectButton::formButton');