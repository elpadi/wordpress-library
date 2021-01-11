<?php

namespace WordpressLib\Admin;

use Symfony\Component\HttpFoundation\Session\Session;

class Notices
{

    public function __construct(Session $symfonySession)
    {
        $this->flash = $symfonySession->getFlashBag();
        add_action('admin_notices', [$this, 'display']);
    }

    public function display()
    {
        foreach ($this->flash->all() as $type => $msgs) {
            foreach ($msgs as $msg) {
                printf('<div class="notice notice-%s"><p>%s</p></div>', $type, $msg);
            }
        }
    }

    public static function wpError($wp_error)
    {
        foreach ($wp_error->get_error_messages() as $msg) {
            $this->flash->add('error', $msg);
        }
    }
}
