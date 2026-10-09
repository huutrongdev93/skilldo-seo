<?php
namespace SkdSeo\Mcp;

use McpServer\Protocol\ToolResult;
use McpServer\Supports\McpContext;
use SkdSeo\Supports\SeoAnalyzer;
use SkdSeo\Supports\SeoPoint;
use SkillDo\Cms\Support\Url;

/**
 * Ghi các thiết lập SEO của metabox: từ khóa chính, robots, canonical. Đi qua
 * đúng setter của module như AdminPoint::save(), chỉ đổi trường được gửi.
 *
 * Schema thủ công (JSON-LD tự viết) cố ý không mở cho AI: schema tự động của hệ
 * thống đã đúng cho hầu hết trang, viết sai thì Google báo lỗi cấu trúc.
 */
class SeoUpdateTool extends SeoTool
{
    const ROBOTS = ['noFollow', 'noArchive', 'noImage', 'noSnippet'];

    public function name(): string
    {
        return 'seo_update';
    }

    public function title(): string
    {
        return 'Update SEO settings';
    }

    public function description(): string
    {
        return 'Set the SEO settings of a post, page, product or category: focus keyword, search engine indexing, robots flags and canonical URL. Only the fields you send change. '
            .'Returns the new SEO score. Title, meta title/description and content are changed with post_update / page_update / product_update, not here.';
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function inputSchema(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'type'          => $this->typeSchema(),
                'id'            => ['type' => 'integer', 'minimum' => 1],
                'focus_keyword' => ['type' => 'string', 'maxLength' => 191, 'description' => 'The main phrase this page should rank for. Empty string removes it.'],
                'index'         => ['type' => 'boolean', 'description' => 'false = ask search engines not to index this page (noindex). Use with care.'],
                'robots'        => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => static::ROBOTS], 'description' => 'Extra robots flags; replaces the current list, [] clears it'],
                'canonical'     => ['type' => 'string', 'maxLength' => 500, 'description' => 'Canonical URL when this content duplicates another page; empty string removes it'],
            ],
            'required'             => ['type', 'id'],
            'additionalProperties' => false,
        ];
    }

    protected function run(array $arguments, McpContext $context): ToolResult
    {
        $target = $this->target((string)$arguments['type'], (int)$arguments['id'], $context, 'edit');

        if (is_string($target)) return $this->error($target);

        $class = $target['class'];

        $id = (int)$target['object']->id;

        $updated = [];

        if (array_key_exists('focus_keyword', $arguments))
        {
            $keyword = trim(strip_tags((string)$arguments['focus_keyword']));

            $class->setFocusKeyword($id, $keyword);

            $updated[] = 'focus_keyword';
        }

        if (array_key_exists('index', $arguments) || array_key_exists('robots', $arguments))
        {
            // Cùng dạng AdminPoint::save(): ['index' => yes|no, 'robots' => [...]]; giữ phần không gửi.
            /*
            | Chỉ giữ đúng hai khoá. Bản ghi chưa có robots thì getRobots() trả chuỗi
            | rỗng; (array)'' = [0 => ''] và mảng có khoá 0 bị lớp metadata hiểu là
            | danh sách nhiều giá trị, đọc lại chỉ còn ''.
            */
            $current = $class->getRobots($id);

            $current = is_array($current) ? $current : [];

            $robots = [
                'index'  => ($current['index'] ?? 'yes') === 'no' ? 'no' : 'yes',
                'robots' => array_values(array_intersect(static::ROBOTS, (array)($current['robots'] ?? []))),
            ];

            if (array_key_exists('index', $arguments))
            {
                $robots['index'] = $arguments['index'] ? 'yes' : 'no';

                $updated[] = 'index';
            }

            if (array_key_exists('robots', $arguments))
            {
                $robots['robots'] = array_values(array_intersect(static::ROBOTS, (array)$arguments['robots']));

                $updated[] = 'robots';
            }

            $class->setRobots($id, $robots);
        }

        if (array_key_exists('canonical', $arguments))
        {
            $canonical = trim((string)$arguments['canonical']);

            if ($canonical !== '' && !preg_match('#^https?://#i', $canonical) && !str_starts_with($canonical, '/'))
            {
                return $this->error('canonical must be an absolute http(s) URL or a path starting with "/".');
            }

            // Như AdminPoint::save(): URL của chính site lưu dạng tương đối.
            $class->setCanonical($id, $canonical === '' ? '' : str_replace(Url::base(), '', $canonical));

            $updated[] = 'canonical';
        }

        if ($updated === [])
        {
            return $this->error('Nothing to update: send focus_keyword, index, robots or canonical.');
        }

        $input = $this->input($target);

        $unique = $input['keyword'] !== '' ? array_values(SeoPoint::duplicateKeyword($target['module'], $input['keyword'], $id)) : [];

        $result = SeoAnalyzer::analyze($target['module'], $input, ['unique' => $unique]);

        return ToolResult::json([
            'type'          => (string)$arguments['type'],
            'id'            => $id,
            'updated'       => $updated,
            'focus_keyword' => $input['keyword'],
            'score'         => $result['score'],
            'rating'        => static::rating($result['score']),
            'failed'        => array_values(array_map(fn($c) => $c['key'].': '.$c['message'], array_filter($this->present($result['criteria']), fn($c) => !$c['passed']))),
        ]);
    }
}
