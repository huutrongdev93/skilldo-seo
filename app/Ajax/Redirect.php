<?php
namespace SkdSeo\Ajax;

use SkillDo\Cms\Support\Url;
use SkillDo\Http\Request;

Class Redirect
{
    static function save(Request $request): void
    {
        $id = (int)$request->input('id');

        $redirect = \SkdSeo\Models\Redirect::find($id);

        if(noItems($redirect))
        {
            response()->error(trans('Dữ liệu không tồn tại'));
        }

        $redirect->redirect = (int)$request->input('redirect');

        $redirect->to = $request->input('redirect_to');

        if(empty($redirect->to))
        {
            response()->error(trans('Không được để trống Url chuyển hướng'));
        }

        if(!Url::is($redirect->to))
        {
            response()->error(trans('Url chuyển hướng phải là url'));
        }

        $redirect->save();

        \SkillDo\Cache::delete('seo_redirect_'.md5($redirect->path));

        response()->success(trans('ajax.save.success'), $redirect);
    }
}

