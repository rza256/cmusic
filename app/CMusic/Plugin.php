<?php

// For any future readers of this code,
// please let me know if it's bad or weird and
// feel free to make pull requests!!!

namespace CMusic;
use App\Models\File;

class Plugin
{
    public const API_VERSION = 1;
    private Fields $fields;

    // Author information
    private object $authorInfo;

    public function __construct(Fields $fields, object $authorInfo)
    {
        if ($authorInfo->minimum_api_version < Plugin::API_VERSION) {
            report(new \RuntimeException('Plugin ' . $authorInfo->plugin_name . ' requires an API version that is not supported anymore. If you think this is a mistake please enable the PLUGIN_API_VER_BYPASS env var.'));
            die();
        }

        $this->fields = $fields;
        $this->authorInfo = $authorInfo;

        foreach ($this->fields->get() as $field) {
            $field->dbInit();
        }
    }

    public function getAuthorInfo() : object {
        return $this->authorInfo;
    }

    public function getOptions() : Fields {
        return $this->fields;
    }

    public function option(string $key): ?string {
        return $this->fields->getByKey($key)?->getValue();
    }

    // hooks
    public function onSongPlay(File $file) {}
    public function onFileAdded(File $file) {}
}