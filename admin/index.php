<?php
class AdminSystemSeo {
    static function seoThemeRegister($tabs) {
        if(!empty($tabs['theme-seo'])) unset($tabs['theme-seo']);
        $tabs['seo'] = [
            'label' => 'Seo',
            'description' => 'Quản lý thông tin hỗ trợ seo website',
            'callback' => 'AdminSystemSeo::render',
            'icon' => '<i class="fal fa-megaphone"></i>'
        ];
        return $tabs;
    }
    static function render($ci, $tab): void {
        do_action('admin_system_seo_html');
    }
    static function renderGeneral($tab): void {
        $form = new FormBuilder();
        $form
            ->add('seo_favicon', 'image', ['label' => 'Favicon'], Option::get('seo_favicon'))
            ->add('general_title', 'text', ['label' => 'Meta title (shop)'], Option::get('general_title'))
            ->add('general_description', 'textarea', ['label' => 'Meta description (Mô tả trang chủ)'], Option::get('general_description'))
            ->add('general_keyword', 'textarea', ['label' => 'Meta keyword (Từ khóa trang chủ)'], Option::get('general_keyword'));
        Admin::partial('function/system/html/default', [
            'title'       => 'Cấu hình chung',
            'description' => 'Quản lý thông tin seo website cơ bản',
            'form'        => $form
        ]);
    }
    static function renderScript($tab): void {
        $form = new FormBuilder();
        $form
            ->add('header_script',  'code', ['label' => 'Script Header', 'language'  => 'javascript'], Option::get('header_script'))
            ->add('body_script',    'code', ['label' => 'Script Body',   'language'  => 'javascript'], Option::get('body_script'))
            ->add('footer_script',  'code', ['label' => 'Script Footer', 'language'  => 'javascript'], Option::get('footer_script'));

        Admin::partial('function/system/html/default', [
            'title'       => 'Script',
            'description' => 'Chèn code seo, code của bên thứ ba vào các vị trí tương ứng (google analytic code, google master code, chat code, thống kê code..)',
            'form'        => $form
        ]);
    }
    static function renderRobots($tab): void {
        $form = new FormBuilder();
        $form
            ->add('skd_seo_robots',  'textarea', ['label' => 'Nội dung file robots'], Option::get('skd_seo_robots'));

        Admin::partial('function/system/html/default', [
            'title'       => 'File Robots',
            'description' => 'Điều hướng các robot tìm kiếm cho phép hoặc không cho phép các công cụ tìm kiếm thu thập dữ liệu',
            'form'        => $form
        ]);
    }
    static function renderPoint($tab): void {
        $form = new FormBuilder();
        $form->add('seo_point',  'select', ['label' => 'Chấm điểm seo', 'options' => [0 => 'không sử dụng', 1 => 'Sử dụng']], Option::get('seo_point'));

        Admin::partial('function/system/html/default', [
            'title'       => 'Chấm điểm seo',
            'description' => 'Quản lý công cụ chấm điểm seo trong bài viết, sản phẩm...',
            'form'        => $form
        ]);
    }
    static function renderRedirect($tab): void {
        $form = new FormBuilder();
        $form
            ->add('seo_404[redirect_to]', 'select', [
                'label' => 'Chuyển hướng đến',
                'options' => [
                    '' => 'Không chuyển hướng', 'home' => 'Trang chủ website', 'link' => 'Url tùy chỉnh'
                ],
                'note' => "<p></p><strong>Không chuyển hướng:</strong> Để tắt chuyển hướng.</p><p></p><strong>Trang chủ website:</strong> Chuyển hướng trang 404 đến trang chủ website.</p><p></p><strong>URL tùy chỉnh:</strong> Chuyển hướng yêu cầu 404 đến một URL cụ thể.</p>"
            ], Seo_404::config('redirect_to'))
            ->add('seo_404[redirect_link]', 'text', [
                'label' => 'URL tùy chỉnh',
                'note'  => "Nhập bất kỳ url nào (bao gồm cả http://) để sử dụng tùy chọn URL tùy chỉnh"
            ], Seo_404::config('redirect_link'))
            ->add('seo_404[redirect_logs]', 'switch', [
                'label' => 'Nhật ký 404 lỗi',
                'note'  => "Bật/Tắt Ghi nhật ký",
                'single'=> true,
            ], Seo_404::config('redirect_logs'));

        Admin::partial('function/system/html/default', [
            'title'       => 'Chuyển hướng 404',
            'description' => 'Quản lý chuyển hướng với các trang 404',
            'form'        => $form
        ]);
    }
    static function save($result, $data) {

        Option::update('seo_favicon' , FileHandler::handlingUrl(Request::post('seo_favicon')));
        Option::update('general_title' , Request::post('general_title'));
        Option::update('general_description' , Request::post('general_description'));
        Option::update('general_keyword' , Request::post('general_keyword'));
        Option::update('header_script' , Request::post('header_script'));
        Option::update('body_script' , Request::post('body_script'));
        Option::update('footer_script' , Request::post('footer_script'));
        Option::update('skd_seo_robots' , Request::post('skd_seo_robots'));
        Option::update('seo_point' , Request::post('seo_point'));

        $seo404 = Request::post('seo_404');

        if(!empty($seo404)) {

            $seo404Update = [
                'redirect_to'   => $seo404['redirect_to'],
                'redirect_link' => $seo404['redirect_link'],
                'redirect_logs' => $seo404['redirect_logs']
            ];

            Option::update('seo_404' , $seo404Update);
        }

        return $result;
    }
}

add_filter('skd_system_tab', 'AdminSystemSeo::seoThemeRegister', 50);
add_action('admin_system_seo_html','AdminSystemSeo::renderGeneral', 10);
add_action('admin_system_seo_html','AdminSystemSeo::renderScript', 20);
add_action('admin_system_seo_html','AdminSystemSeo::renderRobots', 30);
add_action('admin_system_seo_html','AdminSystemSeo::renderPoint', 40);
add_action('admin_system_seo_html','AdminSystemSeo::renderRedirect', 50);
add_filter('admin_system_seo_save','AdminSystemSeo::save',10,2);