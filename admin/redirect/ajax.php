<?php
use JetBrains\PhpStorm\NoReturn;
use SkillDo\Http\Request;

Class AjaxAdminSeoRedirect {
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
            $args = apply_filters('admin_seo_redirect_controllers_index_args_before_count', $args);

            if(!is_numeric($recordsTotal)) {
                $recordsTotal = apply_filters('admin_seo_redirect_controllers_index_count', Seo_Redirect::count($args), $args);
            }


            # [List data]
            $args->limit($limit)
                ->offset(($page - 1)*$limit)
                ->orderBy('order')
                ->orderBy('created', 'desc');

            $args = apply_filters('admin_seo_redirect_controllers_index_args', $args);

            $objects = apply_filters('admin_seo_redirect_controllers_index_objects', Seo_Redirect::gets($args), $args);

            $args = [
                'items' => $objects,
                'table' => 'redirect',
                'model' => model('redirect'),
                'module'=> 'seo_redirect',
            ];

            $table = new Seo_Redirect_Table($args);
            $table->get_columns();
            ob_start();
            $table->display_rows_or_message();
            $html = ob_get_contents();
            ob_end_clean();

            /**
             * Bulk Actions
             * @hook table_*_bulk_action_buttons Hook mới phiên bản 7.0.0
             */
            $buttonsBulkAction = apply_filters('table_seo_redirect_bulk_action_buttons', []);

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
    static function save(Request $request, $model): true
    {
        if($request->isMethod('post')) {

            $id = (int)$request->input('id');

            $redirectUp = [];

            $redirect = Seo_Redirect::get($id);

            if(!have_posts($redirect)) {
                response()->error(trans('Dữ liệu không tồn tại'));
            }

            $redirectUp['id'] = $redirect->id;

            $redirectUp['redirect'] = (int)$request->input('redirect');

            $redirectUp['to'] = $request->input('redirect_to');

            if(empty($redirectUp['to'])) {
                response()->error(trans('Không được để trống Url chuyển hướng'));
            }

            if(!Url::is($redirectUp['to'])) {
                response()->error(trans('Url chuyển hướng phải là url'));
            }

            $error = Seo_Redirect::insert($redirectUp);

            if(is_skd_error($error) ) {
                response()->error($error);
            }

            $redirect = Seo_Redirect::get($error);

            Seo_Redirect_Helper::build($redirect);

            response()->success(trans('ajax.save.success'), $redirect);
        }

        response()->error(trans('ajax.save.error'));
    }
}

Ajax::admin('AjaxAdminSeoRedirect::load');
Ajax::admin('AjaxAdminSeoRedirect::save');