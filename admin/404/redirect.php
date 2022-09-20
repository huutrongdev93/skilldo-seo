<?php
Class Seo_Redirect extends Model {

    static string $table = 'redirect';

    public static function insert($insertData = array()) {

        $columnsTable = [
            'path'          => ['string'],
            'to'            => ['string'],
            'type'          => ['string', '301'],
            'redirect'      => ['string', 0],
        ];

        $columnsTable = apply_filters('columns_db_'.self::$table, $columnsTable);

        $update = false;

        if (!empty($insertData['id'])) {
            $id          = (int) $insertData['id'];
            $update      = true;
            $oldObject   = static::get($id);
            if (!$oldObject) return new SKD_Error('invalid_id', __('ID redirect không chính xác.'));

        }

        $insertData = createdDataInsert($columnsTable, $insertData, (isset($oldObject)) ? $oldObject : null);

        foreach ($columnsTable as $columnsKey => $columnsValue ) {
            ${$columnsKey}  = $insertData[$columnsKey];
        }

        $data = compact(array_keys($columnsTable));

        $model = model(self::$table);

        if ($update) {
            $model->update($data, Qr::set($id));
            $redirect_id = (int) $id;
        }
        else {
            $redirect_id = $model->add($data);
        }

        return $redirect_id;
    }

    static public function delete($redirectID = 0) {
        $ci =& get_instance();
        $redirectID = (int)Str::clear($redirectID);
        if($redirectID == 0) return false;
        $model = model(self::$table);
        $redirect  = static::get($redirectID);
        if(have_posts($redirect)) {
            $ci->data['module']  = self::$table;
            do_action('delete_redirect', $redirectID );
            if($model->delete(Qr::set('id', $redirectID))) {
                do_action('delete_redirect_success', $redirectID);
                return [$redirectID];
            }
        }

        return false;
    }

    static public function deleteList($redirectID = [])   {
        if(have_posts($redirectID)) {
            if(model(self::$table)->delete(Qr::set()->whereIn('id', $redirectID))) {
                do_action('delete_redirect_list_trash_success', $redirectID );
                return $redirectID;
            }
        }
        return false;
    }
}

