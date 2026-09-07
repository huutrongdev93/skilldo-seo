<?php

use SkdSeo\Services\NoIndexService;
use SkillDo\Cms\Support\Admin;

/*
|--------------------------------------------------------------------------
| Chặn lập chỉ mục toàn website (site demo / site đang dựng)
|--------------------------------------------------------------------------
| Công tắc ở Cấu hình > Seo > Chặn lập chỉ mục. Tắt thì không đăng ký gì cả để
| không phải trả phí cho một tính năng gần như luôn tắt.
|
| Ba lớp chặn và lý do từng lớp: xem docblock của NoIndexService.
*/

if(!NoIndexService::enabled() || Admin::is())
{
    return;
}

//Meta robots: 999 để thắng thiết lập tay của biên tập viên (AdminPoint chạy ở 99)
add_filter('seo_render', [NoIndexService::class, 'seoRender'], 999, 2);

//robots.txt: thay toàn bộ nội dung admin tự viết
add_filter('skd_seo_robots_content', [NoIndexService::class, 'robots'], 999);

/*
| X-Robots-Tag phải gửi trước khi có output. Bootstrap của plugin chạy ở
| plugins_loaded, tức là trước cả routing, nên gọi thẳng ở đây là sớm nhất và
| phủ được cả sitemap.xml / llms.txt (các file không có thẻ head để chèn meta).
*/
NoIndexService::httpHeader();
