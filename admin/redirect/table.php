<?php

use SkillDo\Form\Form;
use SkillDo\Http\Request;
class Seo_Redirect_Table extends \SkillDo\Table\SKDObjectTable {
    function get_columns() {
        $this->_column_headers = [];
        $this->_column_headers['cb']       = 'cb';
        $this->_column_headers['path']     = '404 Path';
        $this->_column_headers['redirect'] = 'Chuyển hướng';
        $this->_column_headers['to']       = 'Chuyển Đến';
        $this->_column_headers['created']  = 'Ngày';
        $this->_column_headers['action']   = 'Hành động';
        return apply_filters( "manage_seo_redirect_columns", $this->_column_headers );
    }
    function column_default($column_name, $item, $global): void
    {
        do_action( 'manage_seo_redirect_custom_column', $column_name, $item, $global );
    }
    function column_path($item, $column_name, $module, $table): void
    {
        echo '<b style="color:red">'.$item->path.'</b>';
    }
    function column_redirect($item, $column_name, $module, $table): void
    {
        echo (($item->redirect == 0) ? '<span class="badge text-bg-success">Bật</span>' : '<span class="badge text-bg-danger">Tắt</span>');
    }
    function column_to($item, $column_name, $module, $table): void
    {
        echo (!empty($item->to)) ? $item->to : 'Mặc định' ;
    }

    function column_created($item, $column_name, $module, $table, $class): void
    {
        echo (!empty($item->created)) ? date('d/m/Y H:i', strtotime($item->created)) : '';
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
            'trash' => 'disable',
            'id' => $item->id,
            'module' => 'Seo_Redirect',
            'des' => trans('message.page.confirmDelete')
        ]);
        /**
         * @since 7.0.0
         */
        return apply_filters('admin_seo_redirect_table_columns_action', $listButton);
    }

    function headerFilter(Form $form, Request $request)
    {
        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_seo_redirect_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {

        $form->text('keyword', ['placeholder' => trans('table.search.keyword').'...'], $request->input('keyword'));

        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_seo_redirect_table_form_search', $form);
    }
}