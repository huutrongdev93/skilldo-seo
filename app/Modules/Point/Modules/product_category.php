<?php
class SKD_Seo_Product_Category_Point {

    function __construct() {
    }

    public function getFocusKeyword($id) {
        return ProductCategory::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id) {
        return ProductCategory::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id) {
        return ProductCategory::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id) {
        return ProductCategory::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data) {
        return ProductCategory::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data) {
        return ProductCategory::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data) {
        return ProductCategory::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data) {
        return ProductCategory::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object) {

        if($page != 'products_index') {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'products_index') {
            return false;
        }

        return [
            'robots' => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}