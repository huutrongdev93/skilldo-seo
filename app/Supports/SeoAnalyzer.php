<?php
namespace SkdSeo\Supports;

use Illuminate\Support\Str;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;

/*
|--------------------------------------------------------------------------
| Chấm điểm SEO phía máy chủ (bản PHP của JS trong views/point/point.blade.php)
|--------------------------------------------------------------------------
| Dùng cho tool MCP `seo_score`: AI viết bài xong tự chấm, sửa tới khi đạt, như
| biên tập viên nhìn metabox. Hai bản phải chấm GIỐNG NHAU:
|  - collect()  ↔ collect() của JS  (đọc tiêu đề / mô tả / nội dung như Google thấy)
|  - evaluate() ↔ evaluate() của JS (từng tiêu chí, cùng ngưỡng, cùng câu báo)
|  - score()    ↔ render() của JS   (điểm có trọng số, làm tròn)
| Sửa một nhánh chấm ở JS thì sửa ở đây, và ngược lại. `tool/test-seo-point.php`
| chạy cùng dữ liệu mẫu qua metabox thật (Chromium headless) và qua lớp này rồi
| so từng tiêu chí; lệch là đỏ.
|
| Khác biệt duy nhất có chủ đích: độ rộng tiêu đề. JS đo bằng canvas (font Arial
| thật của máy), PHP tra bảng độ rộng ký tự Arial (ARIAL_WIDTHS). Sai số vài px,
| chỉ ảnh hưởng tiêu đề nằm sát ngưỡng 580px.
*/
class SeoAnalyzer
{
    /** Tiêu chí cần trình soạn thảo nội dung (form không có editor thì bỏ, như JS). */
    const CONTENT_CRITERIA = [
        'keywordIn10Percent', 'keywordInContent', 'keywordInSubheadings', 'keywordStuffing',
        'contentNotThin', 'noH1InContent', 'contentHasSubheadings', 'contentHasShortParagraphs',
        'contentHasLists', 'linksHasInternal', 'linksHasExternal', 'imagesHaveAlt',
    ];

    /** Câu báo khi ĐẠT (JS: messageSuccess). Chưa đạt thì dùng câu gợi ý của SeoPoint::listCriteria(). */
    const MESSAGE_SUCCESS = [
        'keywordNotUsed'           => 'Đã đặt từ khóa chính.',
        'keywordUnique'            => 'Từ khóa chính chưa được dùng cho nội dung nào khác.',
        'keywordInTitle'           => 'Tiêu đề có chứa từ khóa chính.',
        'titleStartWithKeyword'    => 'Từ khóa chính nằm ở nửa đầu tiêu đề.',
        'keywordInMetaDescription' => 'Mô tả có chứa từ khóa chính.',
        'keywordInPermalink'       => 'Đường dẫn có chứa từ khóa chính.',
        'keywordIn10Percent'       => 'Từ khóa chính xuất hiện ngay ở đoạn mở đầu.',
        'keywordInContent'         => 'Nội dung có sử dụng từ khóa chính.',
        'keywordInSubheadings'     => 'Từ khóa chính có trong tiêu đề phụ.',
        'noH1InContent'            => 'Nội dung không có thẻ H1 (giao diện đã dùng tiêu đề làm H1).',
        'contentHasLists'          => 'Nội dung có danh sách hoặc bảng.',
        'linksHasInternal'         => 'Nội dung có liên kết nội bộ.',
        'linksHasExternal'         => 'Nội dung có dẫn nguồn ra website bên ngoài.',
        'hasFeaturedImage'         => 'Đã có ảnh đại diện.',
    ];

