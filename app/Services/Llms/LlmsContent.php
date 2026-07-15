<?php
namespace SkdSeo\Services\Llms;

use SkillDo\Cms\Support\Option;

class LlmsContent
{
    protected array $group = [];

    public function __construct()
    {
        $this->group = [
            'main' => new LlmsGroup('# '.Option::get('general_title')),
            'page' => new LlmsGroup('## Website'),
            'post' => new LlmsGroup('## Bài viết'),
            'category' => new LlmsGroup('## Danh mục bài viết'),
            'tag' => new LlmsGroup('## Thẻ bài viết'),
            'product' => new LlmsGroup('## Sản phẩm'),
            'product_category' => new LlmsGroup('## Danh mục sản phẩm'),
            'product_tag' => new LlmsGroup('## Thẻ sản phẩm'),
            'service' => new LlmsGroup('## Dịch vụ'),
            'service_category' => new LlmsGroup('## Danh mục dịch vụ'),
            'service_tag' => new LlmsGroup('## Thẻ dịch vụ'),
            'knowledge' => new LlmsGroup('## Kiến thức'),
            'knowledge_category' => new LlmsGroup('## Danh mục kiến thức'),
            'knowledge_tag' => new LlmsGroup('## Thẻ kiến thức'),
            'portfolio' => new LlmsGroup('## Dự án'),
            'portfolio_category' => new LlmsGroup('## Danh mục dự án'),
            'portfolio_tag' => new LlmsGroup('## Thẻ dự án'),
            'help' => new LlmsGroup('## Hỗ trợ'),
            // Add more groups as needed
        ];
    }

    public function addGroup($key, $name): static
    {
        $this->group[$key] = new LlmsGroup($name);

        return $this;
    }

    public function group($key): ?LlmsGroup
    {
        return $this->group[$key] ?? null;
    }

    public function render(): string
    {
        $output = '';

        foreach ($this->group as $group)
        {
            $output .= $group->render() . "\n";
        }

        return $output;
    }
}
