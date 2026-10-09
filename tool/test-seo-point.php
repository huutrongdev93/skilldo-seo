<?php
/*
|--------------------------------------------------------------------------
| Kiểm thử chấm điểm SEO: JS (metabox thật) và PHP (SeoAnalyzer) phải giống nhau
|--------------------------------------------------------------------------
| Chạy:  TEST_BASE_URL=http://s8.vn/ php plugins/skd-seo/tool/test-seo-point.php
|
| Với mỗi bài mẫu:
|  1. render metabox THẬT (AdminPoint::metaBox → views/point/point.blade.php) kèm
|     các ô form giả (#vi_title, #vi_content…) và jQuery, ghi ra một file HTML;
|  2. mở bằng Chromium headless (bản Playwright cài sẵn, hoặc CHROME_PATH), đọc
|     kết quả JS đã chấm (class test-success/test-fail + điểm) ra từ DOM;
|  3. chấm cùng dữ liệu bằng SeoAnalyzer::analyze() rồi so từng tiêu chí + điểm.
|
| Không có Chromium thì phần so với JS bị bỏ qua (báo skip), phần PHP vẫn chạy.
| Từ khóa trùng (ajax) không kiểm ở đây: JS chờ ajax coi như đạt, PHP truyền unique = null.
*/

use SkdSeo\Supports\SeoAnalyzer;

if (PHP_SAPI !== 'cli') exit("Chỉ chạy ở CLI.\n");

unset($_SERVER['argv'], $_SERVER['argc']);

$root = dirname(__DIR__, 3);

/** @var \SkillDo\Application $app */
$app = require $root . '/tests/bootstrap.php';

require_once $root . '/tests/lib/Reporter.php';

$report = new Reporter('skd-seo · chấm điểm SEO (JS ↔ PHP)');

$check = function (string $where, bool $ok, string $detail = '') use ($report)
{
    $ok ? $report->pass() : $report->fail($where, $detail ?: 'sai');
};

/*
|--------------------------------------------------------------------------
| Bài mẫu
|--------------------------------------------------------------------------
*/
$sentence = fn(string $s, int $n) => implode(' ', array_fill(0, $n, $s));

$longBody = '<p>Máy lọc nước giúp gia đình có nguồn nước sạch mỗi ngày. '.$sentence('Bài viết hướng dẫn cách chọn thiết bị phù hợp với nhu cầu sử dụng thực tế của từng nhà.', 4).'</p>'
    .'<h2>Cách chọn máy lọc nước cho gia đình</h2>'
    .'<p>'.$sentence('Hãy xem công suất, số lõi lọc và chi phí thay lõi trước khi mua.', 6).'</p>'
    .'<ul><li>Công suất lọc</li><li>Số lõi lọc</li><li>Chi phí thay lõi</li></ul>'
    .'<h3>Lưu ý khi lắp đặt</h3>'
    .'<p>'.$sentence('Đặt máy ở nơi khô ráo, gần nguồn nước và ổ điện.', 6).' Xem thêm <a href="/san-pham">sản phẩm</a> và <a href="https://who.int/water">khuyến nghị của WHO</a>.</p>'
    .'<p><img src="/storage/uploads/source/a.webp" alt="Máy lọc nước đặt dưới bồn rửa"></p>'
    .'<p>'.$sentence('Bảo dưỡng định kỳ giúp nước luôn sạch và máy bền lâu hơn.', 6).'</p>';

