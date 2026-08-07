<?php
namespace SkdSeo\Middlewares;

use Closure;
use SkillDo\Cache\Cache;
use SkillDo\Cms\Support\Admin;
use SkillDo\Http\Request;

class RedirectIfMatched
{
    public function handle(Request $request, Closure $next)
    {
        if(Admin::is() || $request->ajax())
        {
            return $next($request);
        }

        /*
        | Công tắc RIÊNG của bảng Chuyển Hướng, mặc định BẬT (config/redirect.php).
        | Trước đây dùng nhờ `skd-seo::log404.redirect` — option của mục Log 404,
        | mặc định rỗng và không có ô bật/tắt nào trong màn Chuyển Hướng — nên mọi
        | chuyển hướng thêm qua admin đều im lặng không chạy.
        */
        if(!empty(config('skd-seo::redirect.enabled', true)))
        {
            $currentPath = ltrim($request->path(), '/');

            $redirect = Cache::remember('seo_redirect_'.md5($currentPath), 30*60, function() use ($currentPath)
            {
                return \SkdSeo\Models\Redirect::where('path', $currentPath)->first();
            });

            if(hasItems($redirect))
            {
                $target = $redirect->to;

                if ($query = $request->getQueryString())
                {
                    if (!preg_match('#^https?://#i', $target))
                    {
                        $target .= '?' . $query;
                    }
                    else
                    {
                        $target .= (parse_url($target, PHP_URL_QUERY) ? '&' : '?') . $query;
                    }
                }

                response()
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');

                header("Location: $target", true, $redirect->type);
                exit;
            }
        }

        return $next($request);
    }
}