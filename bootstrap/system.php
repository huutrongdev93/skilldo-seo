<?php

use SkdSeo\Modules\System\AdminSystem;
use SkillDo\Cms\Support\Admin;

/*
|--------------------------------------------------------------------------
| admin_system_tabs
|--------------------------------------------------------------------------
| Đăng ký mục cấu hình seo vào cấu hình hệ thống
*/
add_filter('admin_system_tabs', [AdminSystem::class, 'register'], 50);
/*
|--------------------------------------------------------------------------
| admin_system_seo_html
|--------------------------------------------------------------------------
| renderGeneral: Đăng ký mục cấu hình chung vào cấu hình seo
| renderProduct: Đăng ký mục cấu hình seo cho trang sản phẩm vào cấu hình seo
| renderScript: Đăng ký mục điền script headerr, body, foooter vào cấu hình seo
| renderRobots: Đăng ký mục điền robots vào cấu hình seo
| renderPoint: Đăng ký mục cấu hình chấm điểm seo vào cấu hình seo
| renderRedirect: Đăng ký mục cấu hình chuyển hướng cho link 404 vào cấu hình seo
*/
add_action('admin_system_seo_html',[AdminSystem::class, 'renderGeneral'], 10);
add_action('admin_system_seo_html',[AdminSystem::class, 'renderProduct'], 15);
add_action('admin_system_seo_html',[AdminSystem::class, 'renderScript'], 20);
add_action('admin_system_seo_html',[AdminSystem::class, 'renderSchema'], 30);
add_action('admin_system_seo_html',[AdminSystem::class, 'renderRobots'], 40);

if(Admin::isRoot())
{
    add_action('admin_system_seo_html',[AdminSystem::class, 'renderPoint'], 40);
}
add_action('admin_system_seo_html',[AdminSystem::class, 'renderRedirect'], 50);

/*
|--------------------------------------------------------------------------
| admin_system_seo_save
|--------------------------------------------------------------------------
| Đăng ký method lưu lại các thông tin seo
*/
add_action('admin_system_seo_save',[AdminSystem::class, 'save']);