<?php

namespace CMusic;

use CMusic\Options;

class LastFm extends Plugin
{
    public function __construct() {
        $options = new Options([
            new Option('', 'string', 'LASTFM_API_KEY', 'API key'),
        ]);

        $authorInfo = (object)[
            'author' => 'd9mz',
            'plugin_name' => 'LastFM integration',
            'plugin_description' => 'For LastFM integration on CMusic',
            'plugin_version' => '1.0',

            'minimum_api_version' => 1,
        ];

        parent::__construct($options, $authorInfo);
    }
}