$fixtures = [
    'trống' => ['post', [
        'keyword' => '', 'title' => '', 'seo_title' => '', 'seo_description' => '', 'excerpt' => '', 'content' => '', 'slug' => '', 'image' => '',
    ]],
    'bài tốt' => ['post', [
        'keyword' => 'Máy lọc nước', 'title' => 'Máy lọc nước: cách chọn đúng cho gia đình năm 2026', 'seo_title' => '',
        'seo_description' => 'Máy lọc nước nào phù hợp với gia đình bạn? Hướng dẫn chọn theo công suất, số lõi lọc và chi phí thay lõi, kèm lưu ý lắp đặt.',
        'excerpt' => '', 'content' => $longBody, 'slug' => 'may-loc-nuoc-cach-chon', 'image' => 'news/may-loc.webp',
    ]],
    'nhồi từ khóa' => ['post', [
        'keyword' => 'giày chạy bộ', 'title' => 'Giày chạy bộ', 'seo_title' => '', 'seo_description' => '', 'excerpt' => '<p>Giày chạy bộ giá tốt &amp; bền</p>',
        'content' => '<p>'.$sentence('giày chạy bộ', 12).' rẻ</p>', 'slug' => '', 'image' => '',
    ]],
    'H1, đoạn dài, ảnh thiếu alt' => ['post', [
        'keyword' => 'du lịch Đà Lạt', 'title' => 'Kinh nghiệm du lịch Đà Lạt tự túc trọn gói', 'seo_title' => 'Cẩm nang 3 ngày 2 đêm cho người đi lần đầu: du lịch Đà Lạt',
        'seo_description' => 'Ngắn.', 'excerpt' => '',
        'content' => '<h1>Du lịch Đà Lạt</h1><p>'.$sentence('Đà Lạt mùa này se lạnh, sáng sớm có sương mù bao phủ khắp các con dốc quanh hồ Xuân Hương.', 9).'</p><img src="/a.jpg"><img src="/b.jpg" alt=" ">',
        'slug' => 'kinh-nghiem-di-choi-o-mot-thanh-pho-cao-nguyen-mien-trung-viet-nam-tu-tuc-tron-goi', 'image' => '',
    ]],
    'tiêu đề dài, mô tả dài' => ['page', [
        'keyword' => 'bảng giá', 'title' => 'Thông tin chi tiết về các gói dịch vụ thiết kế website trọn gói và bảng giá', 'seo_title' => '',
        'seo_description' => $sentence('Mô tả quá dài vượt ngưỡng hiển thị.', 6), 'excerpt' => '', 'content' => '<p>Nội dung ngắn có bảng giá.</p>', 'slug' => '', 'image' => 'a.webp',
    ]],
    'từ khóa có ký tự đặc biệt + NFD' => ['post', [
        // "học" viết dạng tổ hợp (NFD): norm() phải đưa về NFC trước khi so.
        'keyword' => "C++ (h\u{006F}\u{0323}c) cơ bản", 'title' => 'Tự học C++ (học) cơ bản cho người mới bắt đầu', 'seo_title' => '',
        'seo_description' => '', 'excerpt' => '<p>Lộ trình C++ (học) cơ bản: biến, vòng lặp, hàm và con trỏ, kèm bài tập có lời giải chi tiết cho người mới.</p>',
        'content' => '<p>Bắt đầu với C++ (học) cơ bản.</p><h2>C++ (học) cơ bản là gì</h2><p><a href="mailto:a@b.c">Liên hệ</a> <a href="#muc-1">mục 1</a> <a href="//cplusplus.com/doc">tài liệu</a></p>',
        'slug' => 'tu-hoc-c-hoc-co-ban', 'image' => 'x.webp',
    ]],
    'sản phẩm (ngưỡng 150 từ)' => ['products', [
        'keyword' => 'bình giữ nhiệt', 'title' => 'Bình giữ nhiệt inox 500ml giữ nóng 12 giờ', 'seo_title' => '', 'seo_description' => '', 'excerpt' => '',
        'content' => '<p>'.$sentence('Bình giữ nhiệt bằng inox hai lớp, nắp kín, dễ mang theo đi làm và đi học.', 12).'</p>', 'slug' => 'binh-giu-nhiet-inox', 'image' => 'p.webp',
    ]],
    'form không có nội dung / ảnh' => ['post', [
        'keyword' => 'khuyến mãi', 'title' => 'Chương trình khuyến mãi tháng 10 cho khách hàng thân thiết', 'seo_title' => '', 'seo_description' => '', 'excerpt' => '',
        'content' => null, 'slug' => '', 'image' => null,
    ]],
    'tên thương hiệu ghép vào tiêu đề' => ['post', [
        'keyword' => 'cà phê', 'title' => 'Cà phê rang xay nguyên chất', 'seo_title' => '', 'seo_description' => '', 'excerpt' => '',
        'content' => '<p>Cà phê rang mộc.</p>', 'slug' => '', 'image' => '',
    ], 'Nhà Rang Cà Phê Đà Lạt Xanh'],
    'mô tả lấy từ excerpt có thực thể HTML' => ['post', [
        'keyword' => 'trà & bánh', 'title' => 'Hộp quà trà & bánh trung thu cao cấp', 'seo_title' => 'Hộp quà Trà & Bánh trung thu 2026 sang trọng',
        'seo_description' => '', 'excerpt' => '<p>Hộp quà <strong>trà &amp; bánh</strong> trung thu gồm 4 bánh nướng, 1 hộp trà ô long thượng hạng, túi giấy kraft và thiệp chúc mừng viết tay.</p>',
        'content' => '<p>Trà &amp; bánh cho mùa trăng.</p><table><tr><td>Bánh</td></tr></table>', 'slug' => 'hop-qua-tra-banh', 'image' => '',
    ]],
];

/*
|--------------------------------------------------------------------------
| Chromium
|--------------------------------------------------------------------------
*/
$chrome = getenv('CHROME_PATH') ?: null;

if (!$chrome)
{
    foreach (glob(getenv('LOCALAPPDATA').'/ms-playwright/chromium-*/chrome-win*/chrome.exe') ?: [] as $candidate)
    {
        $chrome = $candidate;
    }
}

$jquery = @file_get_contents($root.'/node_modules/jquery/dist/jquery.min.js');

$tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'skd-seo-point-'.bin2hex(random_bytes(4));

@mkdir($tmp);

$language = \SkillDo\Cms\Support\Language::default();

$brand = null;

add_filter('seo_title_brand', function ($value) use (&$brand) { return $brand ?? $value; }, 999);

