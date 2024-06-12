<?php
class AdminLog404Button {
    /**
     * Thêm buttons action cho header của table
     * @param $buttons
     * @return array
     */
    static function tableHeaderButton($buttons): array
    {
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

        $buttons = apply_filters('seo_redirect_form_buttons', $buttons);

        Admin::view('include/form/form-action', ['buttons' => $buttons, 'module' => $module]);
    }
}
add_filter('table_log404_header_buttons', 'AdminLog404Button::tableHeaderButton');
add_filter('table_log404_bulk_action_buttons', 'AdminLog404Button::bulkAction', 30);
add_action('form_log404_action_button', 'AdminLog404Button::formButton');