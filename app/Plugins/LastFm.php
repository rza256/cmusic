<?php

namespace CMusic\Plugins;

use CMusic\Fields;
use CMusic\Field;
use CMusic\Plugin;

use App\Models\File;

class LastFm extends Plugin
{
    public function __construct() {
        $fields = new Fields([
            new Field('', 'string', 'LASTFM_API_KEY', 'API key'),
        ]);

        $authorInfo = (object)[
            'author' => 'd9mz',
            'plugin_name' => 'LastFM integration',
            'plugin_description' => 'For LastFM integration on CMusic',
            'plugin_version' => '1.0',

            'minimum_api_version' => 1,
        ];

        parent::__construct($fields, $authorInfo);
    }

    #[\Override]
    public function onSongPlay(File $file) {
        report('onSongPlay played on ' . $file->id);
    }
}