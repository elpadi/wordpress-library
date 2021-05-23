<?php

namespace WordpressLib\Posts;

abstract class FakeArchive extends FakePage
{

    protected $templateName = 'archive';

    public function __construct($slug, $title)
    {
        parent::__construct($slug, $title);

        remove_filter('the_title', [$this, 'replaceContentTitle'], 10, 2);
        remove_filter('the_content', [$this, 'replaceContent']);

        add_filter('get_the_archive_title', function ($t) {
            return $this->title;
        }, 100);
    }

    public function addRewriteRules()
    {
        parent::addRewriteRules();
        add_rewrite_rule(
            sprintf('%s/page/([0-9]+)/?$', $this->slug),
            "index.php?$this->queryVar=$this->slug&paged=\$matches[1]",
            'top'
        );
    }

    protected function createContent()
    {
        throw new() \BadMethodCallException("This method should not be called on this class.");
    }

    protected function getBodyClasses()
    {
        return array_merge(parent::getBodyClasses(), ['archive']);
    }
}