    /**
     * Độ rộng ký tự của Arial (đơn vị 1/1000 em, theo bảng AFM chuẩn). Chữ có dấu
     * tiếng Việt lấy độ rộng chữ gốc (dấu nằm trên/dưới, không chiếm thêm chiều ngang).
     */
    const ARIAL_WIDTHS = [
        ' ' => 278, '!' => 278, '"' => 355, '#' => 556, '$' => 556, '%' => 889, '&' => 667, "'" => 191,
        '(' => 333, ')' => 333, '*' => 389, '+' => 584, ',' => 278, '-' => 333, '.' => 278, '/' => 278,
        '0' => 556, '1' => 556, '2' => 556, '3' => 556, '4' => 556, '5' => 556, '6' => 556, '7' => 556,
        '8' => 556, '9' => 556, ':' => 278, ';' => 278, '<' => 584, '=' => 584, '>' => 584, '?' => 556,
        '@' => 1015, 'A' => 667, 'B' => 667, 'C' => 722, 'D' => 722, 'E' => 667, 'F' => 611, 'G' => 778,
        'H' => 722, 'I' => 278, 'J' => 500, 'K' => 667, 'L' => 556, 'M' => 833, 'N' => 722, 'O' => 778,
        'P' => 667, 'Q' => 778, 'R' => 722, 'S' => 667, 'T' => 611, 'U' => 722, 'V' => 667, 'W' => 944,
        'X' => 667, 'Y' => 667, 'Z' => 611, '[' => 278, '\\' => 278, ']' => 278, '^' => 469, '_' => 556,
        '`' => 333, 'a' => 556, 'b' => 556, 'c' => 500, 'd' => 556, 'e' => 556, 'f' => 278, 'g' => 556,
        'h' => 556, 'i' => 222, 'j' => 222, 'k' => 500, 'l' => 222, 'm' => 833, 'n' => 556, 'o' => 556,
        'p' => 556, 'q' => 556, 'r' => 333, 's' => 500, 't' => 278, 'u' => 556, 'v' => 500, 'w' => 722,
        'x' => 500, 'y' => 500, 'z' => 500, '{' => 334, '|' => 260, '}' => 334, '~' => 584,
        'đ' => 556, 'Đ' => 722, 'ư' => 600, 'Ư' => 780, 'ơ' => 600, 'Ơ' => 820,
        '–' => 556, '—' => 1000, '…' => 1000, '“' => 333, '”' => 333, '‘' => 222, '’' => 222, '·' => 278,
    ];

    /**
     * @param array{keyword?: string, title?: string, seo_title?: string, seo_description?: string,
     *              excerpt?: string, content?: string|null, slug?: string, image?: string|null} $input
     *        `content` = null: form không có trình soạn thảo nội dung (bỏ tiêu chí nội dung);
     *        `image` = null: form không có ô ảnh đại diện (bỏ hasFeaturedImage).
     * @param array{brand?: string, separator?: string, domain?: string, settings?: array,
     *              unique?: string[]|null} $context `unique` = tên các nội dung khác đang dùng
     *        cùng từ khóa; null = chưa kiểm (coi như đạt, giống JS lúc chờ ajax).
     *
     * @return array{score: int, criteria: array<string, array{passed: bool, message: string, weight: int|float}>}
     */
    public static function analyze(string $module, array $input, array $context = []): array
    {
        $context += [
            'brand'     => static::brand(),
            'separator' => (string)apply_filters('seo_title_separator', ' | '),
            'domain'    => Url::base(),
            'settings'  => SeoPoint::settings($module),
            'unique'    => null,
        ];

        $context['settings'] = array_merge(['minWords' => 300, 'longWords' => 300], (array)$context['settings']);

        $keys = array_keys(SeoPoint::criteria($module));

        $hasContent = array_key_exists('content', $input) && $input['content'] !== null;

        $hasImage = array_key_exists('image', $input) && $input['image'] !== null;

        $keys = array_values(array_filter($keys, fn($key) =>
            ($hasContent || !in_array($key, static::CONTENT_CRITERIA, true)) && ($hasImage || $key !== 'hasFeaturedImage')));

        $data = static::collect($input, $context);

        $results = static::evaluate($data, $context);

        return static::score($keys, $results, SeoPoint::weights($module), SeoPoint::criteria($module));
    }

    /** Tên thương hiệu ghép sau tiêu đề, cùng cách AdminPoint::metaBox() đưa xuống JS. */
    public static function brand(): string
    {
        return trim((string)apply_filters('seo_title_brand', trim(Str::clear((string)Option::get('general_label', '')))));
    }

    //------------------------------------------------------------------ đọc dữ liệu (JS: collect)

