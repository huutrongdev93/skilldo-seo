<?php
namespace SkdSeo\Modules\Redirect;

use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Url;
use SkillDo\Cms\Table\Columns\ColumnText;
use SkillDo\Cms\Table\Columns\ColumnView;
use SkillDo\Database\Eloquent\Builder;
use SkillDo\Cms\Form\Form;
use SkillDo\Http\Request;

class Table extends \SkillDo\Cms\Table\SKDObjectTable
{
    protected string $module = 'seo_redirect';

    protected string $table = 'redirect';

    protected mixed $model = \SkdSeo\Models\Redirect::class;

    protected bool $trash = false;

    function getColumns()
    {
        $this->_column_headers = [
            'cb'        => 'cb',
            'path'     => [
                'label' => '404 Path',
                'column' => fn ($item, $args) => ColumnView::make('path', $item, $args)->html(function ($column) {
                    echo '<b style="color:red">'.$column->item->path.'</b>';
                })
            ],
            'redirect'     => [
                'label' => 'Chuyển hướng',
                'column' => fn ($item, $args) => ColumnView::make('redirect', $item, $args)->html(function ($column) {
                    echo (($column->item->redirect == 0) ? Admin::badge('success', 'Bật') : Admin::badge('error', 'Tắt'));
                })
            ],
            'to'     => [
                'label' => 'Chuyển Đến',
                'column' => fn ($item, $args) => ColumnView::make('to', $item, $args)->html(function ($column) {
                    echo (!empty($column->item->to)) ? $column->item->to : 'Mặc định' ;
                })
            ],
            'created' => [
                'label'  => trans('table.created'),
                'column' => fn ($item, $args) => ColumnText::make('created', $item, $args)->datetime()
            ],
        ];

        $this->_column_headers = apply_filters( "manage_".$this->module."_columns", $this->_column_headers );

        $this->_column_headers['action'] = trans('table.action');

        return apply_filters( "manage_".$this->module."_columns_full", $this->_column_headers );
    }

    function actionButton($item, $module, $table): array
    {
        $listButton = [];

        $listButton[] = Admin::button('blue', [
            'class'   => 'js_redirect_btn__edit',
            'data-id' => $item->id,
            'data-item' => htmlentities(json_encode($item->toObject())),
            'icon'    => Admin::icon('edit')
        ]);
        $listButton[] = Admin::btnDelete([
            'id' => $item->id,
            'module' => $this->module,
            'model' => $this->model,
            'description' => trans('message.page.confirmDelete')
        ]);
        /**
         * @since 7.0.0
         */
        return apply_filters('admin_'.$this->module.'_table_columns_action', $listButton);
    }

    function queryDisplay(Builder $query, \SkillDo\Http\Request $request, $data = []): Builder
    {
        $query = parent::queryDisplay($query, $request, $data);

        $query->orderBy('created', 'desc');

        return $query;
    }

    function headerFilter(Form $form, Request $request)
    {
        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_'.$this->module.'_table_form_filter', $form);
    }

    function headerSearch(Form $form, Request $request): Form
    {

        $form->text('keyword', ['placeholder' => trans('table.search.keyword').'...'], $request->input('keyword'));

        /**
         * @singe v7.0.0
         */
        return apply_filters('admin_'.$this->module.'_table_form_search', $form);
    }

    function headerButton(): array
    {
        $buttons[] = Admin::button('add', ['href' => Url::admin('system/redirect/add')]);

        $buttons[] = Admin::button('reload');

        return $buttons;
    }
}