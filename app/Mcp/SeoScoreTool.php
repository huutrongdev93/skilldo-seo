<?php
namespace SkdSeo\Mcp;

use McpServer\Protocol\ToolResult;
use McpServer\Supports\McpContext;
use SkdSeo\Supports\SeoAnalyzer;
use SkdSeo\Supports\SeoPoint;

/**
 * Chấm điểm SEO một bản ghi, đúng như metabox trong admin (SeoAnalyzer = bản PHP
 * của JS metabox). Truyền thêm `draft` để chấm thử trước khi lưu.
 */
class SeoScoreTool extends SeoTool
{
    public function name(): string
    {
        return 'seo_score';
    }

    public function title(): string
    {
        return 'SEO score';
    }

    public function description(): string
    {
        return 'Score a saved post, page, product or category with the site\'s SEO checklist (same result as the admin SEO box): score 0-100, rating, and every criterion with pass/fail and a message saying what to fix. '
            .'Failed items come first, most important first. Pass "draft" to score changes BEFORE saving them (only the fields you send are replaced). '
            .'Aim for a score of 80 or more ("good").';
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        $draft = [];

        foreach (static::DRAFT_FIELDS as $field)
        {
            $draft[$field] = ['type' => 'string'];
        }

        $draft['keyword']['description'] = 'Focus keyword to test (the saved one is used when omitted)';
        $draft['content']['description'] = 'HTML';
        $draft['excerpt']['description'] = 'HTML';

        return [
            'type'                 => 'object',
            'properties'           => [
                'type'  => $this->typeSchema(),
                'id'    => ['type' => 'integer', 'minimum' => 1],
                'draft' => [
                    'type'                 => 'object',
                    'description'          => 'Optional unsaved values to score instead of the saved ones',
                    'properties'           => $draft,
                    'additionalProperties' => false,
                ],
            ],
            'required'             => ['type', 'id'],
            'additionalProperties' => false,
        ];
    }

    protected function run(array $arguments, McpContext $context): ToolResult
    {
        $target = $this->target((string)$arguments['type'], (int)$arguments['id'], $context, 'view');

        if (is_string($target)) return $this->error($target);

        $draft = (array)($arguments['draft'] ?? []);

        $input = $this->input($target, $draft);

        $id = (int)$target['object']->id;

        $unique = $input['keyword'] !== ''
            ? array_values(SeoPoint::duplicateKeyword($target['module'], $input['keyword'], $id))
            : [];

        $result = SeoAnalyzer::analyze($target['module'], $input, ['unique' => $unique]);

        $failed = count(array_filter($result['criteria'], fn($c) => !$c['passed']));

        $robots = $target['class']->getRobots($id);

        return ToolResult::json([
            'type'          => (string)$arguments['type'],
            'id'            => $id,
            'draft'         => $draft !== [],
            'score'         => $result['score'],
            'rating'        => static::rating($result['score']),
            'focus_keyword' => $input['keyword'],
            'failed'        => $failed,
            'criteria'      => $this->present($result['criteria']),
            'robots'        => [
                'index'  => ($robots['index'] ?? 'yes') !== 'no',
                'robots' => array_values((array)($robots['robots'] ?? [])),
            ],
            'canonical'     => (string)$target['class']->getCanonical($id),
        ]);
    }
}
