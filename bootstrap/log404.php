<?php
use SkdSeo\Modules\Log404\AdminLog404;
use SkdSeo\Modules\Log404\Form;
use SkdSeo\Modules\Log404\Log404;

/*
|--------------------------------------------------------------------------
| admin_system_tabs
|--------------------------------------------------------------------------
| Đăng ký mục chuyển hướng vào cấu hình hệ thống
*/
add_filter('admin_system_tabs', [AdminLog404::class, 'register'], 50);

/*
|--------------------------------------------------------------------------
| manage_log404_input
|--------------------------------------------------------------------------
| Đăng danh sách input vào form add và edit
*/
add_filter('manage_log404_input', [Form::class, 'fields']);

/*
|--------------------------------------------------------------------------
| admin_form_log404_action_button
|--------------------------------------------------------------------------
| Thêm các nút button vào form add và edit
*/
add_filter('admin_form_log404_action_button', [Form::class, 'buttons']);

/*
|--------------------------------------------------------------------------
| save_log404_object_before
|--------------------------------------------------------------------------
| Xử lý dữ liệu trước khi lưu
*/
add_filter('save_log404_object_before', [AdminLog404::class, 'save'], 10);

/*
|--------------------------------------------------------------------------
| template_redirect
|--------------------------------------------------------------------------
| Xử lý việc lưu lại các response 404 và chuyển hướng
*/
add_action('template_redirect', [Log404::class, 'handle']);

