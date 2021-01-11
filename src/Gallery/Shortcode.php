<?php

namespace Tome\Gallery;

use WordpressLib\Shortcode\Shortcode as BaseShortcode;

class Shortcode extends BaseShortcode
{

    protected function output($atts, $content, $tag)
    {
        $p = get_post($atts['id']);
        $has_cover = has_post_thumbnail($p->ID);
        if (!$p || $p->post_type != 'tome-gallery') {
            if (WP_DEBUG) {
                throw new() \InvalidArgumentException("Gallery ID $atts[id] is not valid.");
            }
        } else {
            printf(
                '<div class="tome-gallery %s"><h3>%s</h3>%s %s</div>',
                $has_cover ? 'has-cover' : 'no-cover',
                $p->post_title,
                $has_cover ? '<figure><span class="overlay"></span>' . get_the_post_thumbnail($p->ID) . '</figure>' : '',
                str_replace('<figure>', '<figure><span class="overlay"></span>', apply_filters('the_content', $p->post_content))
            );
        }
    }
}
