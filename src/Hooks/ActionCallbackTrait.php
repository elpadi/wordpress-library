<?php

namespace WordpressLib\Hooks;

use function Stringy\create as s;

trait ActionCallbackTrait
{
    /**
     * Helper method to add CRUD callbacks.
     *
     * E.g. POST to wp_ajax_{get|delete|update}_post.
     *
     * `$this->addActionCallbacks('wp_ajax_#_post', 'get', 'delete', 'update')` will
     * create actions for self::getPost, self::deletePost, and self::updatePost.
     */
    protected function addActionCallbacks(string $tokenizedActionName, ...$verbs): void
    {
        $parts = explode('#', $tokenizedActionName);
        foreach ($verbs as $verb) {
            add_action(
                str_replace('#', $verb, $tokenizedActionName),
                [$this, (string) s($verb . '_' . ($parts[1] ?? ''))->camelize()]
            );
        }
    }
}
