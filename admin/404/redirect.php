<?php
Class Seo_Redirect {
    static public function get($args = []) {
        $model = get_model()->settable('redirect');
        if(is_numeric($args)) $args = array( 'where' => array('id' => (int)$args));
        if(!have_posts($args)) $args = [];
        $args = array_merge( array('where' => [], 'params' => [] ), $args );
        $redirect = $model->get_data($args);
        return apply_filters('get_redirect', $redirect, $args);
    }

    static public function getBy( $field, $value, $params = [] ) {
        $field = Str::clear( $field );
        $value = Str::clear( $value );
        $args = array( 'where' => array( $field => $value));
        if(have_posts($params)) $arg['params'] = $params;
        return apply_filters('get_redirect_by', static::get($args), $field, $value );
    }

    static public function gets($args = []) {
        $model 	= get_model()->settable('redirect')->settable_metabox('metabox');
        if(!have_posts($args)) $args = [];
        $args = array_merge(['where' => [], 'params' => []], $args );
        $redirect = $model->gets_data($args);
        return apply_filters( 'gets_redirect', $redirect, $args );
    }

    static public function getsBy( $field, $value, $params = [] ) {
        $field = Str::clear( $field );
        $value = Str::clear( $value );
        $args = ['where' => array( $field => $value )];
        if( have_posts($params) ) $arg['params'] = $params;
        return apply_filters( 'gets_redirect_by', static::gets($args), $field, $value );
    }

    static public function count( $args = [] ) {
        if( is_numeric($args) ) $args = array( 'where' => array('id' => (int)$args));
        if( !have_posts($args) ) $args = [];
        $args = array_merge( array('where' => [], 'params' => [] ), $args );
        $model = get_model()->settable('redirect')->settable_metabox('redirect_metadata');
        $redirect = $model->count_data($args);
        return apply_filters('count_redirect', $redirect, $args );
    }

    public static function insert($redirect = array()) {

        $model = get_model('home')->settable('redirect');

        if (!empty( $redirect['id'])) {
            $id             = (int) $redirect['id'];
            $update         = true;
            $old_redirect   = static::get($id);
            if (!$old_redirect) return new SKD_Error( 'invalid_redirect_id', __('ID redirect không chính xác.'));
            $redirect['path']   = (isset($redirect['path'])) ? $redirect['path'] : $old_redirect->path;
            $redirect['to']     = (isset($redirect['to'])) ? $redirect['to'] : $old_redirect->to;
            $redirect['type']   = (isset($redirect['type'])) ? $redirect['type'] : $old_redirect->type;
            $redirect['redirect']   = (isset($redirect['redirect'])) ? $redirect['redirect'] : $old_redirect->redirect;

        } else {
            $update = false;
        }

        $path       =  (!empty($redirect['path'])) ? Str::clear($redirect['path']) : '';
        $to         =  (!empty($redirect['to'])) ? Str::clear($redirect['to']) : 0;
        $type       =  (!empty($redirect['type'])) ? Str::clear($redirect['type']) : '301';
        $redirect   =  (!empty($redirect['redirect'])) ? Str::clear($redirect['redirect']) : 0;
        $data = compact('path', 'to', 'type', 'redirect');

        if ($update) {
            $model->settable('redirect')->update_where( $data, compact('id' ));
            $redirect_id = (int) $id;
        }
        else {
            $redirect_id = $model->settable('redirect')->add( $data );
        }

        return $redirect_id;
    }

    static public function delete( $redirectID = 0) {
        $ci =& get_instance();
        $redirectID = (int)Str::clear($redirectID);
        if( $redirectID == 0 ) return false;
        $model = get_model('home')->settable('redirect');
        $redirect  = static::get( $redirectID );
        if(have_posts($redirect) ) {
            $ci->data['module']   = 'redirect';
            do_action('delete_redirect', $redirectID );
            if($model->delete_where(['id'=> $redirectID])) {
                do_action('delete_redirect_success', $redirectID );
                return [$redirectID];
            }
        }

        return false;
    }

    static public function deleteList( $redirectID = []) {
        if(have_posts($redirectID)) {
            $model      = get_model('home')->settable('redirect');
            if($model->delete_where_in(['field' => 'id', 'data' => $redirectID])) {
                do_action('delete_redirect_list_trash_success', $redirectID );
                return $redirectID;
            }
        }
        return false;
    }
}

