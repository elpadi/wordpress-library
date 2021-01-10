<?php

namespace WordpressLib\Posts;

abstract class ContentObjectElements extends ContentObjects
{

    abstract protected function createItemFromElement($el);
    abstract protected function isObjectElement($el);

    protected function recursiveNodeWalk($node)
    {
        foreach ($node->childNodes as $child) {
            if ($this->isObjectElement($child)) {
                $this->append($this->createItemFromElement($child));
            }
            if ($child->childNodes) {
                $this->recursiveNodeWalk($child);
            }
        }
    }

    public function transformTags()
    {
        if ($this->count() && ($html = implode('', $this->getArrayCopy()))) {
            $doc = new() \DOMDocument();
            $doc->loadXML('<p>' . $html . '</p>');
            $this->exchangeArray([]);
            $this->recursiveNodeWalk($doc);
            $this->exchangeArray(array_unique($this->getArrayCopy()));
        }
    }
}
