<?php
/*
|--------------------------------------------------------------------------
| Kiểm thử tool MCP chấm điểm SEO (CLI)
|--------------------------------------------------------------------------
| Chạy:  TEST_BASE_URL=http://s8.vn/ php plugins/skd-seo/tool/test-mcp.php
|
| Cần plugin mcp-server có trong plugins/ (tự đăng ký autoload McpServer\*) và
| chấm điểm seo đang bật cho bài viết (`post_post`). Boot ở ngữ cảnh WEB như
| request API thật. Mọi thao tác ghi chạy trong transaction luôn rollback.
|
| Độ khớp giữa SeoAnalyzer (PHP) và metabox (JS) kiểm ở tool/test-seo-point.php.
*/

use Illuminate\Support\Facades\DB;
use McpServer\Protocol\Server;
use McpServer\Supports\McpContext;
use McpServer\Supports\McpRegistry;
use SkdSeo\Supports\SeoAnalyzer;

if (PHP_SAPI !== 'cli') exit("Chỉ chạy ở CLI.\n");

putenv('TEST_CONTEXT=web');

unset($_SERVER['argv'], $_SERVER['argc']);

$root = dirname(__DIR__, 3);

/** @var \SkillDo\Application $app */
$app = require $root . '/tests/bootstrap.php';

require_once $root . '/tests/lib/Reporter.php';

spl_autoload_register(function ($class) use ($root)
{
    if (!str_starts_with($class, 'McpServer\\')) return;

    $file = $root.'/plugins/mcp-server/app/'.str_replace('\\', '/', substr($class, 10)).'.php';

    if (is_file($file)) require_once $file;
});

$report = new Reporter('skd-seo · tool MCP chấm điểm SEO');

$check = function (string $where, bool $ok, string $detail = '') use ($report)
{
    $ok ? $report->pass() : $report->fail($where, $detail ?: 'sai');
};

if (!is_file($root.'/plugins/mcp-server/app/Supports/McpRegistry.php'))
{
    $report->skip('mcp', 'không có plugins/mcp-server');

    exit($report->summary());
}

if (!\SkdSeo\Mcp\SeoTool::supported('post_post'))
{
    $report->skip('mcp', 'chưa bật chấm điểm seo cho bài viết (Cấu hình > Seo > Chấm điểm seo, tick "Bài viết")');

    exit($report->summary());
}

require_once $root.'/plugins/skd-seo/bootstrap/mcp.php';

$admin = null;

foreach (\SkillDo\Cms\Models\User::where('status', 'public')->orderBy('id')->limit(50)->get() as $user)
{
    if (\SkillDo\Cms\Support\UserRole::hasCap($user->id, 'loggin_admin') && \SkillDo\Cms\Support\UserRole::hasCap($user->id, 'add_posts'))
    {
        $admin = $user;
        break;
    }
}

if (!$admin)
{
    $report->skip('mcp', 'không tìm thấy user quản trị có quyền bài viết');

    exit($report->summary());
}

app()->instance('user', $admin);

$full = new McpContext($admin, 0, false);

$names = fn(array $tools) => array_map(fn($t) => $t->name(), $tools);

$seoTools = ['seo_guidelines', 'seo_score', 'seo_update'];

$check('registry · đủ 3 tool', array_diff($seoTools, $names(McpRegistry::forContext($full))) === []);
$check('registry · key chỉ đọc không thấy seo_update', array_values(array_intersect($seoTools, $names(McpRegistry::forContext(new McpContext($admin, 0, true))))) === ['seo_guidelines', 'seo_score']);

foreach ($seoTools as $tool)
{
    $class = apply_filters('cms_mcp_tools', [])[$tool] ?? null;

    $check('registry · '.$tool, McpRegistry::problem($tool, $class) === null, (string)McpRegistry::problem($tool, $class));
}

$report->section('registry', 'khai báo qua cms_mcp_tools khi bật chấm điểm seo');

$server = new Server('test', '1.0.0', '', McpRegistry::forContext($full), $full);

