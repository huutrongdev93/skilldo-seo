<?php

use SkdSeo\Modules\Point\AdminPoint;
use SkdSeo\Supports\SeoPoint;
use SkillDo\Cms\Support\Option;

if(Admin::is() && !empty(Option::get('seo_point')))
{
    add_action('add_meta_box', [SeoPoint::class, 'registerMetabox']);
    add_action('save_object', [AdminPoint::class, 'save'], 10, 3);
    add_filter('schema_render', [AdminPoint::class, 'schemaRender'], 10, 2);
    add_filter('seo_render', [AdminPoint::class, 'seoRender'], 10, 2);
}