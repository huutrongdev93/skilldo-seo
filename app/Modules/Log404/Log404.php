<?php
namespace SkdSeo\Modules\Log404;

use SkillDo\Cms\Support\Url;
use Illuminate\Support\Facades\DB;

class Log404
{
    static function handle(): void
    {
        $response = response();

        if ($response->getStatusCode() === 404)
        {
            $request = request();

            $url     = $request->getRequestUri();

            $ip      = $request->ip();

            $log404 = \SkdSeo\Models\Log404::where('path', $url)->select('redirect', 'to', 'hit')->first();

            if(!hasItems($log404))
            {
                if(config('skd-seo::log404.enabled', 1) === 1)
                {
                    \SkdSeo\Models\Log404::create([
                        'path'      => $url,
                        'redirect'  => 0,
                        'ip'        => $ip,
                        'hit'       => 1,
                    ]);
                }
            }
            else
            {
                \SkdSeo\Models\Log404::where('id', $log404->id)->update([
                    'ip'    => $ip,
                    'hit'   => DB::raw('hit + 1'),
                    'updated' => gmdate('Y-m-d H:i:s', time() + 7 * 3600)
                ]);

                if($log404->redirect == 1 && !empty($log404->to))
                {
                    response()
                        ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');

                    header("Location: $log404->to", true, 301);
                    exit;
                }

                $redirectType = config('plugin.skd-seo.log404.redirect', 'home');

                if(!empty($redirectType))
                {
                    $target = config('plugin.skd-seo.log404.link', $redirectType);

                    if($redirectType == 'home')
                    {
                        $target = Url::base();
                    }

                    if(!empty($target))
                    {
                        response()
                            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                            ->header('Pragma', 'no-cache')
                            ->header('Expires', '0');

                        header("Location: $target", true, 301);
                        exit;
                    }
                }
            }
        }
    }
}