    public static function collect(array $input, array $context): array
    {
        $rawTitle = trim((string)($input['title'] ?? ''));

        $rawSeoTitle = trim((string)($input['seo_title'] ?? ''));

        $seoTitle = $rawSeoTitle !== '' ? $rawSeoTitle : $rawTitle;

        $rawDescription = trim((string)($input['seo_description'] ?? ''));

        $description = $rawDescription !== '' ? $rawDescription : static::stripHtml((string)($input['excerpt'] ?? ''));

        $slug = trim((string)($input['slug'] ?? ''));

        if ($slug === '') $slug = static::changeToSlug($rawTitle);

        $html = (string)($input['content'] ?? '');

        $text = static::stripHtml($html);

        return [
            'keyword'                => static::norm((string)($input['keyword'] ?? '')),
            'seoTitle'               => $seoTitle,
            'seoTitleFromH1'         => $rawSeoTitle === '',
            'documentTitle'          => static::documentTitle($seoTitle, $context['brand'], $context['separator']),
            'description'            => $description,
            'descriptionFromExcerpt' => $rawDescription === '' && $description !== '',
            'slug'                   => $slug,
            'url'                    => $context['domain'].$slug,
            'html'                   => $html,
            'dom'                    => static::parseHtml($html),
            'text'                   => static::norm($text),
            'words'                  => static::countWords($text),
            'image'                  => trim((string)($input['image'] ?? '')),
        ];
    }

    //------------------------------------------------------------------ chấm (JS: evaluate)

