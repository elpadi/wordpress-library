<?php

namespace Tome\Biblio\Reference\Block;

use WordpressLib\Editor\Block\Block;

class Data extends Block
{

    protected function createSettings()
    {
        $settings = parent::createSettings();
        $settings['attributes'] = [
            'type' => ['type' => 'string'],
            'title' => ['type' => 'string'],
        ];
        return $settings;
    }
}
