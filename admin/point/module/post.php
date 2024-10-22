<?php
class SKD_Seo_Post_Point {

    function __construct() {
    }

    public function getFocusKeyword($id) {
        return Posts::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id) {
        return Posts::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id) {
        return Posts::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id) {
        return Posts::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data) {
        return Posts::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data) {
        return Posts::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data) {
        return Posts::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data) {
        return Posts::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object) {

        if($page != 'post_detail') {

            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {

        if($page != 'post_detail') {

            return false;
        }

        return [
            'robots' => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}