<?php

namespace SkdSeo\Services;

use SkillDo\Cms\Support\Option;

class ScriptService
{
    static function header(): void
    {
        echo Option::get('header_script');
    }

    static function footer(): void
    {
        echo Option::get('footer_script');
    }
}