<?php
return [
    /*
    |--------------------------------------------------------------------------
    | enabled
    |--------------------------------------------------------------------------
    | Bật tắt bảng Chuyển Hướng (Cấu hình hệ thống > Marketing > Chuyển Hướng).
    |
    | Mặc định BẬT: có dòng trong bảng `redirect` nghĩa là người dùng đã chủ động
    | khai chuyển hướng đó. Trước đây middleware mượn khoá `skd-seo::log404.redirect`
    | (option của mục Log 404, mặc định rỗng) làm công tắc nên mọi chuyển hướng
    | thêm qua admin đều không chạy mà không có thông báo nào.
    |
    | Muốn tắt hẳn thì tạo config/redirect.php ở thư mục config/ gốc dự án và đặt
    | 'enabled' => 0.
    */
    'enabled' => 1,
];
