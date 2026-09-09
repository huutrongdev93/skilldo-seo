<?php
namespace SkdSeo\Services;

use Illuminate\Support\Str;
use SkillDo\Cms\Support\Image;
use SkillDo\Cms\Support\Language;
use SkillDo\Cms\Support\Url;

class SitemapService
{
    private string $xml = '';

    /**
     * Thẻ mở của một sitemap con.
     *
     * Gom về một chỗ vì chín file nguồn đều lặp lại đúng chuỗi này — thiếu một
     * namespace ở một file là file đó hỏng mà năm file kia vẫn chạy, rất khó
     * thấy. `xmlns:image` phải khai ở đây thì `<image:image>` bên trong mới hợp lệ.
     */
    const URLSET_OPEN = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
        .' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"'
        .' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
        .' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
        .' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

    public function openUrlset(): SitemapService
    {
        return $this->setXml(self::URLSET_OPEN);
    }

    public function closeUrlset(): SitemapService
    {
        return $this->setXml('</urlset>');
    }

    static function sitemap(): void
    {
        $request = request();

        header('Content-type: application/xml');

        $sitemap = new SitemapService();

        $sitemap
            ->setXml('<?xml version="1.0" encoding="UTF-8"?>')
            ->setXml('<?xml-stylesheet type="text/xsl" href="'.Url::base().SKD_SEO_PATH.'assets/main-sitemap.xsl"?>');

        $type = $request->input('p');

        if(empty($type))
        {
            $sitemapList = apply_filters('seo_sitemap_list', []);

            $sitemap->setXml('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');

            if(hasItems($sitemapList))
            {
                foreach ($sitemapList as $sitemapKey => $item)
                {
                    /*
                    | Nhóm nhiều hơn một trang thì khai thẳng từng trang ra index
                    | gốc. Trước đây index gốc trỏ tới `?p=post`, mà trang đó lại
                    | là một sitemapindex nữa — chuẩn sitemap không cho phép lồng
                    | index, nên Google dừng ở tầng hai và không bao giờ đọc tới
                    | các trang con.
                    */
                    $pages = $item['pages'] ?? [];

                    if(is_array($pages) && count($pages) > 1)
                    {
                        foreach ($pages as $number => $date)
                        {
                            $sitemap->item('sitemap.xml?p='.$sitemapKey.'-'.$number, $date);
                        }

                        continue;
                    }

                    //Nhóm một trang giữ nguyên đường dẫn cũ `?p={key}` — không đổi URL đã được index.
                    $sitemap->item('sitemap.xml?p='.$sitemapKey, $item['date'] ?? null);
                }
            }

            $sitemap->setXml('</sitemapindex>');
        }
        else
        {
            $number = 0;

            if(str_contains($type, '-'))
            {
                $parts = explode('-', $type);

                $last = end($parts);

                if (is_numeric($last))
                {
                    $number = $last;

                    array_pop($parts);
                }

                // Nối lại chuỗi
                $type = implode('-', $parts);
            }

            $sitemap = apply_filters('seo_sitemap_'.str_replace('-', '_', trim($type)).'_xml', $sitemap, $type, $number, $request);
        }



        $sitemap->render();
    }

    public function render(): void
    {
        echo trim($this->xml, "\n");
    }

    public function setXml($xml): SitemapService
    {
        $this->xml .= $xml."\n";;
        return $this;
    }

    /**
     * Dựng thẻ <lastmod>, hoặc CHUỖI RỖNG khi không biết ngày sửa thật.
     *
     * Trước đây ba hàm dựng mục bên dưới gọi thẳng `date($date)`, mà mọi nơi gọi
     * đều truyền hằng `DATE_ATOM`. Đó là chuỗi ĐỊNH DẠNG chứ không phải mốc thời
     * gian, nên `date()` lấy giờ hiện tại: toàn bộ sitemap khai "vừa sửa" ở mỗi
     * lần tải. Google phát hiện lastmod không đáng tin thì bỏ qua lastmod của cả
     * site, nên thà không khai còn hơn khai sai — đó là lý do hàm này trả rỗng
     * thay vì lùi về thời điểm hiện tại.
     *
     * Vẫn nhận được cách gọi cũ: hằng định dạng truyền vào thì `strtotime()` trả
     * false và mục đó chỉ đơn giản là không có lastmod, plugin bên thứ ba không
     * gãy mà cũng không còn nói dối.
     *
     * @param string|int|\DateTimeInterface|null $date Mốc thời gian thật
     */
    protected function lastmod(mixed $date): string
    {
        $timestamp = null;

        if ($date instanceof \DateTimeInterface)
        {
            $timestamp = $date->getTimestamp();
        }
        else if (is_int($date) || (is_string($date) && ctype_digit($date)))
        {
            $timestamp = (int) $date;
        }
        else if (is_string($date) && $date !== '')
        {
            //false với mọi hằng định dạng (DATE_ATOM, DATE_RFC2822...), đúng ý đồ.
            $timestamp = strtotime($date) ?: null;
        }

        //Cột ngày rỗng trong MySQL ra '0000-00-00 00:00:00' -> mốc âm, không phải ngày thật.
        if ($timestamp === null || $timestamp <= 0) return '';

        return '<lastmod>'.date(DATE_ATOM, $timestamp).'</lastmod>'."\n";
    }

