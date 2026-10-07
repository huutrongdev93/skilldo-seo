<?php

use SkillDo\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Bốn endpoint văn bản công khai
|--------------------------------------------------------------------------
|
| Cả bốn đều chỉ đọc, không có form, không đọc gì từ phiên làm việc, và gần như
| toàn bộ lưu lượng là trình thu thập. Để chúng đi qua đủ middleware của nhóm
| `web` thì mỗi lượt bot ghé thăm lại tạo một file session trên đĩa và trả về
| cookie phiên — vừa tốn đĩa, vừa khiến CDN bỏ qua cache vì thấy `Set-Cookie`.
|
| Loại hai middleware đó ra là an toàn: không có phiên nào để mất, và CSRF chỉ
| có nghĩa với request ghi dữ liệu.
|
| Phải khai bằng `Route::withoutMiddleware()->group()`, KHÔNG gọi
| `->withoutMiddleware()` trên từng route: `SkillDo\Routing\Route` không có
| method đó, viết vậy là lỗi nghiêm trọng lúc nạp route.
|
| Bốn đường dẫn này cũng phải nằm trong `SetLanguage::exclude()` của provider.
*/
Route::withoutMiddleware([
    \SkillDo\Session\Middleware\StartSession::class,
    \SkillDo\Http\Middlewares\VerifyCsrfToken::class,
])->group(function () {

    Route::get('/sitemap.xml', 'SkdSeo\Controllers\Web\SeoController@sitemap')->name('sitemap');
    Route::get('/robots.txt', 'SkdSeo\Controllers\Web\SeoController@robots')->name('robots');
    Route::get('/llms.txt', 'SkdSeo\Controllers\Web\SeoController@llms')->name('llms');
    Route::get('/llms-full.txt', 'SkdSeo\Controllers\Web\SeoController@llmsFull')->name('llmsFull');

});

/*
|--------------------------------------------------------------------------
| Route dự phòng cho URL nhiều đoạn
|--------------------------------------------------------------------------
|
| Core chỉ có route bắt `/{slug}` và `/{locale}/{slug}`: URL từ ba đoạn trở lên
| (`/product-category/a/b/`) không khớp route nào, nên KHÔNG middleware nhóm `web`
| nào chạy — `RedirectIfMatched` không bao giờ thấy nó, và khách nhận trang lỗi
| "route could not be found" với mã 200. Đó đúng là dạng URL của site cũ cần 301.
|
| `Route::fallback()` luôn được xếp sau mọi route khác (RouteCollection tách riêng
| nhóm fallback), nên không che route thật nào dù file này nạp sớm.
*/
Route::fallback('SkdSeo\Controllers\Web\SeoController@notFound');
