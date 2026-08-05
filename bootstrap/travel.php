<?php

use SkdSeo\Modules\System\AdminSystemTravel;
use SkdSeo\Services\SchemaTravel;
use SkdSeo\Services\SeoTravel;
use SkdSeo\Services\SitemapTour;
use SkdSeo\Services\SitemapTourArchive;
use SkdSeo\Services\SitemapTourCategory;
use SkillDo\Cms\Support\Admin;

/*
|--------------------------------------------------------------------------
| Hỗ trợ plugin travel (Tour & Lữ hành)
|--------------------------------------------------------------------------
| Hook đăng ký vô điều kiện, còn việc plugin travel có được cài hay không thì
| kiểm tra BÊN TRONG từng callback: bootstrap của các plugin chạy theo thứ tự
| nạp, nên tại thời điểm này class Travel\Models\Tour có thể chưa tồn tại dù
| plugin đã bật.
*/

if(Admin::is())
{
    /*
    | Chấm điểm seo: thêm tour / danh mục tour vào danh sách module hỗ trợ và
    | khai báo lớp đọc-ghi metadata tương ứng.
    */
    add_filter('seo_point_support_module', [SeoTravel::class, 'pointSupport']);

    add_filter('seo_point_admin_module_enable', [SeoTravel::class, 'pointModule']);

    /*
    | Cấu hình > Seo: khối meta cho trang danh sách tour tổng.
    | Priority 16 để nằm ngay sau khối sản phẩm (15).
    */
    add_action('admin_system_seo_html', [AdminSystemTravel::class, 'render'], 16);

    add_action('admin_system_seo_save', [AdminSystemTravel::class, 'save']);
}
else
{
    /*
    | Meta head: ánh xạ data-bag của travel (tour / category / archive) sang
    | title - description - keyword - ảnh share.
    */
    add_filter('seo_head_base', [SeoTravel::class, 'headBase'], 10, 2);

    //Robots / canonical / schema thủ công lưu trong metabox Seo
    add_filter('seo_point_object', [SeoTravel::class, 'pointObject'], 10, 2);

    add_filter('seo_point_admin_module_enable', [SeoTravel::class, 'pointModule']);

    //Schema.org: TouristTrip + Product, FAQPage, ItemList
    add_filter('schema_render', [SchemaTravel::class, 'render'], 10, 2);

    //llms.txt
    add_filter('skd_seo_llms_content', [SeoTravel::class, 'llms']);

    /*
    | Sitemap: tour, danh mục tour (4 trục phân loại) và trang lọc SEO.
    */
    add_filter('seo_sitemap_list', [SitemapTour::class, 'register']);
    add_filter('seo_sitemap_tour_xml', [SitemapTour::class, 'sitemap'], 10, 3);

    add_filter('seo_sitemap_list', [SitemapTourCategory::class, 'register']);
    add_filter('seo_sitemap_tour_category_xml', [SitemapTourCategory::class, 'sitemap']);

    add_filter('seo_sitemap_list', [SitemapTourArchive::class, 'register']);
    add_filter('seo_sitemap_tour_archive_xml', [SitemapTourArchive::class, 'sitemap']);
}
