<?php

use SkillDo\Form\Form;
use SkillDo\Http\Request;
class Log404_Table extends \SkillDo\Table\SKDObjectTable {
    function get_columns() {
        $this->_column_headers = [];
        $this->_column_headers['cb']       = 'cb';
        $this->_column_headers['path']     = 'Đường dẫn 404';
        $this->_column_headers['ip']       = 'Ip lần cuối';
        $this->_column_headers['update']   = 'Truy cập lần cuối';
        $this->_column_headers['hit']      = 'Hits';
        $this->_column_headers['to']       = 'Chuyển hướng';
        $this->_column_headers['created']  = 'Ngày';
        $this->_column_headers['action']   = 'Hành động';
        return apply_filters( "manage_log404_columns", $this->_column_headers );
    }
    function column_path($item, $column_name, $module, $table): void
    {
        echo '<b style="color:red">'.$item->path.'</b>';
    }
    function column_ip($item, $column_name, $module, $table): void
    {
        echo $item->ip;
    }
    function column_update($item, $column_name, $module, $table): void
    {
        if(empty($item->updated)) {
            $item->updated = $item->created;
        }
        echo date('d/m/Y H:i', strtotime($item->updated));
    }
    function column_hit($item, $column_name, $module, $table): void
    {
        echo $item->hit;
    }
    function column_to($item, $column_name, $module, $table): void
    {
        echo ($item->redirect == 0) ? 'Mặc định' : $item->to;
    }
    function actionButton($item, $module, $table): array
    {
        $listButton = [];

        $listButton[] = Admin::button('blue', [
            'class'   => 'js_redirect_btn__edit',
            'data-id' => $item->id,
            'data-item' => htmlentities(json_encode($item)),
            'icon'    => Admin::icon('edit')
        ]);
        $listButton[] = Admin::btnDelete([
            'trash'         => 'disable',
            'id'            => $item->id,
            'model'         => 'log404',
            'description'   => trans('message.page.confirmDelete')
        ]);
        /**
         * @since 7.0.0
         */
        return apply_filters('admin_log404_table_columns_action', $listButton);
    }
    function headerFilter(Form $form, Request $request)
    {
        $formFilter = form();
        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_log404_table_form_filter', $formFilter);
    }
    function headerSearch(Form $form, Request $request): Form
    {
        $form->text('keyword', ['placeholder' => trans('table.search.keyword').'...'], $request->input('keyword'));

        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_log404_table_form_search', $form);
    }
}