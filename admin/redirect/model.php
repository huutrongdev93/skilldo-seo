<?php
Class Seo_Redirect extends \SkillDo\Model\Model {

    static string $table = 'redirect';

    static array $columns = [
        'path'          => ['string'],
        'to'            => ['string'],
        'type'          => ['string', '301'],
        'redirect'      => ['string', 0],
    ];

    static array $rules = [
        'created'           => true,
        'updated'           => true,
    ];

    static public function deleteList($redirectID = [])   {
        if(have_posts($redirectID)) {
            if(model(self::$table)::delete(Qr::set()->whereIn('id', $redirectID))) {
                do_action('delete_seo_redirect_list_trash_success', $redirectID );
                return $redirectID;
            }
        }
        return false;
    }
}