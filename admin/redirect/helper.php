<?php
class Seo_Redirect_Helper {

    static function build($object): void
    {
        $jsonFile = 'views/plugins/skd-seo/assets/redirect.json';

        $json = [];

        if(file_exists($jsonFile)) {

            $json = json_decode(file_get_contents($jsonFile));

            if(!have_posts($json)) {
                $json = [];
            }
        }

        if($object->redirect == 0 && !empty($object->to)) {

            $isExits = false;

            foreach($json as $key => $value) {
                if($value->from == $object->path) {
                    $isExits = true;
                    $value->to = $object->to;
                    break;
                }
            }

            if(!$isExits) {

                $json[] = [
                    'from' => $object->path,
                    'to' => $object->to
                ];
            }
        }
        else {
            foreach($json as $key => $value) {
                if($value->from == $object->path) {
                    unset($json[$key]);
                    break;
                }
            }
        }

        file_put_contents($jsonFile, json_encode($json));
    }

    static function buildRemove($path): void
    {
        $jsonFile = 'views/plugins/skd-seo/assets/redirect.json';

        if(file_exists($jsonFile)) {

            $json = json_decode(file_get_contents($jsonFile));

            if(have_posts($json)) {

                foreach($json as $key => $value) {

                    if($value->from == $path) {
                        unset($json[$key]);
                        break;
                    }
                }

                file_put_contents($jsonFile, json_encode($json));
            }
        }
    }
}