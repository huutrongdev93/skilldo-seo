<?php
namespace SkdSeo\Modules\Point\Modules;

/**
 * Đọc / ghi thiết lập seo thủ công của Danh mục sản phẩm (plugin sicommerce).
 *
 * Metadata nằm ở bảng products_categories_metadata thông qua trait ModelMeta.
 *
 * Lớp này chỉ được khai báo trong SeoPoint::module() khi sicommerce đã cài —
 * xem điều kiện class_exists ở đó.
 */
class ProductCategory
{
    static protected function isCategory($object): bool
    {
        return $object instanceof \Ecommerce\Models\ProductCategory;
    }

    public function getFocusKeyword($id)
    {
        return \Ecommerce\Models\ProductCategory::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id)
    {
        return \Ecommerce\Models\ProductCategory::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id)
    {
        return \Ecommerce\Models\ProductCategory::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id)
    {
        return \Ecommerce\Models\ProductCategory::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data)
    {
        return \Ecommerce\Models\ProductCategory::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data)
    {
        return \Ecommerce\Models\ProductCategory::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data)
    {
        return \Ecommerce\Models\ProductCategory::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data)
    {
        return \Ecommerce\Models\ProductCategory::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object)
    {
        if($page != 'products_index' || !self::isCategory($object))
        {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'products_index' || !self::isCategory($object))
        {
            return false;
        }

        return [
            'robots'    => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}