    /**
     * Ngày sửa gần nhất của một nhóm, dùng cho mục trong trang sitemap index.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     */
    static function maxDate($query, string $column = 'updated'): ?string
    {
        try
        {
            return $query->max($column) ?: null;
        }
        catch (\Throwable)
        {
            //Bảng của plugin chưa cài, hoặc bảng cũ chưa có cột: bỏ lastmod, đừng đổ trang.
            return null;
        }
    }

    /**
     * Ngày sửa gần nhất của TỪNG TRANG trong một nhóm có phân trang.
     *
     * Trả `[1 => ngày, 2 => ngày, ...]` để `sitemap()` khai thẳng từng trang ra
     * index gốc, hoặc mảng rỗng khi nhóm chỉ vừa một trang.
     *
     * `$query` phải là ĐÚNG truy vấn mà hàm dựng sitemap con dùng, **đã sắp xếp
     * sẵn** — mỗi service để việc sắp xếp trong `query()` của nó vì thế. Sắp xếp
     * khác nhau giữa hai chỗ thì lát cắt lệch nhau và ngày gắn nhầm trang.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @return array<int, string|null>
     */
    static function pageDates($query, int $limit = 200, string $column = 'updated'): array
    {
        try
        {
            $total = (clone $query)->count();

            if($total <= $limit) return [];

            $dates = [];

            for ($page = 1; $page <= (int) ceil($total / $limit); $page++)
            {
                /*
                | Không dùng được max() thẳng: SQL áp LIMIT sau khi gộp nên
                | `MAX(...) LIMIT 200` vẫn ra max của cả bảng. Lấy về đúng một
                | cột của lát cắt rồi so trong PHP — chuỗi 'Y-m-d H:i:s' so sánh
                | theo thứ tự từ điển cũng chính là thứ tự thời gian.
                */
                $values = (clone $query)
                    ->offset(($page - 1) * $limit)
                    ->limit($limit)
                    ->pluck($column)
                    ->filter();

                $dates[$page] = $values->isEmpty() ? null : (string) $values->max();
            }

            return $dates;
        }
        catch (\Throwable)
        {
            //Bảng của plugin chưa cài, hoặc bảng cũ chưa có cột: coi như một trang.
            return [];
        }
    }

    /**
     * Ảnh của một bản ghi, dạng danh sách URL tuyệt đối.
     *
     * Mọi model của CMS lẫn plugin đều để ảnh đại diện ở cột `image`. Khai ảnh
     * vào sitemap là cách duy nhất đẩy ảnh vào Google Images mà không phụ thuộc
     * việc trình thu thập có dựng được JavaScript của trang hay không — đáng kể
     * với du lịch và thương mại điện tử, nơi lưu lượng từ tìm kiếm ảnh không nhỏ.
     *
     * @return array<int, string>
     */
    static function itemImages($item): array
    {
        $path = $item->image ?? null;

        if(empty($path)) return [];

        $url = Image::source((string) $path)->link();

        if(empty($url)) return [];

        //Filter `get_img_link` có thể đã trả URL tuyệt đối (CDN, tên miền ảnh riêng).
        $url = Str::isUrl($url) ? $url : Url::base($url);

        return apply_filters('seo_sitemap_item_images', [$url], $item);
    }

    /**
     * Thoát ký tự cho nội dung nhét vào XML.
     *
     * Cần cho URL ảnh: tên file do người dùng tải lên có thể chứa `&`, mà một
     * dấu `&` trần là đủ làm hỏng cả file sitemap.
     */
    protected function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Khối `<image:image>` của một mục.
     *
     * @param array<int, string> $images
     */
    protected function images(array $images): string
    {
        $xml = '';

        foreach ($images as $image)
        {
            $image = trim((string) $image);

            if($image === '') continue;

            $xml .= '<image:image><image:loc>'.$this->xml($image).'</image:loc></image:image>'."\n";
        }

        return $xml;
    }

    /**
     * Ngày sửa thật của một bản ghi.
     *
     * Mọi model của CMS lẫn plugin đều theo một quy ước: cột `updated` (nullable,
     * `ON UPDATE CURRENT_TIMESTAMP`) và cột `created`. Bản ghi nhập từ nơi khác
     * có thể để trống `updated`, khi đó ngày tạo là mốc đúng nhất còn lại.
     */
    static function itemDate($item): ?string
    {
        $updated = $item->updated ?? null;

        if (!empty($updated)) return (string) $updated;

        $created = $item->created ?? null;

        return empty($created) ? null : (string) $created;
    }

