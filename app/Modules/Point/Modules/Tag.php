<?php
namespace SkdSeo\Modules\Point\Modules;

/**
 * Đọc / ghi thiết lập seo thủ công của Thẻ (robots, canonical, schema).
 *
 * Metadata lưu qua Model::updateMeta — bảng `tags` chưa có bảng metadata riêng
 * nên Metadata tự đổ vào bảng chung `metabox`, không cần migration.
 */
class Tag
{
    /**
     * Trang thẻ dùng chung template với trang danh mục bài viết nên $page cũng
     * là `post_index`. Phải kiểm tra kiểu của đối tượng, nếu không module thẻ và
     * module danh mục sẽ tranh nhau và đọc nhầm metadata của bảng còn lại.
     */
    static protected function isTag($object): bool
    {
        return $object instanceof \SkillDo\Cms\Models\Tag;
    }

    public function getFocusKeyword($id)
    {
        return \SkillDo\Cms\Models\Tag::getMeta($id, 'seo_focus_keyword', true);
    }

    public function getRobots($id)
    {
        return \SkillDo\Cms\Models\Tag::getMeta($id, 'seo_robots', true);
    }

    public function getCanonical($id)
    {
        return \SkillDo\Cms\Models\Tag::getMeta($id, 'seo_canonical', true);
    }

    public function getSchema($id)
    {
        return \SkillDo\Cms\Models\Tag::getMeta($id, 'seo_schema', true);
    }

    public function setFocusKeyword($id, $data)
    {
        return \SkillDo\Cms\Models\Tag::updateMeta($id, 'seo_focus_keyword', $data);
    }

    public function setRobots($id, $data)
    {
        return \SkillDo\Cms\Models\Tag::updateMeta($id, 'seo_robots', $data);
    }

    public function setCanonical($id, $data)
    {
        return \SkillDo\Cms\Models\Tag::updateMeta($id, 'seo_canonical', $data);
    }

    public function setSchema($id, $data)
    {
        return \SkillDo\Cms\Models\Tag::updateMeta($id, 'seo_schema', $data);
    }

    public function schemaRender($page, $object)
    {
        if($page != 'post_index' || !self::isTag($object))
        {
            return false;
        }

        return $this->getSchema($object->id);
    }

    public function seoRender($page, $object): false|array
    {
        if($page != 'post_index' || !self::isTag($object))
        {
            return false;
        }

        return [
            'robots'    => $this->getRobots($object->id),
            'canonical' => $this->getCanonical($object->id)
        ];
    }
}