$runJs = function (string $module, array $input) use ($chrome, $jquery, $tmp, $language): ?array
{
    if (!$chrome || !$jquery) return null;

    $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

    $titleId = in_array($module, ['post_categories', 'products_categories'], true) ? $language.'_name' : $language.'_title';

    $form = '<input id="'.$titleId.'" value="'.$esc($input['title']).'">'
        .'<input id="seo_title" value="'.$esc($input['seo_title']).'">'
        .'<textarea id="seo_description">'.$esc($input['seo_description']).'</textarea>'
        .'<textarea id="'.$language.'_excerpt">'.$esc($input['excerpt']).'</textarea>'
        .($input['content'] !== null ? '<textarea id="'.$language.'_content">'.$esc($input['content']).'</textarea>' : '')
        .'<input id="slug" value="'.$esc($input['slug']).'">'
        .($input['image'] !== null ? '<input id="image" value="'.$esc($input['image']).'">' : '');

    ob_start();

    \SkdSeo\Modules\Point\AdminPoint::metaBox(null, ['module' => $module]);

    $metabox = (string)ob_get_clean();

    $metabox = str_replace('id="seo_focus_keyword" value=""', 'id="seo_focus_keyword" value="'.$esc($input['keyword']).'"', $metabox);

    $capture = <<<'JS'
<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        var out = {score: parseInt($('#seo_point').text(), 10), criteria: {}};
        $('#seo-general li[key]').each(function () {
            var key = $(this).attr('key');
            out.criteria[key] = this.style.display === 'none' ? 'hidden' : $(this).hasClass('test-success');
        });
        document.body.setAttribute('data-result', JSON.stringify(out));
    }, 100);
});
</script>
JS;

    $html = '<!doctype html><html><head><meta charset="utf-8"><script>'.$jquery.'</script>'
        .'<script>var domain = '.json_encode(\SkillDo\Cms\Support\Url::base()).';</script></head><body>'
        .$form.$metabox.$capture.'</body></html>';

    $file = $tmp.DIRECTORY_SEPARATOR.md5($html).'.html';

    file_put_contents($file, $html);

    $command = escapeshellarg($chrome).' --headless=new --disable-gpu --no-sandbox --virtual-time-budget=3000 --dump-dom '.escapeshellarg('file:///'.str_replace('\\', '/', $file)).' 2>'.(DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null');

    $dom = (string)shell_exec($command);

    if (!preg_match('/data-result="([^"]*)"/', $dom, $m)) return ['error' => mb_substr($dom, 0, 300)];

    return json_decode(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
};

if (!$chrome || !$jquery)
{
    $report->skip('js', 'không tìm thấy Chromium (đặt CHROME_PATH) hoặc node_modules/jquery: chỉ chạy phần PHP');
}

foreach ($fixtures as $name => $fixture)
{
    [$module, $input] = $fixture;

    $brand = $fixture[2] ?? null;

    $php = SeoAnalyzer::analyze($module, $input, ['unique' => null]);

    $check('php · '.$name.' · có điểm', is_int($php['score']) && $php['score'] >= 0 && $php['score'] <= 100);

    $js = $runJs($module, $input);

    if ($js === null) continue;

    if (isset($js['error']) || !isset($js['criteria']))
    {
        $report->fail('js · '.$name, 'không đọc được kết quả JS: '.($js['error'] ?? json_encode($js)));
        continue;
    }

    $jsShown = array_keys(array_filter($js['criteria'], fn($v) => $v !== 'hidden'));

    $phpShown = array_keys($php['criteria']);

    sort($jsShown);
    sort($phpShown);

    $check('song song · '.$name.' · cùng bộ tiêu chí', $jsShown === $phpShown, 'JS: '.implode(',', $jsShown).' | PHP: '.implode(',', $phpShown));

    $diff = [];

    foreach ($php['criteria'] as $key => $result)
    {
        if (array_key_exists($key, $js['criteria']) && $js['criteria'][$key] !== 'hidden' && $js['criteria'][$key] !== $result['passed'])
        {
            $diff[] = $key.' (JS '.($js['criteria'][$key] ? 'đạt' : 'chưa').', PHP '.($result['passed'] ? 'đạt' : 'chưa').': '.$result['message'].')';
        }
    }

    $check('song song · '.$name.' · từng tiêu chí', $diff === [], implode('; ', $diff));

    $check('song song · '.$name.' · điểm', (int)$js['score'] === $php['score'], 'JS '.$js['score'].' ≠ PHP '.$php['score']);

    if (getenv('SEO_POINT_DEBUG'))
    {
        $passed = count(array_filter($php['criteria'], fn($c) => $c['passed']));

        fwrite(STDOUT, sprintf("  %-40s JS %3d | PHP %3d | %d/%d tiêu chí đạt\n", $name, $js['score'], $php['score'], $passed, count($php['criteria'])));
    }
}

$brand = null;

// Dọn file HTML tạm.
foreach (glob($tmp.DIRECTORY_SEPARATOR.'*.html') ?: [] as $file) @unlink($file);

@rmdir($tmp);

$report->section('point', count($fixtures).' bài mẫu chấm bằng metabox thật (Chromium) và SeoAnalyzer');

exit($report->summary());
