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
            'focusKeyword' => $focusKeyword,
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

    static function schemaRender($schema, $page) {

        $object = Cms::getData('object');

        if(hasItems($object))
        {
            $modules = SeoPoint::module();

            $seo_schema = false;

            foreach($modules as $module) {

                if(!class_exists($module['class'])) {
                    continue;
                }

                $class = new $module['class'];

                $seo_schema = $class->schemaRender($object, $page);

                if(hasItems($seo_schema)) {
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

        $object = Cms::getData('object');

        if(hasItems($object)) {

            $modules = SeoPoint::module();

            $seo = true;

            foreach($modules as $module) {

                if(!class_exists($module['class'])) {
                    continue;
                }

                $class = new $module['class'];

                $seo = $class->seoRender($object, $page);

                if(hasItems($seo)) {
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
                $seo_helper->addCode('canonical', '<link rel="canonical" href="'.$seo_canonical.'" />');
            }
        }

        return $seo_helper;
    }
}