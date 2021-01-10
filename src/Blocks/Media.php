<?php

namespace Tome\Blocks;

use WordpressLib\Editor\Block\Block;

class Media extends Block
{

    protected function createSettings()
    {
        $settings = parent::createSettings();
        $settings['attributes'] = [
            'gallery_id' => ['type' => 'string'],
        ];
        return $settings;
    }
}
