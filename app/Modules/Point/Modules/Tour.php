<?php
namespace SkdSeo\Modules\Point\Modules;

/**
 * Chấm điểm / metadata seo cho Tour (plugin travel).
 *
 * Metadata nằm ở bảng travel_tour_metadata thông qua trait ModelMeta của model.
 */
class Tour
{
    public function getFocusKeyword($id)
    {
        return \Travel\Models\Tour::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id)
    {
        return \Travel\Models\Tour::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id)
    {
        return \Travel\Models\Tour::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id)
    {
        return \Travel\Models\Tour::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data)
    {
        return \Travel\Models\Tour::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data)
    {
        return \Travel\Models\Tour::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data)
    {
        return \Travel\Models\Tour::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data)
    {
        return \Travel\Models\Tour::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object)
    {
        if($page != 'tour_detail')
        {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'tour_detail')
        {
            return false;
        }

        return [
            'robots'    => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}