    /**
     * @return array<string, array{0: bool, 1?: string|null}>
     */
    public static function evaluate(array $d, array $context): array
    {
        $r = [];

        $kw = $d['keyword'];

        $hasKw = mb_strlen($kw) > 0;

        $noKeyword = [false, 'Chưa đặt từ khóa chính.'];

        $titleNorm = static::norm($d['seoTitle']);

        $titleSource = $d['seoTitleFromH1'] ? ' (đang lấy từ ô Tiêu đề vì chưa điền Meta title)' : '';

        $settings = $context['settings'];

        //Từ khóa
        $r['keywordNotUsed'] = [$hasKw];

        $unique = $context['unique'];

        if (!$hasKw) {
            $r['keywordUnique'] = $noKeyword;
        } elseif ($unique === null) {
            $r['keywordUnique'] = [true, 'Đang kiểm tra từ khóa trùng...'];
        } elseif (count($unique)) {
            $r['keywordUnique'] = [false, 'Từ khóa đã được dùng cho: '.implode(', ', array_map(fn($t) => '"'.htmlspecialchars((string)$t, ENT_NOQUOTES).'"', $unique)).'. Hai trang cùng nhắm một từ khóa sẽ tranh thứ hạng của nhau.'];
        } else {
            $r['keywordUnique'] = [true];
        }

        $inTitle = static::has($titleNorm, $kw);

        $r['keywordInTitle'] = $hasKw ? [$inTitle, $inTitle ? null : 'Thêm từ khóa chính vào tiêu đề'.$titleSource.'.'] : $noKeyword;

        if (!$hasKw) {
            $r['titleStartWithKeyword'] = $noKeyword;
        } else {
            $pos = mb_strpos($titleNorm, $kw);
            $r['titleStartWithKeyword'] = [$pos !== false && $pos <= mb_strlen($titleNorm) / 2];
        }

        $r['keywordInMetaDescription'] = $hasKw ? [static::has(static::norm($d['description']), $kw)] : $noKeyword;

        $r['keywordInPermalink'] = $hasKw ? [str_contains($d['slug'], static::changeToSlug($kw))] : $noKeyword;

        if (!$hasKw) {
            $r['keywordIn10Percent'] = $noKeyword;
            $r['keywordInContent'] = $noKeyword;
            $r['keywordInSubheadings'] = $noKeyword;
            $r['keywordStuffing'] = $noKeyword;
        } else {
            $pos = mb_strpos($d['text'], $kw);

            $r['keywordInContent'] = [$pos !== false];

            $r['keywordIn10Percent'] = [$pos !== false && $pos <= max(200, mb_strlen($d['text']) * 0.1)];

            $inHeading = false;
            foreach (static::find($d['dom'], ['h2', 'h3', 'h4', 'h5', 'h6']) as $heading) {
                if (static::has(static::norm(static::nodeText($heading)), $kw)) { $inHeading = true; break; }
            }
            $r['keywordInSubheadings'] = [$inHeading];

            $count = static::occurrences($d['text'], $kw);
            $per100 = $d['words'] ? ($count / $d['words']) * 100 : 0;
            $stuffed = $count >= 4 && $per100 > 3;
            $r['keywordStuffing'] = [!$stuffed, 'Từ khóa xuất hiện '.$count.' lần trong '.$d['words'].' từ ('.static::toFixed($per100, 1).' lần/100 từ)'.($stuffed ? ', quá dày. Hãy viết tự nhiên hơn hoặc dùng từ đồng nghĩa.' : '.')];
        }

        //Tiêu đề
        $titleLen = mb_strlen($d['documentTitle']);
        $titleWidth = static::textWidth($d['documentTitle'], 20);
        $brandNote = ($d['documentTitle'] !== trim($d['seoTitle'])) ? ' (đã tính cả tên thương hiệu "'.$context['brand'].'" hệ thống tự ghép vào)' : '';
        if ($titleLen < 30) {
            $r['lengthTitle'] = [false, 'Tiêu đề hiển thị có '.$titleLen.' ký tự'.$brandNote.', hơi ngắn. Nên từ 30 ký tự trở lên'.$titleSource.'.'];
        } elseif ($titleWidth > 580) {
            $r['lengthTitle'] = [false, 'Tiêu đề hiển thị có '.$titleLen.' ký tự'.$brandNote.', Google sẽ cắt bớt phần cuối. Hãy rút ngắn'.$titleSource.'.'];
        } else {
            $r['lengthTitle'] = [true, 'Tiêu đề hiển thị có '.$titleLen.' ký tự'.$brandNote.', vừa đủ, không bị cắt.'];
        }

        //Mô tả
        $descLen = mb_strlen($d['description']);
        $descSource = $d['descriptionFromExcerpt'] ? ' (đang lấy từ mô tả ngắn vì chưa điền Meta description)' : '';
        if ($descLen === 0) {
            $r['lengthMetaDescription'] = [false, 'Chưa có mô tả. Hãy điền Meta description hoặc mô tả ngắn, nếu không Google sẽ tự trích một đoạn bất kỳ.'];
        } elseif ($descLen < 110) {
            $r['lengthMetaDescription'] = [false, 'Mô tả có '.$descLen.' ký tự'.$descSource.', hơi ngắn. Nên từ 110 đến 160 ký tự.'];
        } elseif ($descLen > 160) {
            $r['lengthMetaDescription'] = [false, 'Mô tả có '.$descLen.' ký tự'.$descSource.', Google sẽ cắt bớt. Nên từ 110 đến 160 ký tự.'];
        } else {
            $r['lengthMetaDescription'] = [true, 'Mô tả có '.$descLen.' ký tự'.$descSource.', vừa đủ.'];
        }

        //Đường dẫn: ngắn là tốt, không có ngưỡng tối thiểu
        $urlLen = mb_strlen((string)preg_replace('#^https?://#', '', $d['url']));
        $r['lengthPermalink'] = [$urlLen <= 75, 'Đường dẫn có '.$urlLen.' ký tự'.($urlLen > 75 ? ', khá dài. Hãy rút gọn, bỏ bớt từ thừa.' : ', gọn gàng.')];

        //Nội dung
        $dom = $d['dom'];

        $r['contentNotThin'] = [$d['words'] >= $settings['minWords'], 'Nội dung có '.$d['words'].' từ'.($d['words'] >= $settings['minWords'] ? '.' : ', khá mỏng. Nên có từ '.$settings['minWords'].' từ trở lên với thông tin thật sự hữu ích.')];

        $h1 = count(static::find($dom, ['h1']));
        $r['noH1InContent'] = [$h1 === 0, $h1 ? 'Nội dung có '.$h1.' thẻ H1. Giao diện đã in tiêu đề làm H1, hãy đổi các thẻ này thành H2.' : null];

        $isLong = $d['words'] >= $settings['longWords'];

        $subheadings = count(static::find($dom, ['h2', 'h3']));
        $r['contentHasSubheadings'] = [!$isLong || $subheadings > 0, $isLong ? ($subheadings ? 'Nội dung có '.$subheadings.' tiêu đề phụ H2/H3.' : null) : 'Nội dung ngắn, chưa cần tiêu đề phụ.'];

        $longParagraph = 0;
        foreach (static::find($dom, ['p']) as $paragraph) {
            if (static::countWords(static::collapse(static::nodeText($paragraph))) > 150) $longParagraph++;
        }
        if ($d['words'] === 0) {
            $r['contentHasShortParagraphs'] = [false];
        } elseif ($longParagraph) {
            $r['contentHasShortParagraphs'] = [false, 'Có '.$longParagraph.' đoạn văn dài hơn 150 từ. Hãy tách nhỏ để dễ đọc trên điện thoại.'];
        } else {
            $r['contentHasShortParagraphs'] = [true, 'Các đoạn văn đều ngắn gọn, dễ đọc.'];
        }

        $lists = count(static::find($dom, ['ul', 'ol', 'table']));
        $r['contentHasLists'] = [!$isLong || $lists > 0, $isLong ? null : 'Nội dung ngắn, chưa cần danh sách hoặc bảng.'];

        $domainHost = static::linkHost($context['domain'], $context['domain']);
        $internal = 0;
        $external = 0;
        foreach (static::find($dom, ['a']) as $link) {
            if (!$link->hasAttribute('href')) continue;
            $href = trim($link->getAttribute('href'));
            if ($href === '' || preg_match('/^(#|mailto:|tel:|javascript:)/i', $href)) continue;
            $host = static::linkHost($href, $context['domain']);
            if ($host === '') continue;
            if ($host === $domainHost) $internal++; else $external++;
        }
        $r['linksHasInternal'] = [$internal > 0, $internal ? 'Nội dung có '.$internal.' liên kết nội bộ.' : null];
        $r['linksHasExternal'] = [$external > 0, $external ? 'Nội dung có '.$external.' liên kết ra website bên ngoài.' : null];

        $images = static::find($dom, ['img']);
        $missingAlt = count(array_filter($images, fn($img) => trim($img->getAttribute('alt')) === ''));
        if (!$images) {
            $r['imagesHaveAlt'] = [true, 'Nội dung không có hình ảnh.'];
        } elseif ($missingAlt) {
            $r['imagesHaveAlt'] = [false, $missingAlt.'/'.count($images).' ảnh trong nội dung chưa có alt. Alt nên mô tả đúng nội dung ảnh, không cần nhồi từ khóa.'];
        } else {
            $r['imagesHaveAlt'] = [true, 'Cả '.count($images).' ảnh trong nội dung đều có alt.'];
        }

        $r['hasFeaturedImage'] = [$d['image'] !== ''];

        return $r;
    }

