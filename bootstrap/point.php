<?php

use SkdSeo\Modules\Point\AdminPoint;
use SkdSeo\Supports\SeoPoint;
use SkillDo\Cms\Support\Option;

if(!empty(Option::get('seo_point')))
{
    if(Admin::is())
    {
        /*
        | Admin: hiện metabox Seo trong form và lưu lại khi bấm cập nhật.
        */
        add_action('add_meta_box', [SeoPoint::class, 'registerMetabox']);
        add_action('save_object', [AdminPoint::class, 'save'], 10, 3);
    }
    else
    {
        /*
        | Frontend: xuất robots / canonical / schema thủ công đã lưu.
        |
        | Hai filter này TRƯỚC ĐÂY nằm chung nhánh Admin::is() nên chỉ đăng ký
        | trong admin — nơi cle_header không bao giờ chạy. Kết quả là mọi thiết
        | lập No Index / Canonical / Schema thủ công của metabox đều bị bỏ qua
        | ngoài trang người dùng thấy.
        */
        /*
        | Priority 99: thiết lập tay của người biên tập phải THẮNG mọi schema /
        | robots do plugin khác sinh ra. Để ở 10 thì plugin nào đăng ký sau (tour,
        | bất động sản, hỏi đáp...) vẫn nối schema của nó vào sau khi người dùng
        | đã chọn "Thủ công".
        */
        add_filter('schema_render', [AdminPoint::class, 'schemaRender'], 99, 2);
        add_filter('seo_render', [AdminPoint::class, 'seoRender'], 99, 2);
    }
}
