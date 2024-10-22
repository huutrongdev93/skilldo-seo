<?php
class SKD_Seo_Page_Point {

    function __construct() {
    }

    public function getFocusKeyword($id) {
        return Pages::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id) {
        return Pages::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id) {
        return Pages::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id) {
        return Pages::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data) {
        return Pages::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data) {
        return Pages::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data) {
        return Pages::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data) {
        return Pages::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object) {

        if($page != 'page_detail') {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'page_detail') {
            return false;
        }

        return [
            'robots' => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}