$call = function (string $tool, array $arguments) use ($server)
{
    $response = $server->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $tool, 'arguments' => $arguments]]);

    $result = $response['result'] ?? [];

    $data = isset($result['structuredContent']) ? json_decode(json_encode($result['structuredContent']), true) : null;

    return [$data, !empty($result['isError']), $result['content'][0]['text'] ?? json_encode($response)];
};

$body = '<p>Máy lọc nước giúp gia đình có nguồn nước sạch mỗi ngày. '.str_repeat('Bài viết hướng dẫn cách chọn thiết bị phù hợp với nhu cầu thực tế. ', 6).'</p>'
    .'<h2>Cách chọn máy lọc nước</h2><p>'.str_repeat('Hãy xem công suất, số lõi lọc và chi phí thay lõi trước khi mua. ', 8).'</p>'
    .'<ul><li>Công suất</li><li>Lõi lọc</li></ul><p>Xem <a href="/san-pham">sản phẩm</a> và <a href="https://who.int">WHO</a>.</p>'
    .'<p>'.str_repeat('Bảo dưỡng định kỳ giúp nước luôn sạch và máy bền lâu hơn. ', 8).'</p>';

$slugs = [];

DB::beginTransaction();

try
{
    // --- seo_guidelines
    [$guide, $err, $text] = $call('seo_guidelines', ['type' => 'post']);
    $check('guidelines', !$err && ($guide['enabled'] ?? false) && count($guide['criteria'] ?? []) === count(\SkdSeo\Supports\SeoPoint::criteria('post')), $text);
    $check('guidelines · keywordNotUsed đứng đầu', ($guide['criteria'][0]['key'] ?? '') === 'keywordNotUsed', $text);
    $check('guidelines · ngưỡng theo module (sản phẩm 150 từ)', class_exists(\Ecommerce\Models\Product::class)
        ? (($call('seo_guidelines', ['type' => 'product'])[0]['thresholds']['content_min_words'] ?? 0) === 150) : true);

    // --- bài thử
    $id = (int)\SkillDo\Cms\Models\Post::create([
        'title'           => 'Máy lọc nước: cách chọn đúng cho gia đình năm 2026',
        'seo_description' => 'Máy lọc nước nào phù hợp với gia đình bạn? Hướng dẫn chọn theo công suất, số lõi lọc và chi phí thay lõi, kèm lưu ý lắp đặt.',
        'content'         => $body,
        'image'           => 'news/a.webp',
        'post_type'       => 'post',
        'public'          => 0,
    ]);
    $check('bài thử', $id > 0);
    $slugs[] = DB::table('post')->where('id', $id)->value('slug');

    // --- seo_score khi chưa có từ khóa
    [$score0, $err, $text] = $call('seo_score', ['type' => 'post', 'id' => $id]);
    $check('score · chưa có từ khóa', !$err && $score0['focus_keyword'] === '' && $score0['criteria'][0]['passed'] === false, $text);

    // --- chấm thử bản nháp với từ khóa, chưa lưu
    [$draft, $err, $text] = $call('seo_score', ['type' => 'post', 'id' => $id, 'draft' => ['keyword' => 'máy lọc nước']]);
    $check('score · draft tăng điểm', !$err && $draft['draft'] === true && $draft['score'] > $score0['score'], $text);
    $check('score · draft không ghi gì', (string)\SkillDo\Cms\Models\Post::getMeta($id, 'seo_focus_keyword', true) === '');

    // --- seo_update đặt từ khóa → điểm bằng bản nháp và bằng SeoAnalyzer chấm trực tiếp
    [$updated, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id, 'focus_keyword' => 'máy lọc nước']);
    $check('update · focus_keyword', !$err && ($updated['score'] ?? -1) === $draft['score'], $text);
    $check('update · lưu meta', (string)\SkillDo\Cms\Models\Post::getMeta($id, 'seo_focus_keyword', true) === 'máy lọc nước');

    $post = \SkillDo\Cms\Models\Post::where('id', $id)->whereIn('public', [0, 1])->first();
    $direct = SeoAnalyzer::analyze('post', [
        'keyword' => 'máy lọc nước', 'title' => $post->title, 'seo_title' => $post->seo_title, 'seo_description' => $post->seo_description,
        'excerpt' => $post->excerpt, 'content' => $post->content, 'slug' => $post->slug, 'image' => $post->image,
    ], ['unique' => []]);
    $check('score · khớp SeoAnalyzer', $direct['score'] === $updated['score'], $direct['score'].' ≠ '.$updated['score']);

    // --- từ khóa trùng với bài khác
    $other = (int)\SkillDo\Cms\Models\Post::create(['title' => 'Bài khác về máy lọc nước', 'post_type' => 'post', 'public' => 0]);
    $slugs[] = DB::table('post')->where('id', $other)->value('slug');
    \SkillDo\Cms\Models\Post::updateMeta($other, 'seo_focus_keyword', 'Máy lọc nước');

    [$dup, $err, $text] = $call('seo_score', ['type' => 'post', 'id' => $id]);
    $uniqueItem = collect($dup['criteria'] ?? [])->firstWhere('key', 'keywordUnique');
    $check('score · từ khóa trùng bài khác', !$err && $uniqueItem && !$uniqueItem['passed'] && str_contains($uniqueItem['message'], 'Bài khác về máy lọc nước'), $text);

    // --- robots + canonical, giữ phần không gửi
    [, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id, 'robots' => ['noArchive', 'khong-hop-le']]);
    $check('update · robots giá trị lạ bị schema chặn', $err, $text);

    [, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id, 'robots' => ['noArchive']]);
    $robots = \SkillDo\Cms\Models\Post::getMeta($id, 'seo_robots', true);
    $check('update · robots (bài chưa có robots)', !$err && is_array($robots) && ($robots['robots'] ?? null) === ['noArchive'] && ($robots['index'] ?? '') === 'yes' && !array_key_exists(0, $robots), json_encode($robots).' '.$text);

    [, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id, 'index' => false]);
    $robots = (array)\SkillDo\Cms\Models\Post::getMeta($id, 'seo_robots', true);
    $check('update · index=false giữ robots cũ', !$err && ($robots['index'] ?? '') === 'no' && ($robots['robots'] ?? null) === ['noArchive'], json_encode($robots));

    [, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id, 'canonical' => \SkillDo\Cms\Support\Url::base('bai-goc')]);
    $check('update · canonical của site lưu tương đối', !$err && \SkillDo\Cms\Models\Post::getMeta($id, 'seo_canonical', true) === 'bai-goc', $text);

    [, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id, 'canonical' => 'javascript:alert(1)']);
    $check('update · canonical lạ bị chặn', $err, $text);

    [, $err, $text] = $call('seo_update', ['type' => 'post', 'id' => $id]);
    $check('update · không gửi gì thì báo', $err, $text);

    // --- loại chưa bật / không tồn tại
    $category = DB::table('categories')->value('id');
    if ($category && !\SkdSeo\Mcp\SeoTool::supported('post_categories_'.DB::table('categories')->where('id', $category)->value('cate_type')))
    {
        [, $err, $text] = $call('seo_score', ['type' => 'post_category', 'id' => (int)$category]);
        $check('score · loại chưa bật chấm điểm bị từ chối', $err && str_contains($text, 'not enabled'), $text);
    }

    [, $err, $text] = $call('seo_score', ['type' => 'post', 'id' => 999999999]);
    $check('score · không tồn tại', $err, $text);
}
catch (\Throwable $e)
{
    $report->fail('ngoại lệ', get_class($e).': '.$e->getMessage().' ('.basename($e->getFile()).':'.$e->getLine().')');
}
finally
{
    DB::rollBack();

    foreach (array_filter($slugs) as $slug)
    {
        \SkillDo\Cms\Routing\RouteRepository::forgetSlug($slug);
    }
}

$report->section('tools', 'seo_guidelines, seo_score (cả bản nháp), seo_update trên DB thật, đã rollback');

exit($report->summary());
