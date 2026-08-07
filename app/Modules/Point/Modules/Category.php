<?php
namespace SkdSeo\Modules\Point\Modules;

use SkillDo\Cms\Models\PostCategory;

class Category
{
    public function getFocusKeyword($id) {
        return PostCategory::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id) {
        return PostCategory::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id) {
        return PostCategory::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id) {
        return PostCategory::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data) {
        return PostCategory::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data) {
        return PostCategory::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data) {
        return PostCategory::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data) {
        return PostCategory::updateMeta($id, 'seo_schema', $data);
    }

    /**
     * Trang lưu trữ theo thẻ (tag) cũng báo mình là `post_index` — nó dùng chung
     * template với trang danh mục. Không kiểm tra kiểu đối tượng thì module này
     * sẽ đọc `categories_metadata` bằng id của thẻ và trả về thiết lập seo của
     * một danh mục hoàn toàn khác.
     */
    static protected function isCategory($object): bool
    {
        return $object instanceof PostCategory;
    }

    public function schemaRender($page, $object) {

        if($page != 'post_index' || !self::isCategory($object)) {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'post_index' || !self::isCategory($object)) {
            return false;
        }

        return [
            'robots' => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}