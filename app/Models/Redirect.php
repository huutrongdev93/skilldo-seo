<?php
namespace SkdSeo\Models;

use SkillDo\Cms\Support\Url;

Class Redirect extends \SkillDo\Database\Eloquent\Model
{
    protected string $table = 'redirect';

    protected array $columns = [
        'path'          => ['string'],
        'to'            => ['string'],
        'type'          => ['string', '301'],
        'redirect'      => ['string', 0],
    ];

    /**
     * Giá trị cột `to` có dùng được làm đích chuyển hướng không: URL đầy đủ (http/https) HOẶC đường
     * dẫn tương đối trong site (`du-an`, `/du-an`, `san-pham/abc?x=1`). Chặn scheme lạ (`javascript:`,
     * `data:`) và ký tự không thể có trong URL.
     */
    public static function isTarget(string $to): bool
    {
        $to = trim($to);

        if ($to === '' || preg_match('/[\s<>"\'\\\\]/', $to)) return false;

        if (preg_match('#^https?://#i', $to)) return true;

        // có dấu ":" trước dấu "/" đầu tiên = có scheme (javascript:, data:, mailto: …) -> không nhận
        return !preg_match('~^[^/?#]*:~', $to);
    }

    /**
     * Đích chuyển hướng thành URL tuyệt đối. Đường dẫn tương đối ghép với Url::base() — gửi thẳng
     * `Location: du-an` thì trình duyệt hiểu theo URL ĐANG MỞ (`/product/x/du-an`), còn `/du-an` thì
     * bỏ mất thư mục con khi site cài trong thư mục con (`abc.com/duan/`).
     */
    public static function targetUrl(string $to): string
    {
        $to = trim($to);

        if (preg_match('#^https?://#i', $to)) return $to;

        $to = ltrim($to, '/');

        return ($to === '' || $to === '.' || $to === './') ? Url::base() : Url::base($to);
    }
}
