<?php

namespace WordpressLib\ACF;

class Media
{

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getUrl()
    {
        return $this->data['url'];
    }

    public function getId()
    {
        return $this->data['ID'];
    }

    public function getTitle()
    {
        return $this->data['title'];
    }

    public function getCaption()
    {
        return $this->data['caption'];
    }

    public function getDescription()
    {
        return $this->data['description'];
    }
}
