<?php
namespace SkdSeo\Mcp;

use McpServer\Supports\McpContext;
use McpServer\Supports\McpTool;
use SkdSeo\Supports\SeoPoint;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Taxonomy\Taxonomy;

/*
|--------------------------------------------------------------------------
| Nền chung của tool MCP chấm điểm SEO
|--------------------------------------------------------------------------
| Các class trong app/Mcp/ chỉ được nạp khi plugin mcp-server đọc filter
| `cms_mcp_tools` (khai ở bootstrap/mcp.php bằng tên class dạng chuỗi), nên kế
| thừa thẳng McpServer\Supports\McpTool được.
|
| Một "đích" = một loại nội dung (`type` phía AI) ↔ một module chấm điểm của
| SeoPoint::module() ↔ một khoá trong option `seo_point_support`:
|   post             → module post,                key post_{post_type}
|   page             → module page,                key page
|   product          → module products,            key products            (cần sicommerce)
|   post_category    → module post_categories,     key post_categories_{cate_type}
|   product_category → module products_categories, key products_categories (cần sicommerce)
| Loại nào chưa tick ở Cấu hình > Seo > Chấm điểm seo thì metabox không hiện →
| tool cũng từ chối, để AI không đặt từ khóa mà biên tập viên không bao giờ thấy.
*/
abstract class SeoTool extends McpTool
{
    const TYPES = ['post', 'page', 'product', 'post_category', 'product_category'];

    /** Các trường AI được truyền để chấm thử bản nháp (ghi đè giá trị đang lưu). */
    const DRAFT_FIELDS = ['keyword', 'title', 'seo_title', 'seo_description', 'excerpt', 'content', 'slug', 'image'];

    protected function typeSchema(): array
    {
        $types = static::TYPES;

        if (!class_exists(\Ecommerce\Models\Product::class))
        {
            $types = array_values(array_diff($types, ['product', 'product_category']));
        }

        return ['type' => 'string', 'enum' => $types, 'description' => 'Content type'];
    }

    /**
     * Đích chấm điểm của một bản ghi, kèm kiểm quyền + kiểm module đã bật.
     *
     * @param string $action view | edit
     *
     * @return array{module: string, support: string, object: object, class: object}|string mảng đích, hoặc chuỗi lỗi
     */
    protected function target(string $type, int $id, McpContext $context, string $action): array|string
    {
        $model = match ($type) {
            'post'             => \SkillDo\Cms\Models\Post::class,
            'page'             => \SkillDo\Cms\Models\Page::class,
            'post_category'    => \SkillDo\Cms\Models\PostCategory::class,
            'product'          => \Ecommerce\Models\Product::class,
            'product_category' => \Ecommerce\Models\ProductCategory::class,
            default            => null,
        };

        if ($model === null || !class_exists($model))
        {
            return 'Unknown or unavailable type "'.$type.'".';
        }

        $query = $model::where('id', $id);

        // Ngoài admin, global scope chỉ trả public = 1: tự đặt điều kiện để thấy cả bản ẩn.
        if (in_array('public', schema()->getColumnListing((new $model)->getTable()), true))
        {
            $query->whereIn('public', [0, 1]);
        }

        $object = $query->first();

        if (empty($object))
        {
            return ucfirst(str_replace('_', ' ', $type)).' '.$id.' not found.';
        }

        [$module, $support, $capability] = match ($type) {
            'post'             => ['post', 'post_'.$object->post_type, Taxonomy::getPost($object->post_type)['capabilities'][$action] ?? null],
            'page'             => ['page', 'page', $action === 'edit' ? 'edit_pages' : 'view_pages'],
            'post_category'    => ['post_categories', 'post_categories_'.$object->cate_type, Taxonomy::getCategory($object->cate_type)['capabilities'][$action === 'edit' ? 'edit' : 'view'] ?? null],
            'product'          => ['products', 'products', $action === 'edit' ? 'product_edit' : 'product_list'],
            'product_category' => ['products_categories', 'products_categories', $action === 'edit' ? 'product_cate_edit' : 'product_cate_list'],
        };

        if ($capability && !$context->can($capability))
        {
            return 'You are not allowed to '.$action.' this '.str_replace('_', ' ', $type).'.';
        }

        if ($type === 'product' && $context->can('product_own') && (int)$object->user_created !== $context->userId())
        {
            return 'You can only manage your own products.';
        }

        if (!static::supported($support))
        {
            return 'SEO scoring is not enabled for this content type ("'.$support.'"). An administrator can enable it in System settings > Seo > SEO scoring.';
        }

        $class = SeoPoint::module($module.'.class');

        if (empty($class) || !class_exists($class))
        {
            return 'SEO scoring module "'.$module.'" is not available.';
        }

        return ['module' => $module, 'support' => $support, 'object' => $object, 'class' => new $class()];
    }

    /** Loại nội dung này có bật chấm điểm không (cùng điều kiện hiện metabox). */
    public static function supported(string $support): bool
    {
        $enabled = Option::get('seo_point_support');

        return !empty(Option::get('seo_point')) && is_array($enabled) && in_array($support, $enabled, true);
    }

    /**
     * Dữ liệu chấm điểm của một bản ghi = đúng những ô metabox đọc trong form admin
     * (ngôn ngữ mặc định), rồi ghi đè bằng bản nháp AI gửi (nếu có).
     */
    protected function input(array $target, array $draft = []): array
    {
        $object = $target['object'];

        $text = fn($value) => html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $input = [
            'keyword'         => (string)$target['class']->getFocusKeyword((int)$object->id),
            'title'           => $text($object->title ?? $object->name ?? ''),
            'seo_title'       => $text($object->seo_title ?? ''),
            'seo_description' => $text($object->seo_description ?? ''),
            'excerpt'         => (string)($object->excerpt ?? ''),
            'content'         => (string)($object->content ?? ''),
            'slug'            => (string)($object->slug ?? ''),
            'image'           => (string)($object->image ?? ''),
        ];

        foreach (static::DRAFT_FIELDS as $field)
        {
            if (array_key_exists($field, $draft) && $draft[$field] !== null)
            {
                $input[$field] = (string)$draft[$field];
            }
        }

        return $input;
    }

    /** Thứ tự cho AI: chưa đạt trước, trong mỗi nhóm quan trọng nhất trước. */
    protected function present(array $criteria): array
    {
        uasort($criteria, fn($a, $b) => [$a['passed'], -$a['weight']] <=> [$b['passed'], -$b['weight']]);

        $result = [];

        foreach ($criteria as $key => $item)
        {
            $result[] = [
                'key'        => $key,
                'passed'     => $item['passed'],
                'importance' => SeoPoint::importance($item['weight'])['key'],
                'weight'     => $item['weight'],
                'message'    => html_entity_decode(strip_tags($item['message']), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }

        return $result;
    }

    protected static function rating(int $score): string
    {
        return $score >= 80 ? 'good' : ($score >= 50 ? 'ok' : 'bad');
    }
}
