<?php

// For any future readers of this code,
// please let me know if it's bad or weird and
// feel free to make pull requests!!!

namespace CMusic;
use CMusic\Option;
use CMusic\Options;

class Plugin
{
    public const API_VERSION = 1;
    private Options $options;

    // Author information
    private object $authorInfo;

    public function __construct(Options $options, object $authorInfo)
    {
        if ($authorInfo->minimum_api_version < Plugin::API_VERSION) {
            report(new \RuntimeException('Plugin ' . $authorInfo->plugin_name . ' requires an API version that is not supported anymore. If you think this is a mistake please enable the PLUGIN_API_VER_BYPASS env var.'));
            die();
        }

        $this->options = $options;
        $this->authorInfo = $authorInfo;

        foreach ($this->options->get() as $option) {
            $option->dbInit();
        }
    }

    public function getAuthorInfo() : object {
        return $this->authorInfo;
    }

    public function getOptions() : Options {
        return $this->options;
    }

    public function option(string $key): ?string {
        return $this->options->getByKey($key)?->getValue();
    }
}