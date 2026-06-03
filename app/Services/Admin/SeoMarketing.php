<?php
namespace SkdSeo\Services\Admin;

use SkillDo\Cms\Menu\AdminMenu;
use SkillDo\Cms\Menu\AdminMenuStore;
use SkillDo\Cms\Support\Image;

class SeoMarketing
{
    static function systemGroup($group)
    {
        $group['marketing'] = [
            'label' => 'Marketing',
        ];
        return $group;
    }

    static function navigation(): void
    {
        AdminMenu::add('marketing', 'Marketing', 'system#marketing', [
            'callback' => 'admin_page_marketing_online', //function run
            'position' => 50, //Vị trí nằm sau menu
            'icon' => '<img src="'.Image::admin('smo-icon.png')->link().'">'
        ]);
    }

    static function script()
    {
        ?>
        <script>
            $(function() {
                let box = $('#adminmenu li a[href="admin/system#marketing"]').closest('li');
                if(typeof box.find('.submenu').html() == 'undefined') {
                    box.remove();
                }
            });
        </script>
        <?php
    }
}