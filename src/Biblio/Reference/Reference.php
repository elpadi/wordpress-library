<?php

namespace Tome\Biblio\Reference;

class Reference
{

    protected static $properties = ['id','type','format','content'];

    protected $id = 0;
    protected $type = 'icon';
    protected $format = 'mla';
    protected $content = '';
    protected $fullCitation;

    public static function createFromDomElement($e)
    {
        $instance = new static();
        $instance->hydrateFromDomElement($e);
        return $instance;
    }

    public function __construct()
    {
    }

    public function hydrateFromDomElement($e)
    {
        foreach (static::$properties as $prop) {
            $this->$prop = $e->getAttribute("data-$prop");
        }
    }

    protected function fetchPost()
    {
        $this->post = get_post(intval($this->id));
    }

    public function getID()
    {
        return isset($this->id) ? $this->id : 0;
    }

    public function getFullCitation()
    {
        if (isset($this->fullCitation)) {
            return $this->fullCitation;
        }
        if (!isset($this->post)) {
            $this->fetchPost();
        }
        foreach (parse_blocks($this->post->post_content) as $b) {
            if ($b['blockName'] == 'tome2/reference-output') {
                return isset($b['attrs'][$this->format]) ? $b['attrs'][$this->format] : '';
            }
        }
        return '';
    }
}
