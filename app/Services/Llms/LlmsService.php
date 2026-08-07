<?php
namespace SkdSeo\Services\Llms;

use Illuminate\Support\Str;
use SkillDo\Cms\Models\Page;
use SkillDo\Cms\Models\Post;
use SkillDo\Cms\Models\PostCategory;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;

/**
 * Dựng nội dung cho /llms.txt và /llms-full.txt.
 *
 * llms.txt      = mục lục: mỗi nội dung một dòng link kèm mô tả ngắn.
 * llms-full.txt = toàn văn: dán luôn nội dung bài viết / trang để mô hình đọc
 *                 mà không phải đi lấy từng URL.
 *
 * Giới hạn số mục lấy từ option `seo_llms[limit]` — file này để mô hình ngôn ngữ
 * đọc trong một lần nạp, liệt kê vài nghìn URL vừa vô dụng vừa nặng.
 */
class LlmsService
{
    const OPTION = 'seo_llms';

    const LIMIT_DEFAULT = 100;

    /**
     * Trần cứng cho llms-full.txt — toàn văn nặng hơn mục lục rất nhiều.
     */
    const LIMIT_FULL = 50;

    static function config($key, $default = null)
    {
        $config = Option::get(self::OPTION);

        if(!is_array($config))
        {
            $config = [];
        }

        return (isset($config[$key]) && $config[$key] !== '') ? $config[$key] : $default;
    }

    static function limit(): int
    {
        $limit = (int)self::config('limit', self::LIMIT_DEFAULT);

        return ($limit > 0) ? $limit : self::LIMIT_DEFAULT;
    }

    static function fullEnabled(): bool
    {
        return !empty(self::config('full', 1));
    }

    /**
     * Cây nội dung của llms.txt
     */
    static function build(): LlmsContent
    {
        $llms = new LlmsContent();

        $limit = self::limit();

        $llms->group('main')->setDescription(Option::get('general_description'));

        //Trang nội dung
        $llms->group('page')->addItem('Trang chủ', Url::base(), Option::get('general_description'));

        foreach (Page::all() as $page)
        {
            if($page->slug == 'lien-he' || $page->slug == 'contact')
            {
                $llms->group('help')->addItem($page->title, Url::base($page->slug), 'Liên hệ với chúng tôi');
                continue;
            }

            $llms->group('page')->addItem($page->title, Url::base($page->slug));
        }

        //Danh mục bài viết
        foreach (PostCategory::all() as $category)
        {
            $llms->group('category')->addItem(
                $category->name,
                Url::base($category->slug),
                trim(Str::clear($category->excerpt ?? ''))
            );
        }

        /*
        | Bài viết — phần nội dung có giá trị nhất với mô hình ngôn ngữ. Trước đây
        | nhóm này khai sẵn trong LlmsContent nhưng không ai đổ dữ liệu vào nên
        | llms.txt chỉ có danh mục rỗng.
        */
        //Mục lục không cần nội dung bài — bỏ cột nặng nhất ra khỏi truy vấn
        $posts = Post::where('post_type', 'post')
            ->selectAllBut(['content'])
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();

        foreach ($posts as $post)
        {
            if(empty($post->slug))
            {
                continue;
            }

            $llms->group('post')->addItem(
                $post->title,
                Url::base(Url::permalink((string)$post->slug)),
                trim(Str::clear($post->excerpt ?? ''))
            );
        }

        //Sản phẩm
        if(class_exists(\Ecommerce\Models\Product::class))
        {
            $llms->group('product')->addItem('Tất cả sản phẩm', Url::base(URL_PRODUCT));

            foreach (\Ecommerce\Models\ProductCategory::all() as $category)
            {
                $llms->group('product_category')->addItem(
                    $category->name,
                    Url::base($category->slug),
                    trim(Str::clear($category->excerpt ?? ''))
                );
            }

            $products = \Ecommerce\Models\Product::where('type', 'product')
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get();

            foreach ($products as $product)
            {
                if(empty($product->slug))
                {
                    continue;
                }

                $llms->group('product')->addItem(
                    $product->title,
                    Url::base(Url::permalink((string)$product->slug)),
                    trim(Str::clear($product->excerpt ?? ''))
                );
            }
        }

        //FAQ
        if(class_exists('QuestionAnswer'))
        {
            $llms->group('help')->addItem('FAQ', Url::base('faq'), 'Câu hỏi thường gặp');
        }

        return apply_filters('skd_seo_llms_content', $llms);
    }

    /**
     * Toàn văn cho llms-full.txt
     */
    static function full(): string
    {
        $limit = min(self::limit(), self::LIMIT_FULL);

        $output = '# '.Str::clear((string)Option::get('general_title', ''))."\n\n";

        $description = trim(Str::clear((string)Option::get('general_description', '')));

        if($description !== '')
        {
            $output .= '> '.$description."\n\n";
        }

        $output .= 'Nguồn: '.Url::base()."\n";
        $output .= 'Mục lục rút gọn: '.Url::base('llms.txt')."\n\n";

        foreach (Page::all() as $page)
        {
            $output .= self::document($page->title, Url::base($page->slug), $page->content ?? '');
        }

        $posts = Post::where('post_type', 'post')
            ->orderBy('created', 'desc')
            ->limit($limit)
            ->get();

        foreach ($posts as $post)
        {
            if(empty($post->slug))
            {
                continue;
            }

            $output .= self::document(
                $post->title,
                Url::base(Url::permalink((string)$post->slug)),
                $post->content ?? $post->excerpt ?? ''
            );
        }

        return apply_filters('skd_seo_llms_full', $output);
    }

    /**
     * Một khối toàn văn: tiêu đề + nguồn + nội dung đã bóc hết thẻ HTML.
     */
    static protected function document($title, $url, $content): string
    {
        $content = strip_tags((string)$content);

        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        //Gom khoảng trắng thừa do bóc thẻ để lại, giữ lại ngắt đoạn
        $content = preg_replace('/[ \t]+/u', ' ', $content);

        $content = preg_replace('/(\R){3,}/u', "\n\n", $content);

        $content = trim($content);

        if($content === '')
        {
            return '';
        }

        return '## '.trim(Str::clear((string)$title))."\n"
            .'Nguồn: '.$url."\n\n"
            .$content."\n\n";
    }
}
