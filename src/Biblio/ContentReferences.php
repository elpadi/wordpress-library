<?php
namespace Tome\Biblio;

use WordpressLib\Posts\ContentObjectElements;

class ContentReferences extends ContentObjectElements {

	protected $contentTagNames = ['span'];
	protected $contentNeedles = ['class="in-text-citation"'];

	protected function createItemFromElement($el) {
		return Reference\Reference::createFromDomElement($el);
	}

	public function sortComparator($a, $b) {
		return strcmp($a->getFullCitation(), $b->getFullCitation());
	}

}
