<?php
Route::get('sitemap.xml', 'home@page/skd_seo_sitemap', ['namespace' => 'frontend']);

Route::get('robots.txt', 'home@page/skd_seo_robots', ['namespace' => 'frontend']);

if(file_exists('views/plugins/skd-seo/assets/redirect.json')) {
    $redirect = json_decode(file_get_contents('views/plugins/skd-seo/assets/redirect.json'));
    if(have_posts($redirect)) {
        $host = request()->getHost();
        $url  = request()->url();
        $url  = str_replace('https://'.$host, '', $url);
        $url  = str_replace('https://www.'.$host, '', $url);
        $url  = str_replace('http://'.$host, '', $url);
        $url  = str_replace('http://www.'.$host, '', $url);
        $url  = trim($url, '/');
        foreach($redirect as $key => $value) {
            if($value->from == $url) {
                if(!empty($value->to)) {
                    header('Location: '.$value->to);
                    exit();
                }
                break;
            }
        }
    }
}