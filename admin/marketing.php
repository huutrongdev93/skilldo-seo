<?php
class SeoMarketing {
    static function systemGroup($group) {
        $group['marketing'] = [
            'label' => 'Marketing',
        ];
        return $group;
    }
    static function navigation(): void
    {
        AdminMenu::add('marketing', 'Marketing', 'system#marketing', [
            'callback' => 'admin_page_marketing_online', //function run
            'position' => 'theme', //Vị trí nằm sau menu
            'icon' => '<img src="'.Admin::imgLink('smo-icon.png').'">'
        ]);
    }
    static function script() {
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

add_filter('admin_system_groups', 'SeoMarketing::systemGroup');
add_action('admin_init', 'SeoMarketing::navigation');
add_action('admin_footer', 'SeoMarketing::script');