<?php
if(!Admin::is()) return;
function Seo_update_core(): void
{
    if(Admin::is() && Auth::check() ) {
        $version = Option::get('seo_version');
        $version = (empty($version)) ? '3.0.2' : $version;
        if (version_compare( SKD_SEO_VERSION, $version ) === 1 ) {
            $update = new Seo_Update_Version();
            $update->runUpdate($version);
        }
    }
}
add_action('admin_init', 'Seo_update_core');

Class Seo_Update_Version {

    public function runUpdate($seoVersion): void
    {
        $listVersion  = ['3.1.0', '3.3.2', '3.4.0'];
        foreach ($listVersion as $version ) {
            if(version_compare( $version, $seoVersion ) == 1) {
                $function = 'update_Version_'.str_replace('.','_',$version);
                if(method_exists($this, $function)) $this->$function();
            }
        }
        Option::update('seo_version', SKD_SEO_VERSION );
    }

    public function update_Version_3_1_0(): void
    {
        $database = include "database/db_v3.1.0.php";
        $database->up();
    }

    public function update_Version_3_3_2(): void
    {
        $database = include "database/db_v3.3.2.php";
        $database->up();
    }

    public function update_Version_3_4_0(): void
    {
        $database = include "database/db_v3.4.0.php";
        $database->up();
    }
}