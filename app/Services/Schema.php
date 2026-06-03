<?php
namespace SkdSeo\Services;

use Ecommerce\Models\Brands;
use Ecommerce\Models\Product;
use RatingStar\Models\RatingStar;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Image;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Theme;
use SkillDo\Cms\Support\Url;
use Illuminate\Support\Str;

Class Schema {

    public string $website = 'http://schema.org/';

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
                    "urlTemplate"   => Url::base()."/search?keyword={search_term_string}&type=products"
                ],
                "query-input" => "required name=search_term_string"
            ],
        ];

        $this->schemas[] = [
            "@context"      => $this->website,
            "@type"         => "Organization",
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

        return $this;
    }

    public function home(): static
    {
        $schemaLocalBusiness = Option::get('schemaLocalBusiness', []);

        if(!empty($schemaLocalBusiness['enabled']))
        {
            $this->schemas[] = [
                "@context"      => $this->website,
                "@type"         => "LocalBusiness",
                "name"          => Option::get('general_label'),
                "alternateName" => $this->title,
                "url"           => Url::base(),
                "image"         => asset(Option::get('logo_header')),
                "description"   => $this->description,
                "address"       => [
                    "@type"         => "PostalAddress",
                    "streetAddress" => Option::get('contact_address'),
                    "addressLocality"=> $schemaLocalBusiness['addressLocality'] ?? '',
                    "addressCountry"=> "VN",
                ],
                "telephone"     => Option::get('contact_phone'),
                "openingHours"  => $schemaLocalBusiness['openingHours'] ?? '',
            ];
        }

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

    public function post($item): static
    {
        if (hasItems($item)) {
            $schema = [
                "@context" => $this->website,
                "@type" => "NewsArticle",
                "mainEntityOfPage" => Url::current(),
                "headline" => $this->title,
                "datePublished" => date(DATE_ATOM, strtotime($item->created)),
                "dateModified" => date(DATE_ATOM),
                "image" => array(
                    "@type" => "ImageObject",
                    "url" => $this->image,
                    "height" => 400,
                    "width" => 700
                ),
                "author" => array(
                    "@type" => "Person",
                    "name" => 'Quản trị',
                ),
                "publisher" => array(
                    "@type" => "Organization",
                    "name" => $this->title,
                    "logo" => array(
                        "@type" => "ImageObject",
                        "url" => Url::base(Image::source(Option::get('logo_header'))->link()),
                        "height" => 260,
                        "width" => 100
                    ),
                ),
            ];

            $this->schemas[] = $schema;
        }

        return $this;
    }

    public function category($item): static
    {
        if (hasItems($item))
        {
            $schema = [
                "@context" => $this->website,
                "@type" => "NewsArticle",
                "mainEntityOfPage" => Url::current(),
                "headline" => $this->title,
                "datePublished" => date(DATE_ATOM, strtotime($item->created)),
                "dateModified" => date(DATE_ATOM),
                "image" => array(
                    "@type" => "ImageObject",
                    "url" => $this->image,
                    "height" => 400,
                    "width" => 700
                ),
                "author" => array(
                    "@type" => "Person",
                    "name" => 'Quản trị',
                ),
                "publisher" => array(
                    "@type" => "Organization",
                    "name" => $this->title,
                    "logo" => array(
                        "@type" => "ImageObject",
                        "url" => Url::base(Image::source(Option::get('logo_header'))->link()),
                        "height" => 260,
                        "width" => 100
                    ),
                ),
            ];

            $this->schemas[] = $schema;
        }

        return $this;
    }

    public function render(): void
    {
        $this->website();

        if(is_home()) $this->home();

        if(Theme::isPage('products_detail')) $this->product(Cms::getData('object'));

        if(Theme::isPage('post_index')) $this->category(Cms::getData('category'));

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