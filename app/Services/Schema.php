<?php
namespace SkdSeo\Services;

use Ecommerce\Models\Brands;
use Ecommerce\Models\Product;
use RatingStar\Models\RatingStar;
use SkillDo\Cms\Models\User;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Image;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Theme;
use SkillDo\Cms\Support\Url;
use Illuminate\Support\Str;

Class Schema {

    public string $website = 'http://schema.org/';

    /**
     * Tên tác giả đã tra, theo id người dùng — trang chi tiết chỉ tra một lần.
     */
    protected static array $authorMemo = [];

    public mixed $schemas  = [];

    public mixed $title;

    public mixed $description;

    public mixed $image;

    function __construct()
    {
        $this->title        = Str::clear(Option::get('general_title', ''));
        $this->description  = Str::clear(Option::get('general_description', ''));
        $this->image        = Option::get('logo_header');
    }

    function setTitle($title): static
    {
        if(!empty($title)) $this->title = Str::clear($title);
        $this->title = apply_filters('schema_title', $this->title);
        return $this;
    }

    function setDescription($description): static
    {
        if(!empty($description)) $this->description = Str::clear($description);
        $this->description = apply_filters('schema_description', $this->description);
        return $this;
    }

    function setImage($image): static
    {
        if(!empty($image)) $this->image = Str::clear($image);
        $this->image = apply_filters('schema_image', $this->image);
        if(!empty($this->image)) $this->image = Image::source($this->image)->link();
        if(!Url::is($this->image)) $this->image = Url::base($this->image);
        return $this;
    }

    //schema chung cho toàn website
    public function website(): static
    {
        $this->schemas[] = [
            "@context"      => $this->website,
            "@type"         => "WebSite",
            "name"          => Option::get('general_label'),
            "alternateName" => $this->title,
            "url"           => Url::base(),
            "potentialAction"=> [
                "@type" => "SearchAction",
                "target" => [
                    "@type"         => "EntryPoint",
                    //Url::base() đã có sẵn dấu gạch chéo cuối — nối thêm '/search' nữa ra '//search'.
                    "urlTemplate"   => Url::base('search?keyword={search_term_string}&type=products')
                ],
                "query-input" => "required name=search_term_string"
            ],
        ];

        $organization = [
            "@context"      => $this->website,
            "@type"         => "Organization",
            "@id"           => Url::base().'#organization',
            "name"          => Option::get('general_label'),
            "alternateName" => $this->title,
            "url"           => Url::base(),
            "logo"          => asset(Option::get('logo_header')),
            "contactPoint"=> [
                "@type" => "ContactPoint",
                "telephone" => Option::get('contact_phone'),
                "contactType" => "customer service"
            ],
        ];

        if(!empty(Option::get('contact_address')))
        {
            $organization['address'] = [
                "@type"          => "PostalAddress",
                "streetAddress"  => Option::get('contact_address'),
                "addressCountry" => static::country(),
            ];
        }

        /*
        | sameAs = các hồ sơ chính thức khác của cùng một pháp nhân. Đây là tín
        | hiệu để bộ máy tìm kiếm và mô hình ngôn ngữ nối website với một thực thể
        | có thật thay vì coi là một tên miền vô danh.
        */
        $sameAs = static::sameAs();

        if(!empty($sameAs))
        {
            $organization['sameAs'] = $sameAs;
        }

        $this->schemas[] = $organization;

        return $this;
    }

    /**
     * Danh sách hồ sơ mạng xã hội (option `seo_social_profiles`, mỗi dòng 1 URL)
     */
    static function sameAs(): array
    {
        $raw = (string)Option::get('seo_social_profiles', '');

        $links = preg_split('/\r\n|\r|\n/', $raw);

        $links = array_values(array_filter(array_map('trim', $links), function ($link) {
            return $link !== '' && Url::is($link);
        }));

        return apply_filters('schema_same_as', $links);
    }

    /**
     * Mã quốc gia ISO của doanh nghiệp
     */
    static function country(): string
    {
        return apply_filters('schema_country', (string)Option::get('seo_country', 'VN'));
    }

    public function home(): static
    {
        $schemaLocalBusiness = Option::get('schemaLocalBusiness', []);

        if(!empty($schemaLocalBusiness['enabled']))
        {
            $business = [
                "@context"      => $this->website,
                "@type"         => "LocalBusiness",
                "@id"           => Url::base().'#localbusiness',
                "name"          => Option::get('general_label'),
                "alternateName" => $this->title,
                "url"           => Url::base(),
                "image"         => asset(Option::get('logo_header')),
                "description"   => $this->description,
                "address"       => [
                    "@type"         => "PostalAddress",
                    "streetAddress" => Option::get('contact_address'),
                    /*
                    | Tỉnh / thành phố là đơn vị hành chính cấp 1 -> addressRegion.
                    | addressLocality theo schema.org là quận / huyện / phường.
                    | Trước đây khai nhầm cấp nên Google đọc tên tỉnh như tên phường.
                    */
                    "addressRegion" => $schemaLocalBusiness['addressLocality'] ?? '',
                    "addressCountry"=> static::country(),
                ],
                "telephone"     => Option::get('contact_phone'),
                "openingHours"  => $schemaLocalBusiness['openingHours'] ?? '',
            ];

            $sameAs = static::sameAs();

            if(!empty($sameAs))
            {
                $business['sameAs'] = $sameAs;
            }

            $this->schemas[] = $business;
        }

        return $this;
    }

    /**
     * BreadcrumbList dạng JSON-LD.
     *
     * Dữ liệu lấy từ đúng hai filter mà ThemeBreadcrumb dùng, nhưng KHÔNG khởi
     * tạo singleton của theme: head render trước body, đụng vào singleton ở đây
     * sẽ chốt luôn dữ liệu cho breadcrumb hiển thị bên dưới.
     *
     * Lưu ý: theme vẫn xuất BreadcrumbList dạng microdata (SKDSeoSchemaBreadcrumb).
     * Hai bản mô tả cùng một danh sách, công cụ tìm kiếm gộp lại làm một; bản
     * JSON-LD thêm ở đây là để các trình đọc chỉ hiểu JSON-LD vẫn thấy breadcrumb.
     */
    public function breadcrumb(): static
    {
        $page = Theme::getPage();

        if(empty($page))
        {
            return $this;
        }

        $language = \SkillDo\Cms\Support\Language::current();

        $crumbs = apply_filters('theme_breadcrumb_'.$page.'_data', [], $language);

        $crumbs = apply_filters('theme_breadcrumb_data', $crumbs, $page, $language);

        if(!hasItems($crumbs))
        {
            return $this;
        }

        $itemListElement = [[
            "@type"    => "ListItem",
            "position" => 1,
            "name"     => trans('theme::general.home'),
            "item"     => Url::base(),
        ]];

        $position = 2;

        foreach ($crumbs as $crumb)
        {
            $name = Str::clear((string)($crumb->name ?? ''));

            if($name === '')
            {
                continue;
            }

            $url = (string)($crumb->slug ?? '');

            $itemListElement[] = [
                "@type"    => "ListItem",
                "position" => $position++,
                "name"     => $name,
                "item"     => (Url::is($url)) ? $url : Url::base($url),
            ];
        }

        if(count($itemListElement) < 2)
        {
            return $this;
        }

        $this->schemas[] = [
            "@context"          => $this->website,
            "@type"             => "BreadcrumbList",
            "itemListElement"   => $itemListElement,
        ];

        return $this;
    }

    public function product($item): static
    {
        if(!$item)
        {
            return $this->home();
        }

        $schema = [
            "@context"      => $this->website,
            "@type"         => "Product",
            "name"          => $this->title,
            "image"         => $this->image,
            "description" 	=> (!empty($item->seo_description)) ? Str::clear($item->seo_description) : Str::clear($this->description),
            "sku"           => (!empty($item->code)) ? $item->code : $item->id,
            "offers" 		=> [
                "@type"         => "AggregateOffer",
                "priceCurrency" => "VND",
                "highPrice"      => $item->price,
                "lowPrice"      => (!empty($item->price_sale)) ? $item->price_sale : $item->price,
                "offerCount"    => $item->price
            ],
        ];

        if(!empty($item->brand_id)){
            $brand = Brands::find($item->brand_id);
            if(hasItems($brand)) {
                $schema['brand'] = [
                    "@type" => "Brand",
                    "name" => $brand->name
                ];
            }
        }

        $total_star = 5;

        $total_number_review = 20;

        if(class_exists(RatingStar::class))
        {
            $rating_star_data       = Product::getMeta($item->id, 'rating_star', true);
            $total_star             = (isset($rating_star_data['star'])) ? $rating_star_data['star'] : 0;
            $total_number_review    = (isset($rating_star_data['count'])) ? $rating_star_data['count'] : 0;
            if($total_number_review > 0) {
                $total_star = round($total_star / $total_number_review);
                $reviews = RatingStar::where('object_type', 'products')->where('object_id', $item->id)->where('star', 5)->limit(5)->get();
                if (hasItems($reviews)) {
                    $schema['review'] = [];
                    foreach ($reviews as $review) {
                        $schema['review'][] = [
                            "@type" => "Review",
                            "reviewRating" => [
                                "@type" => "Rating",
                                "bestRating" => 5,
                                "ratingValue" => $review->star,
                                "worstRating" => 1,
                            ],
                            "author" => [
                                "@type" => "Person",
                                "name" => $review->name,
                            ],
                            "reviewBody" => $review->message,
                            "datePublished" => $review->created,
                        ];
                    }
                }
            }
            else {
                $total_star = 5;
                $total_number_review = 20;
            }
        }
        if(!isset($schema['review'])) {
            $schema['review'] = [
                "@type" => "Review",
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => 5,
                    "bestRating" => 5,
                ],
                "author"  => [
                    "@type" => "Person",
                    "name" => "Quản trị viên",
                ]
            ];
        }

        $schema['aggregateRating'] = [
            "@type" => "AggregateRating",
            "ratingValue" => $total_star,
            "reviewCount" => $total_number_review,
        ];

        $this->schemas[] = $schema;

        return $this;
    }

    /**
     * Tên nhà xuất bản = tên website.
     *
     * $this->title là tiêu đề của TRANG hiện tại (đã bị setTitle ghi đè), dùng
     * làm publisher.name thì mỗi bài viết lại khai một nhà xuất bản khác nhau.
     */
    protected function publisherName(): string
    {
        return Str::clear((string)(Option::get('general_label') ?: Option::get('general_title', '')));
    }

    /**
     * Khối `author` của bài viết.
     *
     * Trước đây khai cứng một Person tên "Quản trị" cho mọi bài. Đó là một con
     * người không tồn tại, không có trang tác giả nào trỏ tới — đúng thứ Google
     * xếp vào tín hiệu E-E-A-T rỗng. Ở đây lấy người đã tạo bài; không tra được
     * (bài nhập từ nơi khác, tài khoản đã xoá) thì khai chính tổ chức chủ site,
     * vì đó mới là bên thật sự chịu trách nhiệm nội dung.
     *
     * Filter `schema_author_name` giữ nguyên chữ ký cũ nên site nào đang ghi đè
     * tên tác giả vẫn chạy y như trước.
     *
     * Trả MẢNG RỖNG khi không có tên nào — site chưa điền tên thương hiệu. Khai
     * `"name": ""` thì Search Console vẫn báo lỗi y như thiếu trường, nhưng nhìn
     * vào mã lại tưởng đã khai đủ.
     */
    protected function author($item): array
    {
        $name = '';

        $userId = (int)($item->user_created ?? 0);

        if($userId > 0)
        {
            if(!array_key_exists($userId, static::$authorMemo))
            {
                $user = User::query()->find($userId);

                static::$authorMemo[$userId] = ($user)
                    ? trim(((string)$user->firstname).' '.((string)$user->lastname))
                    : '';
            }

            $name = static::$authorMemo[$userId];
        }

        $isPerson = ($name !== '');

        if(!$isPerson) $name = $this->publisherName();

        $name = Str::clear((string)apply_filters('schema_author_name', $name, $item));

        if($name === '') return [];

        return [
            "@type" => $isPerson ? "Person" : "Organization",
            "name"  => $name,
        ];
    }

    /**
     * Trang chi tiết bài viết.
     *
     * `BlogPosting` chứ không phải `NewsArticle`. NewsArticle dành cho tin tức
     * do một toà soạn xuất bản, và Google đòi thêm điều kiện riêng cho loại đó
     * (thuộc Google News, có tổ chức xuất bản thật). Blog hay cẩm nang của một
     * doanh nghiệp khai NewsArticle thì Search Console báo thiếu trường bắt buộc
     * mà chẳng đổi lại được gì. BlogPosting là con của Article, đúng bản chất và
     * đủ điều kiện cho mọi kết quả nâng cao mà nội dung dạng bài được hưởng.
     *
     * Site nào thật sự là báo thì đổi lại bằng filter `schema_article_type`.
     */
    public function post($item): static
    {
        if (hasItems($item)) {
            $schema = [
                "@context" => $this->website,
                "@type" => apply_filters('schema_article_type', 'BlogPosting', $item),
                "mainEntityOfPage" => Url::current(),
                "headline" => $this->title,
                "datePublished" => date(DATE_ATOM, strtotime($item->created)),
                //Ngày sửa thật của bài, không phải thời điểm khách mở trang
                "dateModified" => date(DATE_ATOM, strtotime(!empty($item->updated) ? $item->updated : $item->created)),
                "inLanguage" => \SkillDo\Cms\Support\Language::current(),
                //Không khai height/width: hai số 700x400 trước đây là bịa, ảnh thật kích thước bất kỳ.
                "image" => array(
                    "@type" => "ImageObject",
                    "url" => $this->image,
                ),
                "publisher" => array(
                    "@type" => "Organization",
                    "name" => $this->publisherName(),
                    "logo" => array(
                        "@type" => "ImageObject",
                        "url" => Url::base(Image::source(Option::get('logo_header'))->link()),
                    ),
                ),
            ];

            $author = $this->author($item);

            if(!empty($author)) $schema['author'] = $author;

            /*
            | Thẻ của bài viết chính là chủ đề bài viết — khai báo vào keywords
            | để Google hiểu bài nói về cái gì mà không phải suy từ nội dung.
            */
            $names = SeoTag::postTagNames((int)$item->id);

            if(hasItems($names))
            {
                $schema['keywords'] = implode(', ', array_map(function ($name) {
                    return Str::clear((string)$name);
                }, $names));
            }

            $this->schemas[] = $schema;
        }

        return $this;
    }

    /**
     * Trang lưu trữ theo thẻ.
     *
     * Không dùng lại category(): trang thẻ là một danh sách liên kết chứ không
     * phải một bài báo, khai là NewsArticle sẽ bị Search Console báo thiếu hàng
     * loạt trường bắt buộc (author, datePublished thật...).
     */
    public function tag($item): static
    {
        if(noItems($item))
        {
            return $this;
        }

        $this->schemas[] = $this->collectionPage();

        return $this;
    }

    /**
     * Trang lưu trữ theo danh mục bài viết.
     *
     * Dùng chung khuôn với trang thẻ. Trước đây hàm này khai `NewsArticle` —
     * cùng một lỗi mà ghi chú trên `tag()` đã mô tả, chỉ khác là chưa ai sửa:
     * một trang danh mục không có tác giả, không có ngày xuất bản của riêng nó,
     * và headline của nó là tên danh mục chứ không phải tiêu đề bài báo. Search
     * Console vì thế báo thiếu trường bắt buộc trên mọi trang danh mục.
     */
    public function category($item): static
    {
        if(noItems($item))
        {
            return $this;
        }

        $this->schemas[] = $this->collectionPage();

        return $this;
    }

    /**
     * Khuôn `CollectionPage` cho mọi trang lưu trữ (danh mục, thẻ).
     *
     * Danh sách bài đang hiển thị nằm ở data-bag `objects`, do controller của
     * trang lưu trữ đặt. Không có thì chỉ khai trang, bỏ `mainEntity`.
     */
    protected function collectionPage(): array
    {
        $schema = [
            "@context"      => $this->website,
            "@type"         => "CollectionPage",
            "@id"           => Url::current(),
            "url"           => Url::current(),
            "name"          => $this->title,
            "description"   => $this->description,
            "isPartOf"      => [
                "@type" => "WebSite",
                "name"  => Option::get('general_label'),
                "url"   => Url::base(),
            ],
        ];

        $objects = Cms::getData('objects');

        if(hasItems($objects))
        {
            $itemListElement = [];

            $position = 1;

            foreach ($objects as $object)
            {
                if(empty($object->slug))
                {
                    continue;
                }

                $itemListElement[] = [
                    "@type"     => "ListItem",
                    "position"  => $position++,
                    "url"       => Url::base(Url::permalink((string)$object->slug)),
                    "name"      => Str::clear((string)($object->title ?? '')),
                ];
            }

            if(!empty($itemListElement))
            {
                $schema['mainEntity'] = [
                    "@type"             => "ItemList",
                    "itemListElement"   => $itemListElement,
                ];
            }
        }

        return $schema;
    }

    public function render(): void
    {
        $this->website();

        if(is_home()) $this->home();

        if(!is_home()) $this->breadcrumb();

        if(Theme::isPage('products_detail')) $this->product(Cms::getData('object'));

        if(Theme::isPage('post_index'))
        {
            /*
            | Trang thẻ dùng chung template với trang danh mục nên cũng là
            | `post_index` — phân biệt bằng data-bag `tag` do TagController cấp.
            */
            $tag = Cms::getData('tag');

            if(hasItems($tag))
            {
                $this->tag($tag);
            }
            else
            {
                $this->category(Cms::getData('category'));
            }
        }

        if(Theme::isPage('post_detail')) $this->post(Cms::getData('object'));

        $this->schemas = apply_filters('schema_render', $this->schemas, Theme::getPage());

        if(hasItems($this->schemas))
        {
            foreach ($this->schemas as $schema)
            {
                echo '<script type="application/ld+json">'.json_encode($schema).'</script>';
            }
        }
    }
}