    //------------------------------------------------------------------ điểm (JS: render)

    /**
     * @param string[] $keys tiêu chí đang chấm
     */
    public static function score(array $keys, array $results, array $weights, array $labels): array
    {
        $total = 0;

        $passed = 0;

        $criteria = [];

        foreach ($keys as $key)
        {
            //Tiêu chí do plugin khác thêm mà không có nhánh chấm: không tính
            if (!array_key_exists($key, $results)) continue;

            $weight = (isset($weights[$key]) && is_numeric($weights[$key])) ? $weights[$key] : 1;

            $ok = ($results[$key][0] ?? false) === true;

            $total += $weight;

            if ($ok) $passed += $weight;

            $message = $results[$key][1] ?? null;

            if ($message === null || $message === '')
            {
                $message = $ok ? (static::MESSAGE_SUCCESS[$key] ?? ($labels[$key] ?? $key)) : ($labels[$key] ?? $key);
            }

            $criteria[$key] = ['passed' => $ok, 'message' => (string)$message, 'weight' => $weight];
        }

        return [
            // Math.round của JS: làm tròn nửa lên (số dương giống round() của PHP).
            'score'    => $total ? (int)round(($passed / $total) * 100) : 0,
            'criteria' => $criteria,
        ];
    }

    //------------------------------------------------------------------ helpers (giống JS)

    /** JS: (value).normalize('NFC').toLowerCase().replace(/\s+/g, ' ').trim() */
    public static function norm(string $value): string
    {
        if (class_exists(\Normalizer::class))
        {
            $value = (string)\Normalizer::normalize($value, \Normalizer::FORM_C);
        }

        return trim(static::collapse(mb_strtolower($value, 'UTF-8')));
    }

    protected static function collapse(string $value): string
    {
        // \s của JS gồm cả NBSP và khoảng trắng Unicode.
        return (string)preg_replace('/[\s\x{00A0}\x{FEFF}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u', ' ', $value);
    }

    protected static function has(string $text, string $keyword): bool
    {
        return $keyword !== '' && mb_strpos($text, $keyword) !== false;
    }

    public static function parseHtml(string $html): \DOMDocument
    {
        $dom = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);

        libxml_clear_errors();

        libxml_use_internal_errors($previous);

