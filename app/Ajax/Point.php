<?php
namespace SkdSeo\Ajax;

use SkdSeo\Supports\SeoPoint;
use SkillDo\Http\Request;

Class Point
{
    /**
     * Tiêu chí keywordUnique của metabox Seo: từ khóa chính đã được đối tượng
     * khác cùng module dùng chưa. Hai trang cùng nhắm một từ khóa sẽ tranh thứ
     * hạng của nhau (keyword cannibalization).
     */
    static function duplicate(Request $request): void
    {
        $module = (string)$request->input('module');

        $keyword = (string)$request->input('keyword');

        $id = (int)$request->input('id');

        if(empty(SeoPoint::module($module)))
        {
            response()->error(trans('Module không hỗ trợ chấm điểm seo'));
        }

        $items = SeoPoint::duplicateKeyword($module, $keyword, $id);

        response()->success(trans('Thành công'), [
            'items' => array_values($items),
        ]);
    }
}
