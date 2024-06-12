<?php
use JetBrains\PhpStorm\NoReturn;
use SkillDo\Http\Request;

Class AjaxAdminLog404 {
    #[NoReturn]
    static function load(Request $request): void
    {
        if($request->isMethod('post')) {

            $page    = $request->input('page');

            $page   = (is_null($page) || empty($page)) ? 1 : (int)$page;

            $limit  = $request->input('limit');

            $limit   = (is_null($limit) || empty($limit)) ? 10 : (int)$limit;

            $keyword = $request->input('keyword');

            $recordsTotal   = $request->input('recordsTotal');

            $args = Qr::set();

            if (!empty($keyword)) {
                $args->where('title', 'like', '%' . $keyword . '%');
            }
            /**
             * @since 7.0.0
             */
            $args = apply_filters('admin_log404_controllers_index_args_before_count', $args);

            if(!is_numeric($recordsTotal)) {
                $recordsTotal = apply_filters('admin_log404_controllers_index_count', Log404::count($args), $args);
            }


            # [List data]
            $args->limit($limit)
                ->offset(($page - 1)*$limit)
                ->orderBy('created', 'desc');

            $args = apply_filters('admin_log404_controllers_index_args', $args);

            $objects = apply_filters('admin_log404_controllers_index_objects', Log404::gets($args), $args);

            $args = [
                'items' => $objects,
                'table' => 'redirect',
                'model' => model('redirect'),
                'module'=> 'log404',
            ];

            $table = new Log404_Table($args);
            $table->get_columns();
            ob_start();
            $table->display_rows_or_message();
            $html = ob_get_contents();
            ob_end_clean();

            /**
             * Bulk Actions
             * @hook table_*_bulk_action_buttons Hook mới phiên bản 7.0.0
             */
            $buttonsBulkAction = apply_filters('table_log404_bulk_action_buttons', []);

            $bulkAction = Admin::partial('include/table/header/bulk-action-buttons', [
                'actionList' => $buttonsBulkAction
            ]);

            $result['data'] = [
                'html'          => base64_encode($html),
                'bulkAction'    => base64_encode($bulkAction),
            ];
            $result['pagination']   = [
                'limit' => $limit,
                'total' => $recordsTotal,
                'page'  => (int)$page,
            ];

            response()->success(trans('ajax.load.success'), $result);
        }

        response()->error(trans('ajax.load.error'));
    }

    #[NoReturn]
    static function save(Request $request, $model): void
    {
        if($request->isMethod('post')) {

            $id = (int)$request->input('id');

            $redirectUp = [];

            $redirect = Log404::get($id);

            if(!have_posts($redirect)) {
                response()->error(trans('Dữ liệu không tồn tại'));
            }

            $redirectUp['id'] = $redirect->id;

            $redirectUp['redirect'] = (int)$request->input('redirect');

            $redirectUp['to'] = $request->input('redirect_to');

            if(!empty($redirectUp['redirect']) && empty($redirectUp['to'])) {
                response()->error(trans('Không được để trống Url chuyển hướng'));
            }

            if(!empty($redirectUp['redirect'])) {

                if(empty($redirectUp['to'])) {
                    response()->error(trans('Không được để trống Url chuyển hướng'));
                }

                if(!Url::is($redirectUp['to'])) {
                    response()->error(trans('Url chuyển hướng phải là url'));
                }
            }

            $error = Log404::insert($redirectUp, $redirect);

            if(is_skd_error($error) ) {
                response()->error($error);
            }

            $redirect = Seo_Redirect::get($error);

            response()->success(trans('ajax.save.success'), $redirect);
        }

        response()->error(trans('ajax.save.error'));
    }
}

Ajax::admin('AjaxAdminLog404::load');
Ajax::admin('AjaxAdminLog404::save');