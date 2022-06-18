<?php
include_once 'redirect.php';
Class Seo_404 {
    static public function config($key = '') {
        $seo404 = Option::get('seo_404');
        $seo404 = (is_array($seo404)) ? $seo404 : [];
        $seo404['redirect_to'] = (!empty($seo404['redirect_to'])) ? $seo404['redirect_to'] : 0;
        $seo404['redirect_link'] = (!empty($seo404['redirect_link'])) ? $seo404['redirect_link'] : '';
        $seo404['redirect_logs'] = (!empty($seo404['redirect_logs'])) ? $seo404['redirect_logs'] : 0;
        if(!empty($key)) return Arr::get($seo404, $key);
        return $seo404;
    }
    static public function page() {
        include 'views/setting.php';
    }
    static public function save($result, $data) {

        $seo404 = InputBuilder::Post('seo_404');

        if(!empty($seo404)) {

            $seo404Update = [
                'redirect_to'   => $seo404['redirect_to'],
                'redirect_link' => $seo404['redirect_link'],
                'redirect_logs' => $seo404['redirect_logs']
            ];

            Option::update('seo_404' , $seo404Update);
        }

        return $result;
    }
    static public function handle() {
        if(Admin::is()) return;

        $page = get_instance()->template->get_view(false);

        if($page == '404-error') {

            $redirect = Seo_404::logs();

            $redirectConfig = [
                'redirect_to' => Seo_404::config('redirect_to'),
                'redirect_link' => Seo_404::config('redirect_link')
            ];

            if(have_posts($redirect)) {
                if(!empty($redirect->redirect)) {
                    if($redirect->redirect == 2) return true;
                    if($redirect->redirect == 1) {
                        $redirectConfig['redirect_to'] = (!empty($redirect->to)) ? 'link' : 'home';
                    }
                    $redirectConfig['redirect_link'] = (!empty($redirect->to)) ? $redirect->to : ((!empty($redirectConfig['redirect_link'])) ? $redirectConfig['redirect_link'] : Url::base());
                }
            }

            if(!empty($redirectConfig['redirect_to'])) {
                $url = Url::base();
                if($redirectConfig['redirect_to'] == 'link' && !empty($redirectConfig['redirect_link'])) {
                    $url = $redirectConfig['redirect_link'];
                }
                if(!str_contains('https://', $url) && !str_contains('http://', $url)) {
                    $url = Url::base().$url;
                }
                header('Location: '.$url);
                exit();
            }
        }
    }
    static public function logs() {
        $domain = Url::base();
        $domain = str_replace('http://', '', $domain);
        $domain = str_replace('https://', '', $domain);
        $domain = str_replace('www.', '', $domain);
        $domain = trim($domain, '/');

        $url = Url::current();
        $url = str_replace(Url::base(), '', $url);
        $url = str_replace('http://', '', $url);
        $url = str_replace('https://', '', $url);
        $url = str_replace('www.', '', $url);
        $url = str_replace($domain, '', $url);
        $url = trim($url, '/');
        $redirect = Seo_Redirect::get(['where' => ['path' => $url]]);
        if(!have_posts($redirect) && Seo_404::config('redirect_logs')) {
            Seo_Redirect::insert(['path' => $url]);
            return 0;
        }
        return $redirect;
    }
}

add_action('system_tab_theme_seo_render','Seo_404::page',20);
add_filter('system_theme_seo_save','Seo_404::save',10,2);
add_action('template_redirect','Seo_404::handle',1);