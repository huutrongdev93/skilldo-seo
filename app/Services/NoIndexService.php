<?php
namespace SkdSeo\Services;

use SkillDo\Cms\Support\Option;

/**
 * Chặn toàn bộ website khỏi công cụ tìm kiếm.
 *
 * Dùng cho site demo / site đang dựng: nội dung chưa phải bản chính thức nhưng
 * vẫn chạy trên tên miền thật, để Google lập chỉ mục là dính trùng lặp nội dung
 * với site thật của khách.
 *
 * Bật lên thì chặn ở cả ba lớp, vì mỗi lớp bịt một lỗ khác nhau:
 *
 * 1. `<meta name="robots">` — lớp duy nhất crawler chắc chắn tuân theo với
 *    trang HTML. Ghi ở priority 999 của `seo_render` để thắng cả thiết lập tay
 *    của biên tập viên (`AdminPoint::seoRender` chạy ở 99).
 * 2. Header `X-Robots-Tag` — dành cho thứ không có thẻ head: sitemap.xml,
 *    llms.txt, file tải về, ảnh.
 * 3. `robots.txt` = `Disallow: /` — cắt luôn việc thu thập, đỡ tốn băng thông
 *    và là chỗ người ta hay mở ra kiểm tra xem site đã bị chặn chưa.
 *
 * Lưu ý đánh đổi của lớp 3: chặn thu thập thì crawler không đọc được meta
 * noindex nữa, nên một URL bị site khác trỏ tới vẫn có thể lọt vào kết quả tìm
 * kiếm dưới dạng chỉ có link, không có nội dung. Với site demo thì chấp nhận
 * được; site thật muốn gỡ hẳn khỏi kết quả tìm kiếm thì phải để crawler vào đọc
 * meta noindex — bỏ lớp 3 bằng filter `skd_seo_robots_content`.
 */
class NoIndexService
{
    const OPTION = 'seo_noindex';

    static function enabled(): bool
    {
        return !empty(Option::get(self::OPTION));
    }

    /**
     * Chỉ thị dùng chung cho meta robots và header X-Robots-Tag
     */
    static function directive(): string
    {
        return apply_filters('seo_noindex_directive', 'noindex, nofollow, noarchive');
    }

    /**
     * Ghi đè meta robots của trang.
     *
     * `HeadService::addMeta` dedupe theo tên nên thẻ này thay thế mọi giá trị
     * robots đã đặt trước đó thay vì thêm thẻ thứ hai.
     */
    static function seoRender($headService, $page = null)
    {
        $headService->addMeta('robots', self::directive());

        return $headService;
    }

    /**
     * Thay toàn bộ nội dung robots.txt bằng lệnh chặn.
     */
    static function robots($content): string
    {
        return '# Website đang bật chế độ chặn lập chỉ mục (Cấu hình > Seo > Chặn lập chỉ mục)'."\n"
            .'User-agent: *'."\n"
            .'Disallow: /'."\n";
    }

    /**
     * Gửi X-Robots-Tag cho mọi phản hồi ngoài trang (kể cả file không có head).
     */
    static function httpHeader(): void
    {
        if(headers_sent())
        {
            return;
        }

        header('X-Robots-Tag: '.self::directive(), true);
    }
}
