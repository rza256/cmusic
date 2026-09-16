<?php

// For any future readers of this code,
// please let me know if it's bad or weird and
// feel free to make pull requests!!!

namespace CMusic;
use CMusic\Option;

class Fields
{
    /** @var Field[] */
    private array $fieldList = [];

    public function __construct(array $fields) {
        foreach($fields as $field)
        {
            $this->add($field);
        }
    }

    public function get() : array {
        return $this->fieldList;
    }

    public function getByKey(string $key) : ?Field {
        foreach ($this->fieldList as $field) {
            if ($field->getKey() == $key)
            {
                return $field;
            }
        }

        return null;
    }

    public function add(Field $field) : static {
        $this->fieldList[] = $field;
        return $this;
    }
}