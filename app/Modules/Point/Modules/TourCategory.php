<?php
namespace SkdSeo\Modules\Point\Modules;

use Travel\Models\Category;

/**
 * Chấm điểm / metadata seo cho danh mục tour (plugin travel).
 *
 * Dùng chung cho cả 4 trục phân loại của travel_category: danh mục, điểm đến,
 * chủ đề và mùa — tất cả đều render bằng trang tour_index.
 */
class TourCategory
{
    public function getFocusKeyword($id)
    {
        return Category::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id)
    {
        return Category::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id)
    {
        return Category::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id)
    {
        return Category::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data)
    {
        return Category::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data)
    {
        return Category::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data)
    {
        return Category::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data)
    {
        return Category::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object)
    {
        if($page != 'tour_index')
        {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'tour_index')
        {
            return false;
        }

        return [
            'robots'    => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}