    /**
     * Một mục trong trang sitemap index.
     *
     * Chuẩn sitemap chỉ cho phép `<sitemap>` chứa `<loc>` và `<lastmod>` bên trong
     * `<sitemapindex>`. Trước đây nhánh đa ngôn ngữ ở đây in ra `<url>` kèm
     * `<xhtml:link>` — cả hai đều sai chỗ, và tiền tố `xhtml` còn chưa được khai
     * ở thẻ gốc, nên trên site đa ngữ file sitemap index hỏng XML và Google bỏ
     * nguyên file. Bản dịch vốn đã được khai trong từng sitemap con (`itemUrl`),
     * khai lại ở index vừa thừa vừa sai.
     *
     * Cũng KHÔNG thêm tiền tố ngôn ngữ vào đường dẫn: `SetLanguage::exclude()`
     * đã loại `sitemap.xml` khỏi middleware ngôn ngữ nên `/en/sitemap.xml` không
     * phải là một URL có thật.
     *
     * @param string|int|\DateTimeInterface|null $date Ngày sửa thật; xem lastmod()
     */
    public function item($url, $date): SitemapService
    {
        $item = '<sitemap>'."\n";
        $item .= '<loc>'.Url::base($url).'</loc>'."\n";
        $item .= $this->lastmod($date);
        $item .= '</sitemap>'."\n";

        $this->xml .= $item;

        return $this;
    }

    /**
     * @param string|int|\DateTimeInterface|null $date Ngày sửa thật; xem lastmod()
     */
    public function itemHome($date, $change, $priority): SitemapService
    {
        if(!Language::isMulti())
        {
            $item = '<url>'."\n";
            $item .= '<loc>'.Url::base().'</loc>'."\n";
            $item .= $this->lastmod($date);
            $item .= '<changefreq>'.$change.'</changefreq>'."\n";
            $item .= '<priority>'.$priority.'</priority>'."\n";
            $item .= '</url>'."\n";
            $this->xml .= $item;
        }
        else
        {
            foreach (Language::listKey() as $lang)
            {
                $item = '<url>'."\n";
                if($lang == Language::default())
                {
                    $item .= '<loc>'.Url::base().'</loc>'."\n";
                }
                else
                {
                    $item .= '<loc>'.Url::base($lang).'</loc>'."\n";
                }
                foreach (Language::listKey() as $langKey)
                {
                    if($langKey == Language::default())
                    {
                        $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base().'"/>'."\n";
                    }
                    else
                    {
                        $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base($langKey).'"/>'."\n";
                    }
                }
                $item .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.Url::base().'"/>'."\n";
                $item .= $this->lastmod($date);
                $item .= '<changefreq>'.$change.'</changefreq>'."\n";
                $item .= '<priority>'.$priority.'</priority>'."\n";
                $item .= '</url>'."\n";
                $this->xml .= $item;
            }
        }

        return $this;
    }

    /**
     * @param string|array<string, string> $url slug KHÔNG kèm tiền tố ngôn ngữ.
     *        Từ CMS 8.2.0 mỗi ngôn ngữ có thể mang slug riêng, nên nhận thêm dạng
     *        mảng ['vi' => 'gioi-thieu', 'en' => 'about-us'] — dựng bằng
     *        `Url::localizedSlugs($object)`. Truyền chuỗi thì mọi ngôn ngữ dùng
     *        chung slug đó, đúng như trước.
     */
    public function itemUrl($url, $date, $change, $priority, array $images = []): SitemapService
    {
        if(!Language::isMulti())
        {
            //Site một ngôn ngữ: lấy slug của ngôn ngữ mặc định nếu được truyền mảng.
            $single = is_array($url) ? ($url[Language::default()] ?? reset($url)) : $url;

            $item = '<url>'."\n";
            $item .= '<loc>'.Url::base($single).'</loc>'."\n";
            $item .= $this->lastmod($date);
            $item .= '<changefreq>'.$change.'</changefreq>'."\n";
            $item .= '<priority>'.$priority.'</priority>'."\n";
            $item .= $this->images($images);
            $item .= '</url>'."\n";
            $this->xml .= $item;
        }
        else
        {
            $slugOf = function (string $language) use ($url): string {

                if(!is_array($url)) return (string) $url;

                //Ngôn ngữ chưa dịch slug thì dùng slug mặc định — URL đó vẫn phục vụ được.
                return (string) ($url[$language] ?? $url[Language::default()] ?? reset($url));
            };

            foreach (Language::listKey() as $lang)
            {
                $item = '<url>'."\n";
                $item .= '<loc>'.Url::base($lang.'/'.$slugOf($lang)).'</loc>'."\n";
                foreach (Language::listKey() as $langKey)
                {
                    $item .= '<xhtml:link rel="alternate" hreflang="'.$langKey.'" href="'.Url::base($langKey.'/'.$slugOf($langKey)).'"/>'."\n";
                }
                $item .= '<xhtml:link rel="alternate" hreflang="x-default" href="'.Url::base(Language::default().'/'.$slugOf(Language::default())).'"/>'."\n";
                $item .= $this->lastmod($date);
                $item .= '<changefreq>'.$change.'</changefreq>'."\n";
                $item .= '<priority>'.$priority.'</priority>'."\n";
                //Cùng một ảnh khai lại ở bản dịch: Google chấp nhận một ảnh nằm ở nhiều URL.
                $item .= $this->images($images);
                $item .= '</url>'."\n";
                $this->xml .= $item;
            }
        }

        return $this;
    }
}