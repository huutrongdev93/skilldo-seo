<?php
namespace SkdSeo\Services\Llms;

class LlmsGroup
{
    protected string $name;

    protected string $description = '';

    protected array $items = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setDescription($description): static
    {
        if(is_string($description))
        {
            $description = trim($description);

            $this->description = $description;
        }

        return $this;
    }

    public function addItem($title, $url, $description = ''): static
    {
        $this->items[] = [
            'title' => $title,
            'url' => $url,
            'description' => $description,
        ];
        return $this;
    }

    public function render(): string
    {
        $output = '';

        if (!empty($this->description) || !empty($this->items))
        {
            $output = $this->name . "\n";

            if (!empty($this->description))
            {
                $output .= $this->description . "\n";
            }

            foreach ($this->items as $item)
            {
                $output .= '- [' . trim($item['title']) . '] (' . $item['url'] . ')';

                if (!empty($item['description']))
                {
                    $output .= ': ' . $item['description'];
                }

                $output .= "\n";
            }

            return $output;
        }

        return $output;
    }
}
