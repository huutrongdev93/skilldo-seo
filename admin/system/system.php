<?php
class AdminSystemSeo {
    static function seoThemeRegister($tabs) {
        if(!empty($tabs['theme-seo'])) unset($tabs['theme-seo']);
        $tabs['seo'] = [
            'label'       => 'Seo',
            'group'       => 'marketing',
            'description' => 'Quản lý thông tin hỗ trợ seo website',
            'callback'    => 'AdminSystemSeo::render',
            'icon'        => '<i class="fal fa-megaphone"></i>'
        ];
        return $tabs;
    }
    static function render($ci, $tab): void {
        do_action('admin_system_seo_html');
    }
    static function renderGeneral($tab): void {
        $form = form();
        $form
            ->image('seo_favicon', ['label' => 'Favicon'], Option::get('seo_favicon'))
            ->image('site_social_image', ['label' => 'Ảnh hiển thị khi share'], Option::get('site_social_image'))
            ->text('general_title', ['label' => 'Meta title (shop)'], Option::get('general_title'))
            ->textarea('general_description', ['label' => 'Meta description (Mô tả trang chủ)'], Option::get('general_description'))
            ->textarea('general_keyword', ['label' => 'Meta keyword (Từ khóa trang chủ)'], Option::get('general_keyword'));

        Admin::view('components/system-default', [
            'title'       => 'Cấu hình chung',
            'description' => 'Quản lý thông tin seo website cơ bản',
            'form'        => $form
        ]);
    }
    static function renderProduct($tab): void {
        if(class_exists('sicommerce')) {
            $form = form();
            $form
                ->text('product_title', ['label' => 'Meta title (Danh sách sản phẩm)'], Option::get('product_title'))
                ->textarea('product_description', ['label' => 'Meta description (Danh sách sản phẩm)'], Option::get('product_description'))
                ->textarea('product_keyword', ['label' => 'Meta keyword (Danh sách sản phẩm)'], Option::get('product_keyword'));

            Admin::view('components/system-default', [
                'title'       => 'Trang Danh Sách Sản Phẩm',
                'description' => 'Quản lý thông tin seo website trang danh sách sản phẩm',
                'form'        => $form
            ]);
        }
    }
    static function renderScript($tab): void {
        $form = form();
        $form
            ->code('header_script',  ['label' => 'Script Header', 'language'  => 'javascript'], Option::get('header_script'))
            ->code('body_script',    ['label' => 'Script Body',   'language'  => 'javascript'], Option::get('body_script'))
            ->code('footer_script',  ['label' => 'Script Footer', 'language'  => 'javascript'], Option::get('footer_script'));

        Admin::view('components/system-default', [
            'title'       => 'Script',
            'description' => 'Chèn code seo, code của bên thứ ba vào các vị trí tương ứng (google analytic code, google master code, chat code, thống kê code..)',
            'form'        => $form
        ]);
    }
    static function renderRobots($tab): void {
        $form = form();
        $form
            ->textarea('skd_seo_robots',  ['label' => 'Nội dung file robots'], Option::get('skd_seo_robots'));

        Admin::view('components/system-default', [
            'title'       => 'File Robots',
            'description' => 'Điều hướng các robot tìm kiếm cho phép hoặc không cho phép các công cụ tìm kiếm thu thập dữ liệu',
            'form'        => $form
        ]);
    }
    static function renderPoint($tab): void {

        $seoPointSupport = [
            'page' => 'Trang nội dung',
            'products' => 'Sản phẩm',
            'products_categories' => 'Danh mục sản phẩm'
        ];

        foreach (Taxonomy::getCategory() as $key => $cateType) {
            $cate = Taxonomy::getCategory($cateType);
            $seoPointSupport['post_categories_'.$cateType] = $cate['labels']['name'];
        }

        foreach (Taxonomy::getPost() as $key => $postType) {
            $cate = Taxonomy::getPost($postType);
            $seoPointSupport['post_'.$postType] = $cate['labels']['name'];
        }

        $form = form();

        $form->add('seo_point',  'select', ['label' => 'Chấm điểm seo', 'options' => [0 => 'không sử dụng', 1 => 'Sử dụng']], Option::get('seo_point'));
        $form->add('seo_point_support',  'checkbox', ['label' => 'Hỗ trợ', 'options' => $seoPointSupport], Option::get('seo_point_support'));

        Admin::view('components/system-default', [
            'title'       => 'Chấm điểm seo',
            'description' => 'Quản lý công cụ chấm điểm seo trong bài viết, sản phẩm...',
            'form'        => $form
        ]);
    }
    static function renderRedirect($tab): void {

        $form = form();

        $form->switch('seo_404[log404]', [
            'label' => 'Nhật ký 404 lỗi',
            'note'  => "Bật/Tắt Ghi nhật ký",
        ], Log404Helper::config('log404'));

        $form->radio('seo_404[redirect_404]', [
            '' => '<strong>Không chuyển hướng:</strong> Để tắt chuyển hướng.',
            'home' => '<strong>Trang chủ website:</strong> Chuyển hướng trang 404 đến trang chủ website.',
            'link' => '<strong>URL tùy chỉnh:</strong> Chuyển hướng yêu cầu 404 đến một URL cụ thể.'
        ], [
            'label' => 'Chuyển hướng đến',
        ], Log404Helper::config('redirect_404'));

        $form->text('seo_404[redirect_404_link]', [
            'label' => 'URL tùy chỉnh',
            'note'  => "Nhập bất kỳ url nào (bao gồm cả http://) để sử dụng tùy chọn URL tùy chỉnh"
        ], Log404Helper::config('redirect_404_link'));

        Admin::view('components/system-default', [
            'title'       => 'Chuyển hướng 404',
            'description' => 'Quản lý chuyển hướng với các trang 404',
            'form'        => $form
        ]);
    }
    static function save(\SkillDo\Http\Request $request): void
    {
        Option::update('seo_favicon' , FileHandler::handlingUrl($request->input('seo_favicon')));
        Option::update('site_social_image' , FileHandler::handlingUrl($request->input('site_social_image')));
        Option::update('general_title' , $request->input('general_title'));
        Option::update('general_description' , $request->input('general_description'));
        Option::update('general_keyword' , $request->input('general_keyword'));
        Option::update('product_title' , $request->input('product_title'));
        Option::update('product_description' , $request->input('product_description'));
        Option::update('product_keyword' , $request->input('product_keyword'));
        Option::update('header_script' , $_POST['header_script']);
        Option::update('body_script' , $_POST['body_script']);
        Option::update('footer_script' , $_POST['footer_script']);
        Option::update('skd_seo_robots' , $request->input('skd_seo_robots'));
        Option::update('seo_point' , $request->input('seo_point'));
        Option::update('seo_point_support' , $request->input('seo_point_support'));

        $seo404 = $request->input('seo_404');

        if(!empty($seo404)) {

            $seo404Update = [
                'redirect_404'      => $seo404['redirect_404'],
                'redirect_404_link' => $seo404['redirect_404_link'],
                'log404'            => $seo404['log404']
            ];

            if(!empty($seo404Update['redirect_404']) && $seo404Update['redirect_404'] == 'link') {

                if(empty($seo404Update['redirect_404_link'])) {
                    response()->error(trans('Không được để trống Url chuyển hướng'));
                }

                if(!Url::is($seo404Update['redirect_404_link'])) {
                    response()->error(trans('Url chuyển hướng phải là url'));
                }
            }

            Option::update('seo_404' , $seo404Update);
        }
    }
}

add_filter('skd_system_tab', 'AdminSystemSeo::seoThemeRegister', 50);
add_action('admin_system_seo_html','AdminSystemSeo::renderGeneral', 10);
add_action('admin_system_seo_html','AdminSystemSeo::renderProduct', 15);
add_action('admin_system_seo_html','AdminSystemSeo::renderScript', 20);
add_action('admin_system_seo_html','AdminSystemSeo::renderRobots', 30);
add_action('admin_system_seo_html','AdminSystemSeo::renderPoint', 40);
add_action('admin_system_seo_html','AdminSystemSeo::renderRedirect', 50);
add_action('admin_system_seo_save','AdminSystemSeo::save');
