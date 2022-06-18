<?php
Class Schema {

    public $website = 'http://schema.org/';

    public $schema  = [];

    public $title;

    public $description;

    public $image;

    function __construct() {
        $this->title        = Str::clear(Option::get('general_title', ''));
        $this->description  = Str::clear(Option::get('general_description', ''));
        $this->image        = Option::get('logo_header');
    }

    function setTitle($title) {
        if(!empty($title)) $this->title = Str::clear($title);
        $this->title = apply_filters('schema_title', $this->title);
        return $this;
    }

    function setDescription($description) {
        if(!empty($description)) $this->description = Str::clear($description);
        $this->description = apply_filters('schema_description', $this->description);
        return $this;
    }

    function setImage($image) {
        if(!empty($image)) $this->image = Str::clear($image);
        $this->image = apply_filters('schema_image', $this->image);
        if(!empty($this->image)) $this->image = Template::imgLink($this->image);
        if(!Url::is($this->image)) $this->image = Url::base($this->image);
        return $this;
    }

    public function home() {
        $this->schema = [
            "@context"      => $this->website,
            "@type"         => "WebSite",
            "name"          => option::get('general_label'),
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
        return $this;
    }

    public function product ($item) {

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

        if(!empty($item->brand_id)) {
            $brand = Brand::get($item->brand_id);
            if(have_posts($brand)) {
                $schema['brand'] = [
                    "@type" => "Brand",
                    "name" => $brand->name
                ];
            }
        }

        $total_star = 5;
        $total_number_review = 20;

        if(class_exists('rating_star')) {
            $rating_star_data       = Product::getMeta($item->id, 'rating_star', true);
            $total_star             = (isset($rating_star_data['star'])) ? $rating_star_data['star'] : 0;
            $total_number_review    = (isset($rating_star_data['count'])) ? $rating_star_data['count'] : 0;
            if($total_number_review > 0) {
                $total_star = round($total_star / $total_number_review);
                $reviews = Rating_star::gets(['where' => array('object_type' => 'product', 'object_id' => $item->id, 'star' => 5), 'params' => array('limit' => 5)]);
                if (have_posts($reviews)) {
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
        $schema['aggregateRating'] = [
            "@type" => "AggregateRating",
            "ratingValue" => $total_star,
            "reviewCount" => $total_number_review,
        ];

        $this->schema = $schema;

        return $this;
    }

    public function post ($item) {
        if (have_posts($item)) {
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
                        "url" => Url::base(Template::imgLink(Option::get('logo_header'))),
                        "height" => 260,
                        "width" => 100
                    ),
                ),
            ];
            $this->schema = $schema;
        }
        return $this;
    }

    public function category ($item) {
        if (have_posts($item)) {
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
                        "url" => Url::base(Template::imgLink(Option::get('logo_header'))),
                        "height" => 260,
                        "width" => 100
                    ),
                ),
            ];
            $this->schema = $schema;
        }
        return $this;
    }

    public function render() {
        if(is_home()) $this->home();
        if(Template::isPage('products_detail')) $this->product(get_object_current('object'));
        if(Template::isPage('post_index')) $this->category(get_object_current('category'));
        if(Template::isPage('post_detail')) $this->post(get_object_current('object'));
        $this->schema = apply_filters('schema_render', $this->schema, Template::getPage());
        if(!empty($this->schema)) {
            if(have_posts($this->schema) || is_array($this->schema)) $this->schema = json_encode($this->schema);
            echo '<script type="application/ld+json">'.$this->schema.'</script>';
        }
        else if(!empty($this->schema)) {
            json_decode($this->schema);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<script type="application/ld+json">'.$this->schema.'</script>';
            }
        }
    }
}