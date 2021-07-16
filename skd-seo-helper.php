<?php
Class SeoHelper {

    public $title;

    public $description;

    public $keyword;

    public $image;

    public $auth;

    public $favicon;

    public $meta = [];

    public $code = [];

    public $schema;

    function __construct() {
        $this->title        = Str::clear(Option::get('general_title', ''));
        $this->description  = Str::clear(Option::get('general_description', ''));
        $this->keyword      = Str::clear(Option::get('general_keyword', ''));
        $this->image        = option::get('logo_header');
        $this->auth         = Option::get('general_title');
        $this->schema       = new Schema();
    }

    function setTitle($title) {
        if(!empty($title)) $this->title = Str::clear($title);
        $this->title = apply_filters('seo_title', $this->title);
        $this->schema->setTitle($this->title);
        return $this;
    }

    function setDescription($description) {
        if(!empty($description)) $this->description = Str::clear($description);
        $this->description = apply_filters('seo_description', $this->description);
        $this->schema->setDescription($this->description);
        $this->addMeta('description', $this->description);
        return $this;
    }

    function setKeyword($keyword) {
        if(!empty($keyword)) $this->keyword = Str::clear($keyword);
        $this->keyword = apply_filters('seo_keyword', $this->keyword);
        $this->addMeta('keywords', $this->keyword);
        return $this;
    }

    function setImage($image) {
        if(!empty($image)) $this->image = Str::clear($image);
        $this->image = apply_filters('seo_image', $this->image);
        $this->schema->setImage($this->image);
        if(!empty($this->image)) $this->image = Template::imgLink($this->image);
        if(!Url::is($this->image)) $this->image = Url::base($this->image);
        $this->addMeta('image', $this->image);
        return $this;
    }

    function setAuth($auth) {
        if(!empty($auth)) $this->auth = $auth;
        $this->auth = apply_filters('seo_auth', $this->auth);
        return $this;
    }

    function setFavicon($favicon) {
        if(!empty($favicon)) $this->favicon = $favicon;
        return $this;
    }

    function addMeta($name, $content, $args = []) {
        if(!empty($content)) {
            $attr = '';
            if(have_posts($args)) {
                foreach ($args as $key => $txt) {
                    if(is_string($txt)) $attr .= $key.'="'.$txt.'" ';
                }
                $attr = trim($attr);
            }
            $this->meta[] = [
                'name'      => $name,
                'content'   => $content,
                'attr'      => $attr
            ];
        }
        return $this;
    }

    function addProperty($name, $content, $args = []) {
        $this->addMeta('', $content, [
            'property' => $name
        ]);
        return $this;
    }

    function addItemprop($name, $content, $args = []) {
        $this->addMeta('', $content, [
            'itemprop' => $name
        ]);
        return $this;
    }

    function addCode($name, $content) {
        $this->code[$name] = $content;
        return $this;
    }

    function render() {
        echo '<title>'.$this->title.'</title>';
        foreach ($this->meta as $meta) {
            echo '<meta '.((!empty($meta['name'])) ? 'name="'.$meta['name'].'" ' :' ').$meta['attr'].' content="'.$meta['content'].'"/>';
        }
        foreach ($this->code as $code) {
            if(is_string($code)) echo $code;
        }
        $this->schema->render();
    }
}