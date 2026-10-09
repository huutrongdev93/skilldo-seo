<?php
namespace SkdSeo\Supports;

use SkillDo\Cms\Support\Metabox;
use SkillDo\Cms\Support\Option;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SeoPoint
{
    static function module($key = '')
    {
        $module = [
            'post' => [
                'class' => \SkdSeo\Modules\Point\Modules\Post::class,
                'model' => \SkillDo\Cms\Models\Post::class,
            ],
            'post_categories' => [
                'class' => \SkdSeo\Modules\Point\Modules\Category::class,
                'model' => \SkillDo\Cms\Models\PostCategory::class,
            ],
            'page' => [
                'class' => \SkdSeo\Modules\Point\Modules\Page::class,
                'model' => \SkillDo\Cms\Models\Page::class,
            ],
        ];

        /*
        | Sản phẩm nằm ở plugin sicommerce nhưng danh sách module hỗ trợ trong
        | Cấu hình > Seo lại khai cứng hai key này, nên đăng ký luôn ở đây thay
        | vì đi qua filter. Bắt buộc kiểm tra class_exists: hai lớp bên dưới gọi
        | thẳng model của sicommerce, site không cài bán hàng mà vẫn đăng ký thì
        | AdminPoint sẽ fatal khi khởi tạo chúng.
        */
        if(class_exists(\Ecommerce\Models\Product::class))
        {
            $module['products'] = [
                'class' => \SkdSeo\Modules\Point\Modules\Product::class,
                'model' => \Ecommerce\Models\Product::class,
            ];

            $module['products_categories'] = [
                'class' => \SkdSeo\Modules\Point\Modules\ProductCategory::class,
                'model' => \Ecommerce\Models\ProductCategory::class,
            ];
        }

        $module = apply_filters('seo_point_admin_module_enable', $module);

        return (!empty($key)) ? Arr::get($module, $key) : $module;
    }

    /**
     * Danh sách tiêu chí chấm điểm (key => câu gợi ý khi CHƯA đạt).
     *
     * Bộ 2026 (6.1.0) bỏ các tiêu chí Google đã nói rõ không phải yếu tố xếp
     * hạng: mật độ từ khóa 0.75–2.5% (nay chỉ cảnh báo khi NHỒI từ khóa), khung
     * 600–2500 từ (nay chỉ bắt lỗi nội dung mỏng), từ khóa trong alt ảnh (nay
     * chỉ cần mọi ảnh có alt), "ít nhất 2 ảnh" (nay là có ảnh đại diện). Key của
     * các tiêu chí còn giữ ý nghĩa được giữ nguyên để filter seo_point_criteria
     * của plugin khác không gãy.
     *
     * Thêm tiêu chí mới phải khai cả ở đây, ở weights(), lẫn nhánh chấm trong
     * views/point/point.blade.php.
     */
    static function listCriteria($key = '') {

        $listCriteria = [
            //Từ khóa
            'keywordNotUsed'            => 'Đặt từ khóa chính cho nội dung này.',
            'keywordUnique'             => 'Từ khóa chính chưa được dùng cho nội dung khác.',
            'keywordInTitle'            => 'Thêm từ khóa chính vào tiêu đề hiển thị trên Google.',
            'titleStartWithKeyword'     => 'Đưa từ khóa chính về nửa đầu của tiêu đề.',
            'keywordInMetaDescription'  => 'Thêm từ khóa chính vào mô tả.',
            'keywordInPermalink'        => 'Sử dụng từ khóa chính trong đường dẫn.',
            'keywordIn10Percent'        => 'Nhắc tới từ khóa chính ngay ở đoạn mở đầu nội dung.',
            'keywordInContent'          => 'Sử dụng từ khóa chính trong nội dung.',
            'keywordInSubheadings'      => 'Sử dụng từ khóa chính trong ít nhất một tiêu đề phụ (H2, H3...).',
            'keywordStuffing'           => 'Không lặp lại từ khóa quá dày đặc.',
            //Tiêu đề, mô tả, đường dẫn
            'lengthTitle'               => 'Tiêu đề nên dài từ 30 ký tự và không bị Google cắt bớt.',
            'lengthMetaDescription'     => 'Mô tả nên dài từ 110 đến 160 ký tự.',
            'lengthPermalink'           => 'Đường dẫn nên ngắn gọn (dưới 75 ký tự).',
            //Nội dung
            'contentNotThin'            => 'Nội dung quá ngắn, hãy bổ sung thông tin hữu ích.',
            'noH1InContent'             => 'Nội dung không nên có thẻ H1.',
            'contentHasSubheadings'     => 'Chia nội dung dài bằng các tiêu đề phụ H2, H3.',
            'contentHasShortParagraphs' => 'Chia nội dung thành các đoạn văn ngắn để dễ đọc.',
            'contentHasLists'           => 'Dùng danh sách hoặc bảng để trình bày ý chính.',
            'linksHasInternal'          => 'Thêm liên kết nội bộ tới trang khác trong website.',
            'linksHasExternal'          => 'Dẫn nguồn tới một website uy tín bên ngoài.',
            'imagesHaveAlt'             => 'Mọi hình ảnh trong nội dung đều có thuộc tính alt.',
            'hasFeaturedImage'          => 'Đặt ảnh đại diện cho nội dung.',
        ];

        if(!empty($key)) return Arr::get($listCriteria, $key);

        return $listCriteria;
    }

    /**
     * Trọng số của từng tiêu chí. Điểm = tổng trọng số tiêu chí đạt / tổng
     * trọng số tiêu chí đang được chấm, nên rút gọn danh sách (filter
     * seo_point_criteria) không làm điểm tối đa nhỏ hơn 100.
     *
     * Key không có ở đây (tiêu chí do plugin khác thêm) nhận trọng số 1.
     */
    static function weights(string $module = ''): array
    {
        $weights = [
            'keywordNotUsed'            => 8,
            'keywordUnique'             => 6,
            'keywordInTitle'            => 10,
            'titleStartWithKeyword'     => 3,
            'keywordInMetaDescription'  => 5,
            'keywordInPermalink'        => 5,
            'keywordIn10Percent'        => 6,
            'keywordInContent'          => 8,
            'keywordInSubheadings'      => 5,
            'keywordStuffing'           => 4,
            'lengthTitle'               => 8,
            'lengthMetaDescription'     => 6,
            'lengthPermalink'           => 3,
            'contentNotThin'            => 6,
            'noH1InContent'             => 4,
            'contentHasSubheadings'     => 4,
            'contentHasShortParagraphs' => 3,
            'contentHasLists'           => 2,
            'linksHasInternal'          => 5,
            'linksHasExternal'          => 2,
            'imagesHaveAlt'             => 4,
            'hasFeaturedImage'          => 5,
        ];

        $weights = apply_filters('seo_point_weights', $weights, $module);

        return is_array($weights) ? $weights : [];
    }

    /**
     * Mức quan trọng hiển thị cạnh từng tiêu chí, suy ra từ trọng số để hai thứ
     * không bao giờ lệch nhau: đổi trọng số qua seo_point_weights thì nhãn tự đổi.
     *
     * @return array{key: string, label: string, hint: string}
     */
    static function importance(int|float $weight): array
    {
        if($weight >= 8)
        {
            return ['key' => 'high', 'label' => 'Rất quan trọng', 'hint' => 'Ảnh hưởng lớn tới thứ hạng, nên xử lý trước.'];
        }

        if($weight >= 5)
        {
            return ['key' => 'medium', 'label' => 'Quan trọng', 'hint' => 'Nên đạt để nội dung cạnh tranh tốt.'];
        }

        return ['key' => 'low', 'label' => 'Nên có', 'hint' => 'Giúp nội dung tốt hơn, ảnh hưởng nhỏ tới điểm.'];
    }

    /**
     * Thứ tự hiển thị trong metabox: quan trọng nhất lên đầu.
     *
     * keywordNotUsed (đặt từ khóa chính) luôn đứng đầu dù trọng số thấp: chưa
     * có từ khóa thì gần nửa danh sách không chấm được, đó là việc phải làm
     * trước tiên. Sắp xếp của PHP 8 ổn định nên cùng trọng số giữ thứ tự gốc.
     */
    static function sortByImportance(array $criteria, array $weights): array
    {
        uksort($criteria, function ($a, $b) use ($weights) {

            if($a === 'keywordNotUsed') return -1;

            if($b === 'keywordNotUsed') return 1;

            return ($weights[$b] ?? 1) <=> ($weights[$a] ?? 1);
        });

        return $criteria;
    }

    /**
     * Ngưỡng chấm điểm theo module, đưa thẳng xuống JS.
     *
     * - minWords: dưới số từ này là nội dung mỏng. Mô tả sản phẩm / danh mục
     *   thường ngắn hơn bài viết nên ngưỡng thấp hơn.
     * - longWords: từ số từ này trở lên mới đòi tiêu đề phụ và danh sách.
     *
     * "Từ" ở đây là số cụm cách nhau bởi khoảng trắng, với tiếng Việt tức là
     * số âm tiết.
     */
    static function settings(string $module = ''): array
    {
        $short = in_array($module, ['products', 'products_categories', 'post_categories', 'travel_category', 'tag']);

        $settings = [
            'minWords'  => $short ? 150 : 300,
            'longWords' => 300,
        ];

        $settings = apply_filters('seo_point_settings', $settings, $module);

        return is_array($settings) ? $settings : [];
    }

    /**
     * Các đối tượng KHÁC cùng module đang dùng $keyword làm từ khóa chính.
     *
     * Chỉ chạy khi registry module có khai 'model' (class model có trait
     * ModelMeta). Module do plugin khác đăng ký mà không khai thì trả rỗng,
     * tức là coi như không trùng.
     *
     * @return array<int, string> [id => tên đối tượng]
     */
    static function duplicateKeyword(string $module, string $keyword, int $excludeId = 0, int $limit = 3): array
    {
        $keyword = trim(Str::lower($keyword));

        $model = static::module($module.'.model');

        if($keyword === '' || empty($model) || !class_exists($model))
        {
            return [];
        }

        $table = (new $model)->getTable();

        //Cùng quy tắc chọn bảng với Metadata::hasMetaTable()
        if(schema()->hasTable($table.'_metadata'))
        {
            $query = DB::table($table.'_metadata');
        }
        else
        {
            $query = DB::table('metabox')->where('object_type', $table);
        }

        $ids = $query->where('meta_key', 'seo_focus_keyword')
            ->whereRaw('LOWER(TRIM(meta_value)) = ?', [$keyword])
            ->where('object_id', '<>', $excludeId)
            ->limit(20)
            ->pluck('object_id')
            ->all();

        if(empty($ids))
        {
            return [];
        }

        $result = [];

        //metadata của bản ghi đã xóa vẫn còn nằm lại: chỉ tính bản ghi còn tồn tại
        $query = $model::query()->whereIn('id', $ids);

        /*
        | Tính cả bản ghi đang ẩn (bản nháp). Trong admin global scope `public = 1`
        | tự tắt nên vốn đã thấy; ngoài admin (tool MCP seo_score) scope bật và bỏ
        | sót bản nháp trùng từ khóa. Đặt điều kiện trên cột public thì scope nhường.
        */
        if(in_array('public', schema()->getColumnListing($table), true))
        {
            $query->whereIn('public', [0, 1]);
        }

        foreach ($query->limit($limit)->get() as $item)
        {
            $result[$item->id] = (string)($item->title ?? $item->name ?? ('#'.$item->id));
        }

        return $result;
    }

    /**
     * Bộ tiêu chí chấm điểm áp dụng cho MỘT module.
     *
     * Không phải form nào cũng có đủ trường để chấm 17 tiêu chí — form thẻ chẳng
     * hạn, không có trình soạn thảo nội dung nên mọi tiêu chí về nội dung,
     * heading, ảnh đều không bao giờ đạt. Module tự rút gọn danh sách qua filter
     * `seo_point_criteria`, điểm số được chia lại theo số tiêu chí còn lại.
     */
    static function criteria(string $module = ''): array
    {
        $criteria = apply_filters('seo_point_criteria', static::listCriteria(), $module);

        return (is_array($criteria) && !empty($criteria)) ? $criteria : static::listCriteria();
    }

    static function registerMetabox(): void
    {
        $seoPointSupport = Option::get('seo_point_support');

        if(!is_array($seoPointSupport)) return;

        foreach ($seoPointSupport as $seoPoint)
        {
            $module = $seoPoint;

            if(Str::startsWith($seoPoint, 'post_categories_'))
            {
                $index = str_replace('post_categories_', '', $seoPoint);

                if($index == request()->input('cate_type'))
                {
                    $module = 'post_categories';
                }
            }
            else if(Str::startsWith($seoPoint, 'post_'))
            {
                $index = str_replace('post_', '', $seoPoint);

                if($index == request()->input('post_type'))
                {
                    $module = 'post';
                }
            }

            if(!empty(SeoPoint::module($module)))
            {
                Metabox::add('SKD_Seo_Point_'.$module, 'Seo', 'SkdSeo\Modules\Point\AdminPoint::metaBox', ['module' => $module]);
            }
        }
    }
}