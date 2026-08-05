<?php
namespace SkdSeo\Services;

use Illuminate\Support\Str;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Image;
use SkillDo\Cms\Support\Option;
use SkillDo\Cms\Support\Url;
use Travel\Models\Tour;

/**
 * Schema.org cho các trang của plugin travel.
 *
 * Trang chi tiết tour phát một node đa kiểu ["Product", "TouristTrip"]:
 * TouristTrip mô tả đúng bản chất sản phẩm, còn Product là kiểu Google dùng để
 * dựng rich result (giá + sao đánh giá) — khai báo cả hai trên cùng một node
 * thay vì hai node rời để không tạo ra hai thực thể trùng nhau.
 *
 * FAQ và danh sách tour tách thành node riêng vì là thực thể khác.
 */
class SchemaTravel
{
    public string $website = 'http://schema.org/';

    static function support(): bool
    {
        return class_exists(Tour::class);
    }

    /**
     * Filter: schema_render
     */
    static function render($schemas, $page)
    {
        if(!self::support())
        {
            return $schemas;
        }

        $service = new static();

        if($page == 'tour_detail')
        {
            $tour = Cms::getData('tour');

            if(hasItems($tour))
            {
                $schemas[] = $service->tour($tour);

                $faq = $service->faq(Cms::getData('faq'));

                if(!empty($faq))
                {
                    $schemas[] = $faq;
                }
            }
        }

        //Trang danh sách tour và cả trang lọc (travel_archive) đều có data-bag 'tours'
        if($page == 'tour_index' || hasItems(Cms::getData('archive')))
        {
            $list = $service->itemList(Cms::getData('tours'));

            if(!empty($list))
            {
                $schemas[] = $list;
            }
        }

        return $schemas;
    }

    /**
     * Node chính của trang chi tiết tour.
     */
    public function tour($tour): array
    {
        $price = ((int) $tour->price_from_sale > 0 && (int) $tour->price_from_sale < (int) $tour->price_from)
            ? (int) $tour->price_from_sale
            : (int) $tour->price_from;

        $schema = [
            "@context"      => $this->website,
            "@type"         => ["Product", "TouristTrip"],
            "name"          => Str::clear($tour->name),
            "url"           => Url::current(),
            "description"   => (!empty($tour->seo_description))
                ? Str::clear($tour->seo_description)
                : Str::clear($tour->excerpt ?? ''),
            "sku"           => (!empty($tour->code)) ? $tour->code : $tour->id,
        ];

        if(!empty($tour->image))
        {
            $schema['image'] = $this->image($tour->image);
        }

        if(!empty($tour->next_departure))
        {
            $schema['departureTime'] = date(DATE_ATOM, strtotime($tour->next_departure));
        }

        /*
        | Giá chỉ khai báo khi TourStatSync đã tính được: một Offer giá 0 làm
        | Google từ chối toàn bộ rich result của trang.
        */
        if($price > 0)
        {
            $schema['offers'] = [
                "@type"         => "AggregateOffer",
                "priceCurrency" => "VND",
                "lowPrice"      => $price,
                "highPrice"     => max($price, (int) $tour->price_from),
                "offerCount"    => max(1, (int) $tour->departure_count),
                "url"           => Url::current(),
                "availability"  => ((int) $tour->seats_available > 0)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ];
        }

        /*
        | Đánh giá chỉ khai báo khi có thật. Bịa aggregateRating là lỗi bị
        | Google phạt thủ công, không phải mẹo seo.
        */
        if((int) $tour->rating_count > 0)
        {
            $schema['aggregateRating'] = [
                "@type"       => "AggregateRating",
                "ratingValue" => round((float) $tour->rating_avg, 1),
                "reviewCount" => (int) $tour->rating_count,
                "bestRating"  => 5,
                "worstRating" => 1,
            ];

            $reviews = $this->reviews(Cms::getData('reviews'));

            if(!empty($reviews))
            {
                $schema['review'] = $reviews;
            }
        }

        $itinerary = $this->itinerary(Cms::getData('itinerary'));

        if(!empty($itinerary))
        {
            $schema['itinerary'] = $itinerary;
        }

        $schema['provider'] = [
            "@type" => "Organization",
            "name"  => Option::get('general_label'),
            "url"   => Url::base(),
        ];

        return $schema;
    }

    /**
     * Lịch trình theo ngày → ItemList.
     */
    protected function itinerary($itinerary): array
    {
        if(!hasItems($itinerary))
        {
            return [];
        }

        $items = [];

        $position = 1;

        foreach ($itinerary as $day)
        {
            if(empty($day->title))
            {
                continue;
            }

            $items[] = [
                "@type"    => "ListItem",
                "position" => $position++,
                "name"     => Str::clear($day->title),
            ];
        }

        if(empty($items))
        {
            return [];
        }

        return [
            "@type"           => "ItemList",
            "numberOfItems"   => count($items),
            "itemListElement" => $items,
        ];
    }

    /**
     * Đánh giá đã duyệt → tối đa 5 review cho rich result.
     */
    protected function reviews($reviews): array
    {
        if(!hasItems($reviews))
        {
            return [];
        }

        $items = [];

        foreach ($reviews as $review)
        {
            if(count($items) >= 5)
            {
                break;
            }

            $items[] = [
                "@type"         => "Review",
                "reviewRating"  => [
                    "@type"       => "Rating",
                    "ratingValue" => (int) $review->rating,
                    "bestRating"  => 5,
                    "worstRating" => 1,
                ],
                "author"        => [
                    "@type" => "Person",
                    "name"  => Str::clear($review->fullname ?: 'Khách hàng'),
                ],
                "reviewBody"    => Str::clear($review->content ?? ''),
                "datePublished" => date(DATE_ATOM, strtotime($review->created)),
            ];
        }

        return $items;
    }

    /**
     * FAQ của tour → FAQPage.
     */
    public function faq($faq): array
    {
        if(!hasItems($faq))
        {
            return [];
        }

        $items = [];

        foreach ($faq as $item)
        {
            $question = $item['question'] ?? ($item['q'] ?? '');

            $answer = $item['answer'] ?? ($item['a'] ?? '');

            if(empty($question) || empty($answer))
            {
                continue;
            }

            $items[] = [
                "@type"          => "Question",
                "name"           => Str::clear($question),
                "acceptedAnswer" => [
                    "@type" => "Answer",
                    "text"  => Str::clear($answer),
                ],
            ];
        }

        if(empty($items))
        {
            return [];
        }

        return [
            "@context"   => $this->website,
            "@type"      => "FAQPage",
            "mainEntity" => $items,
        ];
    }

    /**
     * Danh sách tour của trang index / trang lọc → ItemList.
     */
    public function itemList($tours): array
    {
        if(!hasItems($tours))
        {
            return [];
        }

        $items = [];

        $position = 1;

        foreach ($tours as $tour)
        {
            if(empty($tour->slug))
            {
                continue;
            }

            $items[] = [
                "@type"    => "ListItem",
                "position" => $position++,
                "url"      => Url::base(Url::permalink((string) $tour->slug)),
                "name"     => Str::clear($tour->name),
            ];
        }

        if(empty($items))
        {
            return [];
        }

        return [
            "@context"        => $this->website,
            "@type"           => "ItemList",
            "numberOfItems"   => count($items),
            "itemListElement" => $items,
        ];
    }

    protected function image($image): string
    {
        $link = Image::source($image)->link();

        return (Url::is($link)) ? $link : Url::base($link);
    }
}
