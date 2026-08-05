<?php

namespace SkdSeo\Services;

use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Theme;
use SkillDo\Cms\Support\Url;
use Illuminate\Support\Str;

class HeadService
{
    public mixed $title;

    public mixed $description;

    public mixed $keyword;

    public mixed $image;

    public mixed $auth;

    public string $favicon;

    public array $meta = [];

    public array $code = [];

    public Schema $schema;

    function __construct()
    {

        $this->title = Str::clear(Option::get('general_title', ''));

        $this->description = Str::clear(Option::get('general_description', ''));

        $this->keyword = Str::clear(Option::get('general_keyword', ''));

        if (Theme::isPage('products_index') && is_null(Cms::getData('category')))
        {
            $this->title = Str::clear(Option::get('product_title'));

            $this->description = Str::clear(Option::get('product_description'));

            $this->keyword = Str::clear(Option::get('product_keyword'));
        }

        $this->image = Option::get('logo_header');

        $this->auth = Option::get('general_title');

        $this->schema = new Schema();
    }

    function setTitle($title): static
    {
        if (!empty($title))
        {
            if (Str::isHtmlSpecialChars($title))
            {
                $title = htmlspecialchars_decode($title);
            }
            $this->title = Str::clear($title);
        }
        $this->title = apply_filters('seo_title', $this->title);

        $this->schema->setTitle($this->title);

        return $this;
    }

    function setDescription($description): static
    {
        if (!empty($description))
        {
            if (Str::isHtmlSpecialChars($description))
            {
                $description = htmlspecialchars_decode($description);
            }
            $this->description = Str::clear($description);
        }
        $this->description = apply_filters('seo_description', $this->description);
        $this->schema->setDescription($this->description);
        $this->addMeta('description', $this->description);
        return $this;
    }

    function setKeyword($keyword): static
    {
        if (!empty($keyword))
        {
            if (Str::isHtmlSpecialChars($keyword))
            {
                $keyword = htmlspecialchars_decode($keyword);
            }
            $this->keyword = Str::clear($keyword);
        }
        $this->keyword = apply_filters('seo_keyword', $this->keyword);
        $this->addMeta('keywords', $this->keyword);
        return $this;
    }

    function setImage($image): static
    {
        if (!empty($image)) $this->image = Str::clear($image);
        $this->image = apply_filters('seo_image', $this->image);
        $this->schema->setImage($this->image);
        if (!empty($this->image)) $this->image = \Image::source($this->image)->link();
        if (!Url::is($this->image)) $this->image = Url::base($this->image);
        $this->addMeta('image', $this->image);
        return $this;
    }

    function setAuth($auth): static
    {
        if (!empty($auth)) $this->auth = $auth;
        $this->auth = apply_filters('seo_auth', $this->auth);
        return $this;
    }

    function setFavicon($favicon): static
    {
        if (!empty($favicon)) $this->favicon = $favicon;
        return $this;
    }

    function addMeta($name, $content, $args = []): static
    {
        if (!empty($content))
        {
            $attr = '';
            if (hasItems($args))
            {
                foreach ($args as $key => $txt)
                {
                    if (is_string($txt)) $attr .= $key . '="' . $txt . '" ';
                }
                $attr = trim($attr);
            }
            $item = [
                'name' => $name,
                'content' => $content,
                'attr' => $attr
            ];

            /*
            | Meta có tên (description, keywords, robots, image...) chỉ được
            | phép xuất hiện MỘT lần. Các filter seo_head_base / seo_render của
            | plugin chạy sau khi giá trị mặc định đã set lại, nên nếu cứ nối
            | thêm thì trang sẽ có hai thẻ description với nội dung khác nhau.
            |
            | Meta không tên (og:*, twitter:* khai báo bằng property/itemprop)
            | vẫn nối bình thường vì mỗi thẻ là một thuộc tính khác nhau.
            */
            if (!empty($name))
            {
                foreach ($this->meta as $index => $meta)
                {
                    if ($meta['name'] === $name)
                    {
                        $this->meta[$index] = $item;

                        return $this;
                    }
                }
            }

            $this->meta[] = $item;
        }
        return $this;
    }

    function addProperty($name, $content, $args = []): static
    {
        $this->addMeta('', $content, [
            'property' => $name
        ]);
        return $this;
    }

    function addItemprop($name, $content, $args = []): static
    {
        $this->addMeta('', $content, [
            'itemprop' => $name
        ]);
        return $this;
    }

    function addCode($name, $content): static
    {
        $this->code[$name] = $content;
        return $this;
    }

    function render(): void
    {
        echo '<title>' . $this->title . '</title>';

        foreach ($this->meta as $meta)
        {
            echo '<meta ' . ((!empty($meta['name'])) ? 'name="' . $meta['name'] . '" ' : ' ') . $meta['attr'] . ' content="' . $meta['content'] . '"/>';
        }

        foreach ($this->code as $code)
        {
            if (is_string($code)) echo $code;
        }

        $this->schema->render();
    }
}