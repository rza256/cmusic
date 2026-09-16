<?php

// For any future readers of this code,
// please let me know if it's bad or weird and
// feel free to make pull requests!!!

namespace CMusic;

class Field
{
    public function __construct(
        private string $value, // backend, Field model "value" col default
        private string $type, // backend, Field model "type" col "string", "integer" 
        private string $key, // backend, Field model "key" col
        private string $title, // frontend, title (e.g "title")
        private string $placeholder = '', // frontend, placeholder (e.g. "placeholder")
        private bool $modifiable = true, // frontend, decides if input is disabled
        private bool $render = true, // frontend, decides if input should be displayed at all
    ) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Field key cannot be empty');
        }
    }

    public function dbInit() {
        // init if needed
        $field = \App\Models\Field::where('key', $this->key)->first();
        if (!$field) {
            $field = \App\Models\Field::create([
                'key' => $this->key,
                'value' => $this->value,
                'type' => $this->type,
                'title' => $this->title,
                'placeholder' => $this->placeholder,
                'modifiable' => $this->modifiable,
                'render' => $this->render
            ]);
        }
    }

    public function getKey() : string {
        return $this->key;
    }
    
    public function getValue() : string {
        return $this->value;
    }

    public function setValue(string $value) : void {
        $field = \App\Models\Field::where('key', $this->key)->first();
        if ($field) {
            $field->update([
                'key' => $this->key,
                'value' => $this->value, 
            ]);
        }

        $this->value = $field->value;
    }  
}