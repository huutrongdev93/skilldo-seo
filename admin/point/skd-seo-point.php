<?php
Class SKD_Seo_Point {

    static public function metabox($object, $metabox) {

        if(is_string($metabox)) $metabox = Metabox::get($metabox);

        if($metabox['module'] == 'post_post') {
            $focusKeyword   = (have_posts($object)) ? Posts::getMeta($object->id, 'seo_focus_keyword', true) : '';
            $robots         = (have_posts($object)) ? Posts::getMeta($object->id, 'seo_robots', true) : [];
            $seo_canonical  = (have_posts($object)) ? Posts::getMeta($object->id, 'seo_canonical', true) : '';
            $seo_schema     = (have_posts($object)) ? Posts::getMeta($object->id, 'seo_schema', true) : [];
        }
        if($metabox['module'] == 'post_categories_post_categories') {
            $focusKeyword   = (have_posts($object)) ? PostCategory::getMeta($object->id, 'seo_focus_keyword', true) : '';
            $robots         = (have_posts($object)) ? PostCategory::getMeta($object->id, 'seo_robots', true) : [];
            $seo_canonical  = (have_posts($object)) ? PostCategory::getMeta($object->id, 'seo_canonical', true) : '';
            $seo_schema     = (have_posts($object)) ? PostCategory::getMeta($object->id, 'seo_schema', true) : [];
        }
        if($metabox['module'] == 'page') {
            $focusKeyword   = (have_posts($object)) ? Pages::getMeta($object->id, 'seo_focus_keyword', true) : '';
            $robots         = (have_posts($object)) ? Pages::getMeta($object->id, 'seo_robots', true) : [];
            $seo_canonical  = (have_posts($object)) ? Pages::getMeta($object->id, 'seo_canonical', true) : '';
            $seo_schema     = (have_posts($object)) ? Pages::getMeta($object->id, 'seo_schema', true) : [];
        }
        if($metabox['module'] == 'products') {
            $focusKeyword   = (have_posts($object)) ? Product::getMeta($object->id, 'seo_focus_keyword', true) : '';
            $robots         = (have_posts($object)) ? Product::getMeta($object->id, 'seo_robots', true) : [];
            $seo_canonical  = (have_posts($object)) ? Product::getMeta($object->id, 'seo_canonical', true) : '';
            $seo_schema     = (have_posts($object)) ? Product::getMeta($object->id, 'seo_schema', true) : [];
        }
        if($metabox['module'] == 'products_categories') {
            $focusKeyword   = (have_posts($object)) ? ProductCategory::getMeta($object->id, 'seo_focus_keyword', true) : '';
            $robots         = (have_posts($object)) ? ProductCategory::getMeta($object->id, 'seo_robots', true) : [];
            $seo_canonical  = (have_posts($object)) ? ProductCategory::getMeta($object->id, 'seo_canonical', true) : '';
            $seo_schema     = (have_posts($object)) ? ProductCategory::getMeta($object->id, 'seo_schema', true) : [];
        }

        $seo_index = (empty($robots['index'])) ? 'yes' : $robots['index'];

        $seo_robots = (empty($robots['robots'])) ? [] : $robots['robots'];

        $seo_schema_mode = (empty($seo_schema['mode'])) ? 'auto' : $seo_schema['mode'];

        $seo_schema_custom = (empty($seo_schema['schema'])) ? '' : $seo_schema['schema'];

        include 'views/point.php';
    }

    static public function listCriteria($key = '') {

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

    static public function save($id, $module, $data) {

        if(($module == 'post' && Admin::getPostType() == 'post') || ($module == 'post_categories' && Admin::getCateType() == 'post_categories') || $module == 'page' || $module == 'products' || $module == 'products_categories') {

            $seo_focus_keyword = trim(InputBuilder::Post('seo_focus_keyword'));


            $seo_index = trim(InputBuilder::Post('seo_index'));
            $seo_robots = InputBuilder::Post('seo_robots');
            if(!have_posts($seo_robots)) $seo_robots = [];

            $robots = ['index' => (empty($seo_index)) ? 'yes' : $seo_index];
            $robots['robots'] = $seo_robots;


            $seo_canonical = trim(InputBuilder::Post('seo_canonical'));
            $seo_canonical = str_replace(Url::base(), '', $seo_canonical);


            $seo_schema_mode = trim(InputBuilder::Post('seo_schema_mode'));
            $seo_schema_mode = (empty($seo_schema_mode)) ? 'auto' : $seo_schema_mode;

            $seo_schema_custom = trim(InputBuilder::Post('seo_schema_custom'));

            if($module == 'post') {
                Posts::updateMeta($id, 'seo_focus_keyword', $seo_focus_keyword);
                Posts::updateMeta($id, 'seo_robots', $robots);
                Posts::updateMeta($id, 'seo_canonical', $seo_canonical);
                Posts::updateMeta($id, 'seo_schema', [
                    'mode' => $seo_schema_mode,
                    'schema' => $seo_schema_custom
                ]);
            }

            if($module == 'post_categories') {
                PostCategory::updateMeta($id, 'seo_focus_keyword', $seo_focus_keyword);
                PostCategory::updateMeta($id, 'seo_robots', $robots);
                PostCategory::updateMeta($id, 'seo_canonical', $seo_canonical);
                PostCategory::updateMeta($id, 'seo_schema', [
                    'mode' => $seo_schema_mode,
                    'schema' => $seo_schema_custom
                ]);
            }

            if($module == 'page') {
                Pages::updateMeta($id, 'seo_focus_keyword', $seo_focus_keyword);
                Pages::updateMeta($id, 'seo_robots', $robots);
                Pages::updateMeta($id, 'seo_canonical', $seo_canonical);
                Pages::updateMeta($id, 'seo_schema', [
                    'mode' => $seo_schema_mode,
                    'schema' => $seo_schema_custom
                ]);
            }

            if($module == 'products') {
                Product::updateMeta($id, 'seo_focus_keyword', $seo_focus_keyword);
                Product::updateMeta($id, 'seo_robots', $robots);
                Product::updateMeta($id, 'seo_canonical', $seo_canonical);
                Product::updateMeta($id, 'seo_schema', [
                    'mode' => $seo_schema_mode,
                    'schema' => $seo_schema_custom
                ]);
            }
            if($module == 'products_categories') {
                ProductCategory::updateMeta($id, 'seo_focus_keyword', $seo_focus_keyword);
                ProductCategory::updateMeta($id, 'seo_robots', $robots);
                ProductCategory::updateMeta($id, 'seo_canonical', $seo_canonical);
                ProductCategory::updateMeta($id, 'seo_schema', [
                    'mode' => $seo_schema_mode,
                    'schema' => $seo_schema_custom
                ]);
            }

        }
    }

    static public function headerSchemaRender($schema, $page) {

        if($page == 'post_detail' || $page == 'page_detail' || $page == 'products_detail') {

            $object = get_object_current();

            if(have_posts($object)) {

                $seo_schema = [];

                if($page == 'post_detail') $seo_schema = Posts::getMeta($object->id, 'seo_schema', true);
                if($page == 'page_detail') $seo_schema = Pages::getMeta($object->id, 'seo_schema', true);
                if($page == 'products_detail') $seo_schema = Product::getMeta($object->id, 'seo_schema', true);

                $seo_schema_mode = (empty($seo_schema['mode'])) ? 'auto' : $seo_schema['mode'];

                $seo_schema_custom = (empty($seo_schema['schema'])) ? '' : $seo_schema['schema'];

                if($seo_schema_mode == 'custom') $schema = $seo_schema_custom;
            }
        }

        return $schema;
    }

    static public function headerRobotsRender($seo_helper, $page) {

        if($page == 'post_detail' || $page == 'page_detail' || $page == 'products_detail') {

            $object = get_object_current();

            if(have_posts($object)) {

                $robots = [];
                if($page == 'post_detail') $robots = Posts::getMeta($object->id, 'seo_robots', true);
                if($page == 'page_detail') $robots = Pages::getMeta($object->id, 'seo_robots', true);
                if($page == 'products_detail') $robots = Product::getMeta($object->id, 'seo_robots', true);

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

                $seo_canonical = '';
                if($page == 'post_detail') $seo_canonical = Posts::getMeta($object->id, 'seo_canonical', true);
                if($page == 'page_detail') $seo_canonical = Pages::getMeta($object->id, 'seo_canonical', true);
                if($page == 'products_detail') $seo_canonical = Product::getMeta($object->id, 'seo_canonical', true);

                if(!empty($seo_canonical)) {
                    $seo_helper->addCode('canonical', '<link rel="canonical" href="'.$seo_canonical.'" />');
                }
            }
        }

        return $seo_helper;
    }
}

if(!empty(Option::get('seo_point'))) {

    function SKD_Seo_Point($object, $metabox) {
        SKD_Seo_Point::metabox($object, $metabox);
    }

    Metabox::add('SKD_Seo_Point_Post', 'Seo', 'SKD_Seo_Point', ['module' => 'post_post']);
    if(Template::isPage('post_categories_edit') || Template::isPage('post_categories_add')) {
        Metabox::add('SKD_Seo_Point_Category', 'Seo', 'SKD_Seo_Point', ['module' => 'post_categories_post_categories']);
    }
    if(Template::isPage('products_categories_edit') || Template::isPage('products_categories_add')) {
        Metabox::add('SKD_Seo_Point_Product_Category', 'Seo', 'SKD_Seo_Point', ['module' => 'products_categories']);
    }
    Metabox::add('SKD_Seo_Point_Page', 'Seo', 'SKD_Seo_Point', ['module' => 'page']);
    Metabox::add('SKD_Seo_Point_Product', 'Seo', 'SKD_Seo_Point', ['module' => 'products']);

    add_action('save_object', 'SKD_Seo_Point::save', 10, 3);
    add_filter('schema_render', 'SKD_Seo_Point::headerSchemaRender', 10, 2);
    add_filter('seo_render', 'SKD_Seo_Point::headerRobotsRender', 10, 2);
}