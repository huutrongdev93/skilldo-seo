<?php
namespace SkdSeo\Mcp;

use McpServer\Protocol\ToolResult;
use McpServer\Supports\McpContext;
use SkdSeo\Supports\SeoAnalyzer;
use SkdSeo\Supports\SeoPoint;

/**
 * Bộ tiêu chí chấm điểm SEO của một loại nội dung, để AI viết theo ngay từ đầu.
 * Cùng nguồn với metabox: SeoPoint::criteria() / weights() / settings().
 */
class SeoGuidelinesTool extends SeoTool
{
    public function name(): string
    {
        return 'seo_guidelines';
    }

    public function title(): string
    {
        return 'SEO writing guidelines';
    }

    public function description(): string
    {
        return 'Get the SEO checklist this site scores content against (the same checklist editors see in the admin), with importance, thresholds and how the title / description shown on Google are built. '
            .'Call it BEFORE writing or rewriting a post, page, product or category. Workflow: pick a focus keyword, write following the checklist, save the content, '
            .'set the keyword with seo_update, then check with seo_score and fix the failed items until the score is at least 80.';
    }

    public function readOnly(): bool
    {
        return true;
    }

    public function inputSchema(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'type'          => $this->typeSchema(),
                'post_type'     => ['type' => 'string', 'description' => 'For type "post": the post type, default "post"'],
                'category_type' => ['type' => 'string', 'description' => 'For type "post_category": the category type, default "post_categories"'],
            ],
            'required'             => ['type'],
            'additionalProperties' => false,
        ];
    }

    protected function run(array $arguments, McpContext $context): ToolResult
    {
        $type = (string)$arguments['type'];

        [$module, $support] = match ($type) {
            'post'             => ['post', 'post_'.($arguments['post_type'] ?? 'post')],
            'page'             => ['page', 'page'],
            'post_category'    => ['post_categories', 'post_categories_'.($arguments['category_type'] ?? 'post_categories')],
            'product'          => ['products', 'products'],
            'product_category' => ['products_categories', 'products_categories'],
            default            => [null, null],
        };

        if ($module === null) return $this->error('Unknown type "'.$type.'".');

        $weights = SeoPoint::weights($module);

        $criteria = [];

        foreach (SeoPoint::sortByImportance(SeoPoint::criteria($module), $weights) as $key => $guideline)
        {
            $weight = $weights[$key] ?? 1;

            $criteria[] = [
                'key'        => $key,
                'guideline'  => $guideline,
                'importance' => SeoPoint::importance($weight)['key'],
                'weight'     => $weight,
            ];
        }

        $settings = array_merge(['minWords' => 300, 'longWords' => 300], SeoPoint::settings($module));

        $brand = SeoAnalyzer::brand();

        $separator = (string)apply_filters('seo_title_separator', ' | ');

        return ToolResult::json([
            'type'      => $type,
            'enabled'   => static::supported($support),
            'note'      => static::supported($support) ? null : 'SEO scoring is not enabled for this content type, so seo_score / seo_update will refuse it. The checklist is still good practice.',
            'criteria'  => $criteria,
            'thresholds' => [
                'title_min_chars'          => 30,
                'title_max_width_px'       => 580,
                'title_max_chars_estimate' => 60,
                'title_brand_suffix'       => $brand !== '' ? $separator.$brand : null,
                'description_chars'        => [110, 160],
                'url_max_chars'            => 75,
                'content_min_words'        => $settings['minWords'],
                'long_content_words'       => $settings['longWords'],
                'paragraph_max_words'      => 150,
                'keyword_stuffing'         => 'Fails when the keyword appears 4+ times AND more than 3 times per 100 words.',
                'keyword_in_intro'         => 'Within the first 200 characters, or the first 10% of the text if longer.',
            ],
            'how_it_is_read' => [
                'Words are counted by spaces, so in Vietnamese each syllable is one word.',
                'Title shown on Google = seo_title, or the title when seo_title is empty'.($brand !== '' ? ', followed by "'.$separator.$brand.'" added automatically (counts toward the width)' : '').'.',
                'Description = seo_description, or the excerpt (HTML stripped) when it is empty.',
                'The theme prints the title as H1: the content must NOT contain <h1>; use <h2>/<h3> for sections.',
                'Keyword matching ignores case and extra spaces, but not accents ("may loc nuoc" does not match "máy lọc nước").',
                'Subheadings and lists are only required once the content reaches long_content_words.',
                'Links: relative URLs and this site count as internal; other domains count as external. Every <img> needs a descriptive alt.',
                'The focus keyword is stored separately: set it with seo_update, it is not a field of post_create / post_update.',
            ],
        ]);
    }
}
