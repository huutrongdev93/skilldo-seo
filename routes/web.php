<?php
Route::get('/sitemap.xml', 'SkdSeo\Controllers\Web\SeoController@sitemap')->name('sitemap');
Route::get('/robots.txt', 'SkdSeo\Controllers\Web\SeoController@robots')->name('robots');
//
//Route::get('robots.txt', 'home@page/skd_seo_robots', ['namespace' => 'frontend'])->name('robots');
//
//if(file_exists('views/plugins/skd-seo/assets/redirect.json')) {
//    $redirect = json_decode(file_get_contents('views/plugins/skd-seo/assets/redirect.json'));
//    if(hasItems($redirect)) {
//        $host = request()->getHost();
//        $url  = request()->url();
//        $url  = str_replace('https://'.$host, '', $url);
//        $url  = str_replace('https://www.'.$host, '', $url);
//        $url  = str_replace('http://'.$host, '', $url);
//        $url  = str_replace('http://www.'.$host, '', $url);
//        $url  = trim($url, '/');
//        foreach($redirect as $key => $value) {
//            if($value->from == $url) {
//                if(!empty($value->to)) {
//                    header('Location: '.$value->to);
//                    exit();
//                }
//                break;
//            }
//        }
//    }
//}