        return $dom;
    }

    /** JS: parseHtml(html).textContent rồi gộp khoảng trắng + trim. */
    public static function stripHtml(string $html): string
    {
        $body = static::parseHtml($html)->getElementsByTagName('body')->item(0);

        return trim(static::collapse($body ? $body->textContent : ''));
    }

    protected static function nodeText(\DOMNode $node): string
    {
        return (string)$node->textContent;
    }

    /**
     * Các phần tử theo tên thẻ, theo thứ tự tài liệu (như jQuery find('h2,h3')).
     *
     * @return \DOMElement[]
     */
    protected static function find(\DOMDocument $dom, array $tags): array
    {
        $xpath = new \DOMXPath($dom);

        $query = '//body//*['.implode(' or ', array_map(fn($t) => 'self::'.$t, $tags)).']';

        $result = [];

        foreach ($xpath->query($query) ?: [] as $node)
        {
            $result[] = $node;
        }

        return $result;
    }

    public static function countWords(string $text): int
    {
        if ($text === '') return 0;

        return count(array_filter(preg_split('/[\s\x{00A0}\x{FEFF}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u', $text) ?: [], fn($w) => $w !== ''));
    }

    protected static function occurrences(string $text, string $keyword): int
    {
        if ($keyword === '') return 0;

        $count = 0;

        $offset = 0;

        $length = mb_strlen($keyword);

        while (($pos = mb_strpos($text, $keyword, $offset)) !== false)
        {
            $count++;

            $offset = $pos + $length;
        }

        return $count;
    }

    /** Number.prototype.toFixed */
    protected static function toFixed(float $value, int $digits): string
    {
        return number_format($value, $digits, '.', '');
    }

    /** Độ rộng hiển thị (px) theo bảng Arial; JS đo bằng canvas `20px Arial`. */
    public static function textWidth(string $text, int $fontSize = 20): float
    {
        $units = 0;

        foreach (mb_str_split($text) as $char)
        {
            if (isset(static::ARIAL_WIDTHS[$char]))
            {
                $units += static::ARIAL_WIDTHS[$char];
                continue;
            }

            // Bỏ dấu tiếng Việt: độ rộng của chữ gốc.
            $base = class_exists(\Normalizer::class)
                ? (string)preg_replace('/\p{Mn}+/u', '', (string)\Normalizer::normalize($char, \Normalizer::FORM_D))
                : $char;

            $units += static::ARIAL_WIDTHS[$base] ?? 556;
        }

        return $units * $fontSize / 1000;
    }

    public static function documentTitle(string $title, string $brand, string $separator): string
    {
        $brand = trim($brand);

        $title = trim($title);

        if ($brand === '') return $title;

        if ($title === '') return $brand;

        if (mb_strpos(mb_strtolower($title), mb_strtolower($brand)) !== false) return $title;

        return $title.($separator !== '' ? $separator : ' | ').$brand;
    }

    /** JS changeToSlug(): bỏ dấu, giữ a-z0-9, khoảng trắng → "-". */
    public static function changeToSlug(string $title): string
    {
        $slug = mb_strtolower($title, 'UTF-8');

        $map = [
            'a' => 'á|à|ả|ạ|ã|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ',
            'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
            'i' => 'í|ì|ỉ|ĩ|ị',
            'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
            'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
            'y' => 'ý|ỳ|ỷ|ỹ|ỵ',
            'd' => 'đ',
        ];

        foreach ($map as $ascii => $pattern)
        {
            $slug = (string)preg_replace('/'.$pattern.'/iu', $ascii, $slug);
        }

        $slug = (string)preg_replace('/[^a-z0-9\s-]/u', '', $slug);

        $slug = (string)preg_replace('/\s+/u', '-', $slug);

        $slug = (string)preg_replace('/-+/', '-', $slug);

        return (string)preg_replace('/^-|-$/', '', $slug);
    }

    /** JS: new URL(href, domain).host (có cổng nếu khác mặc định), lỗi thì ''. */
    protected static function linkHost(string $href, string $domain): string
    {
        if (str_starts_with($href, '//'))
        {
            $href = (parse_url($domain, PHP_URL_SCHEME) ?: 'https').':'.$href;
        }
        elseif (!preg_match('/^[a-z][a-z0-9+.\-]*:/i', $href))
        {
            // Đường dẫn tương đối: thuộc tên miền của site.
            $href = $domain;
        }

        $parts = parse_url($href);

        if (!$parts || empty($parts['host'])) return '';

        $host = strtolower($parts['host']);

        $scheme = strtolower($parts['scheme'] ?? '');

        if (!empty($parts['port']) && !(($scheme === 'http' && $parts['port'] == 80) || ($scheme === 'https' && $parts['port'] == 443)))
        {
            $host .= ':'.$parts['port'];
        }

        return $host;
    }
}