Class Seo_Redirect_Admin {

    static function register($tabs) {
        $tabs['redirect'] = [
            'label' => '404 Redirect',
            'description' => 'Quản lý log chuyển hướng link 404',
            'callback' => 'Seo_Redirect_Admin::render',
            'icon' => '<i class="fa-light fa-diamond-turn-right"></i>',
            'form' => false,
        ];
        return $tabs;
    }

    public static function render() {
        $view = Request::get('view');
        if(empty($view)) {

            $limit = 20;

            $args = Qr::set();

            $total = Seo_Redirect::count($args);

            $url = Url::admin('system/redirect?paging={paging}');

            $pagination = pagination($total, $url, $limit);

            $args->limit($limit)->offset($pagination->offset())->orderByDesc('created');

            $tableConfig = array(
                'items' => Seo_Redirect::gets($args),
                'table' => 'redirect',
                'model' => model('redirect'),
                'module'=> 'redirect',
            );

            $table_list = new Seo_Redirect_Table($tableConfig);

            include SKD_SEO_PATH.'admin/404/views/page-index.php';
        }
        else if($view == 'add') {
            Admin::creatForm('redirect');
            include SKD_SEO_PATH.'admin/404/views/page-save.php';
        }
        else if($view == 'edit') {
            $id     = (int)Request::get('id');
            $object = Seo_Redirect::get($id);
            if(have_posts($object)) {
                Admin::creatForm('redirect', $object);
                include SKD_SEO_PATH.'admin/404/views/page-save.php';
            }
        }
    }

    public static function button() {
        $page = Url::segment(3);
        if($page == 'redirect') {
            switch (Request::get('view')) {
                case 'edit':
                case 'add':
                    echo '<button name="save" class="btn-icon btn-green" form="js_redirect_form_save">'.Admin::icon('save').' Lưu</button>';
                    echo '<a href="'.Url::admin('system/redirect').'" class="btn-icon btn-blue">'.Admin::icon('back').' Quay lại</a>';
                    break;
                default:
                    echo '<a href="'.Url::admin('system/redirect?view=add').'" class="btn-icon btn-green">'.Admin::icon('add').' Thêm Mới</a>';
                    break;
            }
        }
    }

    public static function form($form) {

        $form->leftBottom
            ->addGroup('add', 'Thông tin')
            ->addField('path', 'text', ['label' => 'Url chuyển hướng', 'note' => 'Không bao gồm tên miền'])
            ->addField('to', 'text', ['label' => 'Url đích', 'note' => 'Để trống sẽ tự động lấy từ cấu hình seo']);

        return $form;
    }

    public static function save($ci, $model) {

        $result['status']  = 'error';

        $result['message'] = __('Lưu dữ liệu không thành công');

        if(Request::post()) {

            $id = (int)Request::post('id');

            $redirectUp = [];

            if(!empty($id)) {
                $redirect = Seo_Redirect::get($id);
                if(!have_posts($redirect)) {
                    $result['message'] = __('Dữ liệu lưu không còn tồn tại');
                    echo json_encode($result);
                    return true;
                }
                $redirectUp['id'] = $redirect->id;
                $redirectUp['path'] = $redirect->path;
                $redirectUp['redirect'] = (int)Request::post('redirect');
                $redirectUp['to'] = (int)Request::post('redirect_to');
            }
            else {
                $redirectUp['path'] = Request::post('path');
                $redirectUp['to'] = Request::post('to');
            }

            if(empty($redirectUp['path'])) {
                $result['message'] = __('Không được để trống Url chuyển hướng');
                echo json_encode($result);
                return true;
            }

            $error = Seo_Redirect::insert($redirectUp);

            if(is_skd_error($error) ) {
                $result['status']  = 'error';
                foreach ($error->errors as $key => $er) {
                    $result['message'] = $er;
                }
            }
            else {
                $redirect = Seo_Redirect::get($error);
                $result['item']     = $redirect;
                $result['status']   = 'success';
                $result['message']  = __('Lưu dữ liệu thành công.');
            }
        }

        echo json_encode($result);

        return true;
    }
}
add_filter('skd_system_tab', 'Seo_Redirect_Admin::register', 50);
add_action('action_bar_system_right', 'Seo_Redirect_Admin::button', 10 );
add_filter('manage_redirect_input', 'Seo_Redirect_Admin::form');
Ajax::admin('Seo_Redirect_Admin::save');

class Seo_Redirect_Table extends skd_object_list_table {
    function get_columns() {
        $this->_column_headers = [];
        $this->_column_headers['cb']       = 'cb';
        $this->_column_headers['path']     = '404 Path';
        $this->_column_headers['redirect'] = 'Chuyển hướng';
        $this->_column_headers['to']       = 'Chuyển Đến';
        $this->_column_headers['created']  = 'Ngày';
        $this->_column_headers['action']   = 'Hành động';
        return apply_filters( "manage_Seo_Redirect_columns", $this->_column_headers );
    }
    function column_default($column_name, $item, $global) {
        do_action( 'manage_Seo_Redirect_custom_column', $column_name, $item, $global );
    }
    function column_path($item, $column_name, $module, $table) {
        echo '<b style="color:red">'.$item->path.'</b>';
    }
    function column_redirect($item, $column_name, $module, $table) {
        echo ($item->redirect == 0) ? 'Mặc định' : (($item->redirect == 1) ? 'Bật' : 'Tắt');
    }
    function column_to($item, $column_name, $module, $table) {
        echo (!empty($item->to)) ? $item->to : 'Mặc định' ;
    }
    function _column_action($item, $column_name, $module, $table, $class) {
        $class .= ' text-center';
        echo '<td class="'.$class.'">';
        echo '<a href="#" class="btn-blue btn js_redirect_btn__edit" data-id="'.$item->id.'" data-item="'.htmlentities(json_encode($item)).'">'.Admin::icon('edit').'</a>';
        echo Admin::btnDelete(['trash' => 'disable', 'id' => $item->id, 'module' => 'Seo_Redirect', 'des' => 'Bạn chắc chắn muốn xóa chuyển hướng này ?']);
        echo "</td>";
    }
    function search_right() {}
}