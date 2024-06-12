<?php
use Illuminate\Database\Capsule\Manager as DB;

class Log404Helper {

    static function config($key = '') {

        $seo404 = Option::get('seo_404');

        $seo404 = (is_array($seo404)) ? $seo404 : [];

        $seo404['redirect_404'] = (!empty($seo404['redirect_404'])) ? $seo404['redirect_404'] : '';

        $seo404['redirect_404_link'] = (!empty($seo404['redirect_404_link'])) ? $seo404['redirect_404_link'] : '';

        $seo404['log404'] = (!empty($seo404['log404'])) ? $seo404['log404'] : 0;

        if(!empty($key)) return Arr::get($seo404, $key);

        return $seo404;
    }

    static function log(): void
    {
        if(Admin::is()) return;

        $page = Cms::get('template')->getView(false);

        $page = Str::afterLast($page, '/');

        $page = str_replace('.blade.php', '', $page);

        if($page == '404-error') {

            $config = Log404Helper::config();

            $request = request();

            $host = $request->getHost();

            $url  = $request->url();

            $url  = str_replace('https://'.$host, '', $url);

            $url  = str_replace('https://www.'.$host, '', $url);

            $url  = str_replace('http://'.$host, '', $url);

            $url  = str_replace('http://www.'.$host, '', $url);

            $url  = trim($url, '/');

            $log404 = Log404::where('path', $url)->select('redirect', 'to', 'hit')->first();

            if($config['log404'] == 1) {

                $ip = request()->ip();

                if(have_posts($log404)) {

                    Log404::where('path', $url)->update([
                        'ip'    => $ip,
                        'hit'   => DB::raw('hit + 1'),
                        'updated' => gmdate('Y-m-d H:i:s', time() + 7 * 3600)
                    ]);
                }
                else {
                    $log = [
                        'path'      => $url,
                        'redirect'  => 0,
                        'ip'        => $ip,
                        'hit'       => 1,
                    ];

                    Log404::insert($log);
                }
            }

            //Chuyển hướng
            if(have_posts($log404)) {
                if($log404->redirect == 1 && !empty($log404->to)) {
                    header('Location:' . $log404->to);
                    exit();
                }
            }

            if(!empty($config['redirect_404'])) {

                $redirectUrl = '';

                if($config['redirect_404'] == 'home') {
                    $redirectUrl = Url::base();
                }

                if($config['redirect_404'] == 'link') {
                    $redirectUrl = $config['redirect_404_link'];
                }

                if(!empty($redirectUrl) && Url::is($redirectUrl)) {
                    header('Location: '.$redirectUrl);
                    exit();
                }
            }
        }
    }
}

add_action('template_redirect', 'Log404Helper::log', 1);