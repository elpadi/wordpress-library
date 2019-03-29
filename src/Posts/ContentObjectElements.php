<?php
namespace WordpressLib\Posts;

abstract class ContentObjectElements extends ContentObjects {

	abstract protected function createItemFromElement($el);

	protected function getHtmlElements($doc) {
		return $doc->childNodes;
	}

	public function transformTags() {
		if ($this->count() && ($html = implode('', $this->getArrayCopy()))) {
			$doc = new \DOMDocument();
			$doc->loadXML($html);
			foreach ($this->getHtmlElements($doc) as $el) {
				$items[] = $this->createItemFromElement($el);
			}
		}
		$this->exchangeArray(isset($items) ? $items : []);
	}

}
