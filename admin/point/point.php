<?php
include 'module/post.php';
include 'module/page.php';
include 'module/category.php';
include 'module/product.php';
include 'module/product_category.php';

Class SKD_Seo_Point {

    static function module($key = '') {

        $module = [
            'post' => [
                'class' => 'SKD_Seo_Post_Point',
            ],
            'post_categories' => [
                'class' => 'SKD_Seo_Category_Point',
            ],
            'page' => [
                'class' => 'SKD_Seo_Page_Point',
            ],
        ];

        if(class_exists('sicommerce')) {
            $module['products'] = [
                'class' => 'SKD_Seo_Product_Point',
            ];
            $module['product_category'] = [
                'class' => 'SKD_Seo_Product_Category_Point',
            ];
        }

        $module = apply_filters('seo_point_admin_module_enable', $module);

        return (!empty($key)) ? Arr::get($module, $key) : $module;
    }

    static function metaBox($object, $metaBox): void
    {
        if(is_string($metaBox)) $metaBox = Metabox::get($metaBox);

        $class = SKD_Seo_Point::module($metaBox['module'].'.class');

        if(!class_exists($class)) {
            echo Admin::alert('error', 'class '.$class.' does not exist');
            return;
        }

        $class = new $class();

        $focusKeyword   = (have_posts($object)) ? $class->getFocusKeyword($object->id) : '';

        $robots         = (have_posts($object)) ? $class->getRobots($object->id) : [];

        $seo_canonical  = (have_posts($object)) ? $class->getCanonical($object->id) : '';

        $seo_schema     = (have_posts($object)) ? $class->getSchema($object->id) : [];

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

        Plugin::view('skd-seo', 'views/point/point', [
            'formRobots'    => $formRobots,
            'formCanonical' => $formCanonical,
            'formSchema'    => $formSchema,
            'focusKeyword' => $focusKeyword,
        ]);
    }

    static function listCriteria($key = '') {

        $listCriteria = [
            'keywordNotUsed' => 'Đặt Từ khóa tập trung cho nội dung này.',
            'keywordInTitle' => 'Thêm từ khóa chính vào tiêu đề SEO.',
            'titleStartWithKeyword' => 'Sử dụng từ khóa chính gần đầu tiêu đề SEO.',
            'lengthTitle' => 'Tiêu đề của bài viết phải lớn hơn 40 ký tự và khuyến cáo nhỏ hơn 70 ký tự',
            'keywordInMetaDescription' => 'Thêm Từ khóa tập trung vào Mô tả meta SEO của bạn.',
            'lengthMetaDescription' => 'Mô tả meta SEO của bạn nên có từ 155 đến 160 ký tự.',
            'keywordInPermalink' => 'Sử dụng từ khóa chính trong URL.',
            'lengthPermalink' => 'URL không khả dụng. Thêm URL ngắn.',
            'keywordIn10Percent' => 'Sử dụng từ khóa chính ở đầu nội dung của bạn.',
            'keywordInContent' => 'Sử dụng từ khóa chính trong nội dung.',
            'lengthContent' => 'Nội dung phải dài 600-2500 từ.',
            'linksHasInternal' => 'Thêm liên kết nội bộ vào nội dung của bạn.',
            'keywordInSubheadings' => 'Sử dụng từ khóa chính trong (các) tiêu đề phụ như H2, H3, H4, v.v..',
            'keywordInImageAlt' => 'Thêm từ khóa vào thuộc tính alt của hình ảnh',
            'keywordDensity' => 'Mật độ từ khóa là 0. Nhắm đến khoảng 1% Mật độ từ khóa.',
            'contentHasShortParagraphs' => 'Thêm các đoạn văn ngắn và súc tích để dễ đọc và UX hơn.',
            'contentHasAssets' => 'Thêm một vài hình ảnh để làm cho nội dung của bạn hấp dẫn.',
        ];

        if(!empty($key)) return Arr::get($listCriteria, $key);

        return $listCriteria;
    }

    static function save($id, $module, \SkillDo\Http\Request $request): void
    {
        $class = SKD_Seo_Point::module($module.'.class');

        if(!is_null($class) && class_exists($class)) {

            $class = new $class();

            $seo_focus_keyword = $request->input('seo_focus_keyword');

            $seo_index = $request->input('seo_index');

            $seo_robots = $request->input('seo_robots');

            if(!have_posts($seo_robots)) $seo_robots = [];

            $robots = ['index' => (empty($seo_index)) ? 'yes' : $seo_index];

            $robots['robots'] = $seo_robots;

            $seo_canonical = $request->input('seo_canonical');

            if(!empty($seo_canonical)) {
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

    static function schemaRender($schema, $page) {

        $object = get_object_current();

        if(have_posts($object)) {

            $modules = SKD_Seo_Point::module();

            $seo_schema = false;

            foreach($modules as $module) {

                if(!class_exists($module['class'])) {
                    continue;
                }

                $class = new $module['class'];

                $seo_schema = $class->schemaRender($object, $page);

                if(have_posts($seo_schema)) {
                    break;
                }
            }

            if($seo_schema === false) {
                return $schema;
            }

            $seo_schema = [];

            $seo_schema = apply_filters('seo_schema', $seo_schema, $object);

            $seo_schema_mode = (empty($seo_schema['mode'])) ? 'auto' : $seo_schema['mode'];

            $seo_schema_custom = (empty($seo_schema['schema'])) ? '' : $seo_schema['schema'];

            if($seo_schema_mode == 'custom') {
                $schema = $seo_schema_custom;
            }
        }

        return $schema;
    }

    static function seoRender($seo_helper, $page) {

        $object = get_object_current();

        if(have_posts($object)) {

            $modules = SKD_Seo_Point::module();

            $seo = true;

            foreach($modules as $module) {

                if(!class_exists($module['class'])) {
                    continue;
                }

                $class = new $module['class'];

                $seo = $class->seoRender($object, $page);

                if(have_posts($seo)) {
                    break;
                }
            }

            if($seo === false) {
                return $seo_helper;
            }

            $robots = $seo['robots'];

            $robots = apply_filters('seo_robots', $robots, $object);

            $robotText = '';

            if(!empty($robots['index']) && $robots['index'] == 'no') $robotText .= 'noindex,';

            if(!empty($robots['robots']) && have_posts($robots['robots'])) {

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
                $seo_helper->addCode('canonical', '<link rel="canonical" href="'.$seo_canonical.'" />');
            }
        }

        return $seo_helper;
    }
}

if(!empty(Option::get('seo_point'))) {

    function SKD_Seo_Point_Create(): void
    {
        $seoPointSupport = Option::get('seo_point_support');

        foreach ($seoPointSupport as $seoPoint) {

            $module = $seoPoint;

            if(Str::startsWith($seoPoint, 'post_categories_')) {

                $index = str_replace('post_categories_', '', $seoPoint);

                if($index == Admin::getCateType()) {
                    $module = 'post_categories';
                }
            }
            else if(Str::startsWith($seoPoint, 'post_')) {

                $index = str_replace('post_', '', $seoPoint);

                if($index == Admin::getPostType()) {
                    $module = 'post';
                }
            }

            if(!empty(SKD_Seo_Point::module($module))) {
                Metabox::add('SKD_Seo_Point_'.$module, 'Seo', 'SKD_Seo_Point::metaBox', ['module' => $module]);
            }
        }
    }

    add_action('admin_init', 'SKD_Seo_Point_Create');
    add_action('save_object', 'SKD_Seo_Point::save', 10, 3);
    add_filter('schema_render', 'SKD_Seo_Point::schemaRender', 10, 2);
    add_filter('seo_render', 'SKD_Seo_Point::seoRender', 10, 2);
}
