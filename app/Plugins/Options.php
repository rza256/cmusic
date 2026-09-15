<?php

// For any future readers of this code,
// please let me know if it's bad or weird and
// feel free to make pull requests!!!

namespace CMusic;

use CMusic\Option;

class Options
{
    /** @var Option[] */
    private array $optionList = [];

    public function __construct(array $options) {
        foreach($options as $option)
        {
            $this->add($option);
        }
    }

    public function get() : array {
        return $this->optionList;
    }

    public function getByKey(string $key) : ?Option {
        foreach ($this->optionList as $option) {
            if ($option->getKey() == $key)
            {
                return $option;
            }
        }

        return null;
    }

    public function add(Option $option) : static {
        $this->optionList[] = $option;
        return $this;
    }
}