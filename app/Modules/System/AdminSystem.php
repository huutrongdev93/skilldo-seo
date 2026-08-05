<?php
namespace SkdSeo\Modules\System;

use Admin\Supports\Component;
use Admin\Supports\Components\BlockSystem;
use Illuminate\Support\Arr;
use SkillDo\Cms\Location\Location2;
use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;
use SkillDo\Cms\Taxonomy\Taxonomy;

class AdminSystem
{
    static function register($tabs)
    {
        if(!empty($tabs['theme-seo'])) unset($tabs['theme-seo']);

        $tabs['seo'] = [
            'label'       => 'Seo',
            'group'       => 'marketing',
            'description' => 'Quản lý thông tin hỗ trợ seo website',
            'callback'    => 'SkdSeo\Modules\System\AdminSystem::render',
            'icon'        => '<i class="fal fa-megaphone"></i>'
        ];

        return $tabs;
    }

    static function render(): void
    {
        do_action('admin_system_seo_html');
    }

    static function renderGeneral(): void
    {
        $form = form();

        $form
            ->image('seo_favicon', ['label' => 'Favicon'], Option::get('seo_favicon'))
            ->image('site_social_image', ['label' => 'Ảnh hiển thị khi share'], Option::get('site_social_image'))
            ->text('general_title', ['label' => 'Meta title (shop)'], Option::get('general_title'))
            ->textarea('general_description', ['label' => 'Meta description (Mô tả trang chủ)'], Option::get('general_description'))
            ->textarea('general_keyword', ['label' => 'Meta keyword (Từ khóa trang chủ)'], Option::get('general_keyword'));

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Cấu hình chung', 'Quản lý thông tin seo website cơ bản');
            $blockSystem->content($form);
        });
    }

    static function renderProduct(): void
    {
        if(class_exists('sicommerce'))
        {
            $form = form();
            $form
                ->text('product_title', ['label' => 'Meta title (Danh sách sản phẩm)'], Option::get('product_title'))
                ->textarea('product_description', ['label' => 'Meta description (Danh sách sản phẩm)'], Option::get('product_description'))
                ->textarea('product_keyword', ['label' => 'Meta keyword (Danh sách sản phẩm)'], Option::get('product_keyword'));

            echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
            {
                $blockSystem->header('Trang Danh Sách Sản Phẩm', 'Quản lý thông tin seo website trang danh sách sản phẩm');
                $blockSystem->content($form);
            });
        }
    }

    static function renderScript(): void {
        $form = form();
        $form
            ->code('header_script',  ['label' => 'Script Header', 'language'  => 'javascript'], Option::get('header_script'))
            ->code('body_script',    ['label' => 'Script Body',   'language'  => 'javascript'], Option::get('body_script'))
            ->code('footer_script',  ['label' => 'Script Footer', 'language'  => 'javascript'], Option::get('footer_script'));

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Script', 'Chèn code seo, code của bên thứ ba vào các vị trí tương ứng (google analytic code, google master code, chat code, thống kê code..)');
            $blockSystem->content($form);
        });
    }

    static function renderSchema(): void {

        $schemaLocalBusiness = Option::get('schemaLocalBusiness', []);

        $states = Location2::provincesOptions();

        //chèn cặp key - value vào đầu array $states
        $states = Arr::prepend($states, 'Chọn tỉnh thành', '');

        $form = form();

        $form->switch('schemaLocalBusiness[enabled]', [
            'label' => 'Kích hoạt Schema LocalBusiness',
            'note'  => "Bật/Tắt chức năng Schema LocalBusiness",
        ], $schemaLocalBusiness['enabled'] ?? false);

        $form->select2('schemaLocalBusiness[addressLocality]',  ['label' => 'Tỉnh / Thành phố'], $schemaLocalBusiness['addressLocality'] ?? '')->options($states);

        $form->text('schemaLocalBusiness[openingHours]',  ['label' => 'Thời gian làm việc'], $schemaLocalBusiness['openingHours'] ?? 'Mo-Fr 08:00-17:00');

        $form->none('<p><b>Tác dụng:</b></p>');
        $form->none('<p>- Tối ưu SEO địa phương (Local SEO)</p>');
        $form->none('<p>- Hỗ trợ xuất hiện trong Google Maps và Local Pack</p>');
        $form->none('<p>- Hiển thị giờ mở cửa, địa chỉ, số điện thoại ngay trên SERP</p>');
        $form->none('<p>- Rất quan trọng nếu khách hàng tìm kiếm theo khu vực</p>');
        $form->none('<p><b>Hiển thị trên Google:</b></p>');
        $form->none('<p>- Ô thông tin địa chỉ + giờ mở cửa + số điện thoại ngay dưới tiêu đề</p>');

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Schema LocalBusiness', 'Khai báo thông tin doanh nghiệp có địa điểm vật lý cụ thể');
            $blockSystem->content($form);
        });
    }

    static function renderRobots(): void {

        $form = form();

        $form
            ->textarea('skd_seo_robots',  ['label' => 'Nội dung file robots'], Option::get('skd_seo_robots'));

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('File Robots', 'Điều hướng các robot tìm kiếm cho phép hoặc không cho phép các công cụ tìm kiếm thu thập dữ liệu');
            $blockSystem->content($form);
        });
    }

    static function renderPoint(): void {

        $seoPointSupport = [
            'page' => 'Trang nội dung',
            'products' => 'Sản phẩm',
            'products_categories' => 'Danh mục sản phẩm'
        ];

        foreach (Taxonomy::getCategory() as $cateType => $cate)
        {
            $seoPointSupport['post_categories_'.$cateType] = $cate['labels']['name'];
        }

        foreach (Taxonomy::getPost() as $postType => $post)
        {
            $seoPointSupport['post_'.$postType] = $post['labels']['name'];
        }

        /*
        | Plugin ngoài (travel...) tự thêm module của mình vào danh sách chấm
        | điểm seo qua filter này.
        */
        $seoPointSupport = apply_filters('seo_point_support_module', $seoPointSupport);

        $form = form();

        $form->select('seo_point',  ['label' => 'Chấm điểm seo'], Option::get('seo_point'))->options([0 => 'không sử dụng', 1 => 'Sử dụng']);

        $form->checkbox('seo_point_support',  ['label' => 'Hỗ trợ'], Option::get('seo_point_support'))->options($seoPointSupport);

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Chấm điểm seo', 'Quản lý công cụ chấm điểm seo trong bài viết, sản phẩm...');
            $blockSystem->content($form);
        });
    }

    static function renderRedirect(): void {

        $form = form();

        $form->switch('seo_404[enabled]', [
            'label' => 'Nhật ký 404 lỗi',
            'note'  => "Bật/Tắt Ghi nhật ký",
        ], config('skd-seo::log404.enabled'));

        $form->radio('seo_404[redirect]', [
            '' => '<strong>Không chuyển hướng:</strong> Để tắt chuyển hướng.',
            'home' => '<strong>Trang chủ website:</strong> Chuyển hướng trang 404 đến trang chủ website.',
            'link' => '<strong>URL tùy chỉnh:</strong> Chuyển hướng yêu cầu 404 đến một URL cụ thể.'
        ], [
            'label' => 'Chuyển hướng đến',
        ], config('skd-seo::log404.redirect'));

        $form->text('seo_404[link]', [
            'label' => 'URL tùy chỉnh',
            'note'  => "Nhập bất kỳ url nào (bao gồm cả https://) để sử dụng tùy chọn URL tùy chỉnh"
        ], config('skd-seo::log404.link'));

        echo Component::blockSystem(function (BlockSystem $blockSystem) use ($form)
        {
            $blockSystem->header('Chấm điểm seo', 'Quản lý công cụ chấm điểm seo trong bài viết, sản phẩm...');
            $blockSystem->content($form);
        });
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        Option::update('seo_favicon' , \Illuminate\Support\Facades\File::clear($request->input('seo_favicon')));
        Option::update('site_social_image' , \Illuminate\Support\Facades\File::clear($request->input('site_social_image')));
        Option::update('general_title' , $request->input('general_title'));
        Option::update('general_description' , $request->input('general_description'));
        Option::update('general_keyword' , $request->input('general_keyword'));
        Option::update('product_title' , $request->input('product_title'));
        Option::update('product_description' , $request->input('product_description'));
        Option::update('product_keyword' , $request->input('product_keyword'));
        Option::update('header_script' , $request->input('header_script'));
        Option::update('body_script' , $request->input('body_script'));
        Option::update('footer_script' , $request->input('footer_script'));
        Option::update('skd_seo_robots' , $request->input('skd_seo_robots'));
        Option::update('schemaLocalBusiness' , $request->input('schemaLocalBusiness'));
        if(Admin::isRoot())
        {
            Option::update('seo_point', $request->input('seo_point'));
            Option::update('seo_point_support' , $request->input('seo_point_support'));
        }

        $seo404 = $request->input('seo_404');

        if(hasItems($seo404))
        {
            $seo404Update = [
                'enabled'  => $seo404['enabled'],
                'redirect' => $seo404['redirect'],
                'link'     => $seo404['link']
            ];

            if(!empty($seo404Update['redirect']) && $seo404Update['redirect'] == 'link')
            {
                if(empty($seo404Update['link']))
                {
                    response()->error(trans('Không được để trống Url chuyển hướng'));
                }

                if(!Url::is($seo404Update['link'])) {
                    response()->error(trans('Url chuyển hướng phải là url'));
                }
            }

            Option::update('seo_404' , $seo404Update);
        }
    }
}