Class Seo_Redirect_Admin {

    public function __construct() {
        AdminMenu::addSub('system', 'redirect', '404 Redirect', 'plugins?page=redirect', ['callback' => 'Seo_Redirect_Admin::page','position' => 'system']);
    }

    public static function page() {

        $view = InputBuilder::get('view');

        if(empty($view)) {
            $limit = 20;

            $args = [];

            $total = Seo_Redirect::count($args);

            $url = Url::admin('plugins?page=redirect&paging={paging}');

            $pagination = pagination($total, $url, $limit);

            $args['params'] = array(
                'limit' => $limit,
                'start' => $pagination->getoffset(),
                'orderby' => 'order, created desc',
            );

            $tableConfig = array(
                'items' => Seo_Redirect::gets($args),
                'table' => 'redirect',
                'model' => get_model(),
                'module'=> 'redirect',
            );

            $table_list = new Seo_Redirect_Table($tableConfig);

            include SKD_SEO_PATH.'admin/404/views/page-index.php';
        }
        else if($view == 'add') {
            $form = Seo_Redirect_Admin::field();
            include SKD_SEO_PATH.'admin/404/views/page-save.php';
        }
        else if($view == 'edit') {
            $form = Seo_Redirect_Admin::field();
            $id     = (int)InputBuilder::get('id');
            $object = Seo_Redirect::get($id);
            if(have_posts($object)) {
                $languages = [];
                if(Language::hasMulti()) {
                    $model = get_model('home')->settable('language');
                    $languages = $model->gets_where(array('object_id' => $object->id, 'object_type' => 'redirect'));
                    foreach ($languages as $key => $lang) {
                        $object->lang[$lang->language]['name']      = $lang->name;
                        $object->lang[$lang->language]['excerpt']   = $lang->excerpt;
                    }
                }
                $form_field = $form['field'];
                foreach($form_field as $key => $field) {
                    //gán giá trị cho các field bình thường
                    if( isset($object->{$field['field']}) ) {
                        $form_field[$key]['value'] = $object->{$field['field']};
                    }
                    //gán giá trị cho các field đa ngôn ngữ
                    else if( isset($field['lang']) ) {
                        $temp = str_replace($field['lang'].'[', '',$field['field']);
                        $temp = str_replace(']', '',$temp);
                        if( have_posts($languages) ) {
                            foreach ($languages as $k => $value) {
                                if($field['lang'] == $value->language ) {
                                    if(isset($value->$temp))  {
                                        $form_field[$key]['value'] = $value->$temp;
                                        break;
                                    }
                                }
                                else if(isset($object->$temp)) {
                                    $form_field[$key]['value'] = $object->$temp;
                                }
                            }
                        } else if(isset($object->$temp)) {
                            $form_field[$key]['value'] = $object->$temp;
                        }
                    }
                }
                $form['field'] = $form_field;
                include SKD_SEO_PATH.'admin/404/views/page-save.php';
            }
        }
    }

    public static function button() {
        if(Template::isClass('plugins')) {
            $page = InputBuilder::get('page');
            if($page == 'redirect') {
                echo '<div class="pull-left"></div>';
                echo '<div class="pull-right">';
                switch (InputBuilder::get('view')) {
                    case 'edit':
                    case 'add':
                        echo '<button name="save" class="btn-icon btn-green" form="js_redirect_form_save">'.Admin::icon('save').' Lưu</button>';
                        echo '<a href="'.Url::admin('plugins?page=redirect').'" class="btn-icon btn-blue">'.Admin::icon('back').' Quay lại</a>';
                        break;
                    default:
                        echo '<a href="'.Url::admin('plugins?page=redirect&view=add').'" class="btn-icon btn-green">'.Admin::icon('add').' Thêm Mới</a>';
                        break;
                }
                echo '</div>';
            }
        }
    }

    public static function field() {
        $form['leftt'] 	= [];
        $form['leftb'] 	= ['add' => 'Thông tin'];
        $form['lang'] 	= [];
        $form['right'] 	= [];
        $form['field']['path'] = array('group' => 'add', 'field' => 'path', 'label' => 'Url chuyển hướng', 'type' => 'text', 'note' => 'Không bao gồm tên miền');
        $form['field']['to'] = array('group' => 'add', 'field' => 'to', 'label' => 'Url đích', 'type' => 'url', 'note' => 'Để trống sẽ tự động lấy từ cấu hình seo');
        return $form;
    }

    public static function save( $ci, $model ) {

        $result['status']  = 'error';

        $result['message'] = __('Lưu dữ liệu không thành công');

        if(InputBuilder::post()) {

            $id = (int)InputBuilder::post('id');

            $redirect = Seo_Redirect::get($id);

            if(!have_posts($redirect)) {
                $result['message'] = __('Dữ liệu lưu không còn tồn tại');
                echo json_encode($result);
                return true;
            }

            $redirectUp = [
                'id'        => $redirect->id,
                'to'        => InputBuilder::post('redirect_to'),
                'redirect'  => (int)InputBuilder::post('redirect'),
            ];

            $error = Seo_Redirect::insert($redirectUp);

            if(is_skd_error($error) ) {
                $result['status']  = 'error';
                foreach ($error->errors as $key => $er) {
                    $result['message'] = $er;
                }
            }
            else {
                $redirect->to = $redirectUp['to'];
                $redirect->redirect = $redirectUp['redirect'];
                $result['item']     = $redirect;
                $result['status']   = 'success';
                $result['message']  = __('Lưu dữ liệu thành công.');
            }
        }

        echo json_encode($result);

        return true;
    }
}

