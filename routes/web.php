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
