<?php

use SkdSeo\Modules\System\AdminSystemTag;
use SkdSeo\Services\SeoTag;
use SkdSeo\Services\SitemapTag;
use SkillDo\Cms\Support\Admin;

/*
|--------------------------------------------------------------------------
| Hỗ trợ chức năng Thẻ (tag) của CMS 8.1.3+
|--------------------------------------------------------------------------
| Hook đăng ký vô điều kiện, việc CMS có chức năng thẻ hay không thì kiểm tra
| BÊN TRONG từng callback (SeoTag::support): plugin phải chạy được cả trên bản
| CMS cũ chưa có model Tag.
*/

if(Admin::is())
{
    /*
    | Chấm điểm seo: thêm thẻ vào danh sách module hỗ trợ, khai báo lớp đọc-ghi
    | metadata và rút gọn bộ tiêu chí (form thẻ không có trình soạn thảo nội dung).
    */
    add_filter('seo_point_support_module', [SeoTag::class, 'pointSupport']);

    add_filter('seo_point_admin_module_enable', [SeoTag::class, 'pointModule']);

    add_filter('seo_point_criteria', [SeoTag::class, 'pointCriteria'], 10, 2);

    /*
    | Cấu hình > Seo: khối cấu hình cho trang thẻ.
    | Priority 17 để nằm ngay sau khối sản phẩm (15) và tour (16).
    */
    add_action('admin_system_seo_html', [AdminSystemTag::class, 'render'], 17);

    add_action('admin_system_seo_save', [AdminSystemTag::class, 'save']);
}
else
{
    /*
    | Meta head: mô tả mặc định cho thẻ trống nội dung, tiêu đề riêng cho trang 2+.
    */
    add_filter('seo_head_base', [SeoTag::class, 'headBase'], 10, 2);

    /*
    | Robots cho thẻ mỏng và og article:tag của trang chi tiết bài viết.
    |
    | Priority 20 — chạy TRƯỚC AdminPoint (99) để thiết lập tay trong metabox Seo
    | vẫn là tiếng nói cuối cùng.
    */
    add_filter('seo_render', [SeoTag::class, 'render'], 20, 2);

    //Robots / canonical / schema thủ công lưu trong metabox Seo
    add_filter('seo_point_object', [SeoTag::class, 'pointObject'], 10, 2);

    add_filter('seo_point_admin_module_enable', [SeoTag::class, 'pointModule']);

    //llms.txt
    add_filter('skd_seo_llms_content', [SeoTag::class, 'llms']);

    //Sitemap trang lưu trữ theo thẻ
    add_filter('seo_sitemap_list', [SitemapTag::class, 'register']);
    add_filter('seo_sitemap_tag_xml', [SitemapTag::class, 'sitemap'], 10, 3);
}