add_action( 'action_bar_before', 'Seo_Redirect_Admin::button', 10 );
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
    function column_default( $item, $column_name ) {
        do_action( 'manage_Seo_Redirect_custom_column', $column_name, $item );
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
        echo '<button class="btn-red btn delete" data-id="'.$item->id.'" data-table="'.$table.'">'.Admin::icon('delete').'</button>';
        echo "</td>";
    }
    function search_right() {}
}

if(!function_exists('admin_ajax_redirect_save')) {

    function admin_ajax_redirect_save( $ci, $model ) {

        $result['status']  = 'error';

        $result['message'] = __('Lưu dữ liệu không thành công');

        if(InputBuilder::post()) {

            $data = InputBuilder::post();

            if(empty($data['path'])) {
                $result['message'] = __('Không được để trống Url chuyển hướng');
                echo json_encode($result);
                return true;
            }

            $error = Seo_Redirect::insert($data);

            if(is_skd_error($error) ) {
                $result['status']  = 'error';
                foreach ($error->errors as $key => $er) {
                    $result['message'] = $er;
                }
            }
            else {
                $result['status']  = 'success';
                $result['message'] = __('Lưu dữ liệu thành công.');
            }
        }

        echo json_encode($result);

        return true;
    }

    Ajax::admin('admin_ajax_redirect_save');
}

if(!function_exists('admin_action_redirect_delete')) {
    function admin_action_redirect_delete($res, $table, $id) {
        if(is_numeric($id)) {
            $res = Seo_Redirect::delete($id);
        }
        else if(have_posts($id)) {
            $res = Seo_Redirect::deleteList($id);
        }
        return $res;
    }

    add_filter('delete_object_redirect', 'admin_action_redirect_delete', 1, 3 );
}

new Seo_Redirect_Admin();