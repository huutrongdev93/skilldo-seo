<?php
/*
|--------------------------------------------------------------------------
| Tool MCP chấm điểm SEO (plugin mcp-server đọc filter này)
|--------------------------------------------------------------------------
| Tên class dạng CHUỖI, không `new`: site không cài mcp-server thì filter không
| ai đọc và các class trong app/Mcp/ (kế thừa McpServer\Supports\McpTool) không
| bao giờ được nạp. Chỉ khai khi bật chấm điểm seo, cùng điều kiện hiện metabox.
*/
use SkillDo\Cms\Support\Option;

add_filter('cms_mcp_tools', function (array $tools) {

    if (empty(Option::get('seo_point'))) return $tools;

    $tools['seo_guidelines'] = \SkdSeo\Mcp\SeoGuidelinesTool::class;
    $tools['seo_score']      = \SkdSeo\Mcp\SeoScoreTool::class;
    $tools['seo_update']     = \SkdSeo\Mcp\SeoUpdateTool::class;

    return $tools;
});
