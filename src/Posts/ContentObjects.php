<?php

namespace WordpressLib\Posts;

class ContentObjects extends \ArrayObject
{

    protected $contentTagNames = ['span'];
    protected $contentNeedles = [''];

    protected function extractHtmlTags($c, $tagName, $needle = '')
    {
        $html = [];
        $i = 0;

        if (empty($needle)) {
            $needle = $tagName;
        }

        // 1. find needle
        while (($j = strpos($c, $needle, $i)) !== false) {
            // 2. find opening tag
            $k = $i + strrpos(substr($c, $i, $j), "<$tagName");
            // 3. find closing tag
            $l = strpos($c, "</$tagName>", $j) + 7;
            if ($k !== false && $l !== false) {
                $html[] = substr($c, $k, $l - $k);
                // 4a. continue at closing tag
                $i = $l;
            } else {
                // 4b. continue after needle
                $i = $j + strlen($needle);
            }
        }

        return $html;
    }

    public function appendFromContent($c)
    {
        foreach ($this->contentTagNames as $i => $tagName) {
            foreach ($this->extractHtmlTags($c, $tagName, $this->contentNeedles[$i]) as $tag) {
                if ($tag) {
                    $this->append($tag);
                }
            }
        }
    }

    public function appendFromPosts($posts)
    {
        foreach ($posts as $p) {
            $this->appendFromContent($p->post_content);
        }
    }

    public function sort()
    {
        if (method_exists($this, 'sortComparator') == false) {
            throw new() \BadMethodCallException('A sort comparator must be defined inside the class.');
        }
        $this->uasort([$this, 'sortComparator']);
    }
}
