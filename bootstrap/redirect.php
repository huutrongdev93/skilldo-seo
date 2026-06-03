<?php
use SkdSeo\Modules\Redirect\AdminRedirect;
use SkdSeo\Modules\Redirect\Form;
/*
|--------------------------------------------------------------------------
| admin_system_tabs
|--------------------------------------------------------------------------
| Đăng ký mục chuyển hướng vào cấu hình hệ thống
*/
add_filter('admin_system_tabs', [AdminRedirect::class, 'register'], 50);

/*
|--------------------------------------------------------------------------
| admin_form_seo_redirect_action_button
|--------------------------------------------------------------------------
| Đăng danh sách input vào form add và edit
*/
add_filter('manage_seo_redirect_input', [Form::class, 'fields']);

/*
|--------------------------------------------------------------------------
| admin_form_seo_redirect_action_button
|--------------------------------------------------------------------------
| Thêm các nút button vào form add và edit
*/
add_filter('admin_form_seo_redirect_action_button', [Form::class, 'buttons']);

/*
|--------------------------------------------------------------------------
| save_seo_redirect_object_before
|--------------------------------------------------------------------------
| Xử lý dữ liệu trước khi lưu
*/
add_filter('save_seo_redirect_object_before', [AdminRedirect::class, 'save'], 10);

/*
|--------------------------------------------------------------------------
| save_seo_redirect_object
|--------------------------------------------------------------------------
| Hành dộng diễn ra sau khi lưu
*/
add_action('save_seo_redirect_object', [AdminRedirect::class, 'afterSave'], 10, 2);

/*
|--------------------------------------------------------------------------
| ajax_delete_after_success
|--------------------------------------------------------------------------
| Hành dộng diễn ra sau khi xóa
*/
add_action('ajax_delete_seo_redirect_after_success', [AdminRedirect::class, 'afterDelete'], 10, 2);