<?php
namespace SkdSeo\Modules\Point;

use SkdSeo\Supports\SeoPoint;
use SkillDo\Cms\Plugin\Plugin;
use SkillDo\Cms\Support\Admin;
use SkillDo\Cms\Support\Cms;
use SkillDo\Cms\Support\Metabox;
use SkillDo\Cms\Support\Url;

Class AdminPoint
{
    static function metaBox($object, $metaBox): void
    {
        if(is_string($metaBox)) $metaBox = Metabox::get($metaBox);

        $class = SeoPoint::module($metaBox['module'].'.class');

        if(!class_exists($class))
        {
            echo Admin::alert('error', 'class '.$class.' does not exist');
            return;
        }

        $class = new $class();

        $focusKeyword   = (hasItems($object)) ? $class->getFocusKeyword($object->id) : '';

        $robots         = (hasItems($object)) ? $class->getRobots($object->id) : [];

        $seo_canonical  = (hasItems($object)) ? $class->getCanonical($object->id) : '';

        $seo_schema     = (hasItems($object)) ? $class->getSchema($object->id) : [];

        $seo_index = (empty($robots['index'])) ? 'yes' : $robots['index'];

        $seo_robots = (empty($robots['robots'])) ? [] : $robots['robots'];

        $seo_schema_mode = (empty($seo_schema['mode'])) ? 'auto' : $seo_schema['mode'];

        $seo_schema_custom = (empty($seo_schema['schema'])) ? '' : $seo_schema['schema'];

        //Tab seo advanced
        $formRobots = form();
        $formRobots->radio('seo_index', ['no' => 'No Index', 'yes' => 'Index'], [
            'label' => 'Index meta',
            'start' => 6
        ], $seo_index);
        $formRobots->checkbox('seo_robots', [
            'noFollow'  => 'No Follow',
            'noArchive' => 'No Archive',
            'noImage'   => 'No Image Index',
            'noSnippet' => 'No Snippet',
        ], [
            'label' => 'Meta order',
            'start' => 6
        ], $seo_robots);

        $formCanonical = form();
        $formCanonical->text('seo_canonical', ['label' => 'Canonical URL'], $seo_canonical);

        //schema
        $formSchema = form();
        $formSchema->radio('seo_schema_mode', ['auto' => 'Hệ thống tự động', 'custom' => 'Thủ công'], ['label' => 'Sử dụng'], $seo_schema_mode);
        $formSchema->code('seo_schema_custom', ['label' => 'Schema thủ công', 'language' => 'javascript'], $seo_schema_custom);

        Plugin::view('skd-seo', 'point/point', [
            'formRobots'    => $formRobots,
            'formCanonical' => $formCanonical,
            'formSchema'    => $formSchema,
            'focusKeyword'  => $focusKeyword,
            //Mỗi module có bộ tiêu chí riêng, xem SeoPoint::criteria()
            'criteria'      => SeoPoint::criteria($metaBox['module'] ?? ''),
        ]);
    }

    static function save($id, $module, \SkillDo\Http\Request $request): void
    {
        $class = SeoPoint::module($module.'.class');

        if(!is_null($class) && class_exists($class)) {

            $class = new $class();

            $seo_focus_keyword = $request->input('seo_focus_keyword');

            $seo_index = $request->input('seo_index');

            $seo_robots = $request->input('seo_robots');

            if(!hasItems($seo_robots)) $seo_robots = [];

            $robots = ['index' => (empty($seo_index)) ? 'yes' : $seo_index];

            $robots['robots'] = $seo_robots;

            $seo_canonical = $request->input('seo_canonical');

            if(!empty($seo_canonical))
            {
                $seo_canonical = str_replace(Url::base(), '', $seo_canonical);
            }

            $seo_schema_mode = $request->input('seo_schema_mode');

            $seo_schema_mode = (empty($seo_schema_mode)) ? 'auto' : $seo_schema_mode;

            $seo_schema_custom = $request->input('seo_schema_custom');

            $class->setFocusKeyword($id, $seo_focus_keyword);

            $class->setRobots($id, $robots);

            $class->setCanonical($id, $seo_canonical);

            $class->setSchema($id, [
                'mode' => $seo_schema_mode,
                'schema' => $seo_schema_custom
            ]);
        }
    }

    /**
     * Đối tượng đang hiển thị.
     *
     * Mặc định là Cms::getData('object') — quy ước của trang CHI TIẾT
     * (post/page/sản phẩm).
     *
     * Trang LƯU TRỮ (danh mục bài viết, danh mục sản phẩm, thẻ) không đặt data-bag
     * đó, đối tượng của trang nằm ở 'category'. Thiếu nhánh dự phòng này thì mọi
     * thiết lập No Index / Canonical / Schema thủ công của danh mục đều lưu được
     * trong admin nhưng không bao giờ xuất ra ngoài trang.
     *
     * Không sợ nhầm trang: trang chi tiết cấp cả hai biến nên 'object' luôn thắng,
     * và mỗi module còn tự kiểm tra tên trang + kiểu đối tượng của nó.
     *
     * Plugin đặt dữ liệu ở data-bag khác (travel dùng 'tour', 'archive') ánh xạ
     * lại qua filter seo_point_object.
     */
    protected static function object($page)
    {
        $object = Cms::getData('object');

        if(noItems($object))
        {
            $object = Cms::getData('category');
        }

        return apply_filters('seo_point_object', $object, $page);
    }

    static function schemaRender($schema, $page) {

        $object = self::object($page);

        if(hasItems($object))
        {
            $modules = SeoPoint::module();

            $seo_schema = false;

            foreach($modules as $module) {

                if(!class_exists($module['class'])) {
                    continue;
                }

                $class = new $module['class'];

                $seo_schema = $class->schemaRender($page, $object);

                if(hasItems($seo_schema)) {
                    break;
                }
            }

            if(!hasItems($seo_schema)) {
                return $schema;
            }

            $seo_schema = apply_filters('seo_schema', $seo_schema, $object);

            $seo_schema_mode = (empty($seo_schema['mode'])) ? 'auto' : $seo_schema['mode'];

            $seo_schema_custom = (empty($seo_schema['schema'])) ? '' : $seo_schema['schema'];

            if($seo_schema_mode == 'custom' && !empty($seo_schema_custom)) {

                /*
                | Schema thủ công lưu dưới dạng chuỗi JSON. Phải decode về mảng
                | các node: Schema::render() foreach trên biến này, gán thẳng
                | chuỗi vào sẽ làm vỡ toàn bộ thẻ ld+json của trang.
                */
                $custom = json_decode($seo_schema_custom, true);

                if(is_array($custom) && !empty($custom)) {

                    $schema = (isset($custom[0]) && is_array($custom[0])) ? $custom : [$custom];
                }
            }
        }

        return $schema;
    }

    static function seoRender($seo_helper, $page) {

        $object = self::object($page);

        if(hasItems($object)) {

            $modules = SeoPoint::module();

            $seo = false;

            foreach($modules as $module) {

                if(!class_exists($module['class'])) {
                    continue;
                }

                $class = new $module['class'];

                $seo = $class->seoRender($page, $object);

                if(hasItems($seo)) {
                    break;
                }
            }

            if(!hasItems($seo)) {
                return $seo_helper;
            }

            $robots = $seo['robots'];

            $robots = apply_filters('seo_robots', $robots, $object);

            $robotText = '';

            if(!empty($robots['index']) && $robots['index'] == 'no') $robotText .= 'noindex,';

            if(!empty($robots['robots']) && hasItems($robots['robots'])) {

                if(in_array('noFollow', $robots['robots']) !== false) {
                    $robotText .= 'nofollow,';
                }
                if(in_array('noArchive', $robots['robots']) !== false) {
                    $robotText .= 'noarchive,';
                }
                if(in_array('noImage', $robots['robots']) !== false) {
                    $robotText .= 'noimageindex,';
                }
                if(in_array('noSnippet', $robots['robots']) !== false) {
                    $robotText .= 'nosnippet,';
                }

                $robotText =trim($robotText, ',');
            }

            if(!empty($robotText)) {
                $seo_helper->addMeta('robots', $robotText);
            }

            $seo_canonical = $seo['canonical'];

            $seo_canonical = apply_filters('seo_canonical', $seo_canonical, $object);

            if(!empty($seo_canonical)) {

                //Lúc lưu đã cắt bỏ domain — canonical bắt buộc phải là URL tuyệt đối
                if(!Url::is($seo_canonical)) {
                    $seo_canonical = Url::base($seo_canonical);
                }

                $seo_helper->addCode('canonical', '<link rel="canonical" href="'.$seo_canonical.'" />');
            }
        }

        return $seo_helper;
    }
}