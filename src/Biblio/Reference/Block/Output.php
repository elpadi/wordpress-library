<?php

namespace Tome\Biblio\Reference\Block;

use WordpressLib\Editor\Block\Block;

class Output extends Block
{

    protected function createSettings()
    {
        $settings = parent::createSettings();
        $settings['attributes'] = [
            'chicago' => ['type' => 'string'],
            'mla' => ['type' => 'string'],
        ];
        return $settings;
    }
}
