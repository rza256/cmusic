<?php

// For any future readers of this code,
// please let me know if it's bad or weird and
// feel free to make pull requests!!!

namespace CMusic;

class Field
{
    private ?\App\Models\Field $record = null;
    private bool $recordLoaded = false;

    public function __construct(
        private string $value, // backend, Field model "value" col default
        private string $type, // backend, Field model "type" col "string", "integer" 
        private string $key, // backend, Field model "key" col
        private string $title, // frontend, title (e.g "title")
        private string $placeholder = '', // frontend, placeholder (e.g. "placeholder")
        private bool $modifiable = true, // frontend, decides if input is disabled
        private bool $render = true, // frontend, decides if input should be displayed at all
        private bool $isText = false, // frontend, decides if field should be displayed as input or text
    ) {
        if (empty($key)) {
            throw new \InvalidArgumentException('Field key cannot be empty');
        }
    }

    private function record(): ?\App\Models\Field
    {
        if (!$this->recordLoaded) {
            $this->record = \App\Models\Field::where('key', $this->key)->first();
            $this->recordLoaded = true;
        }

        return $this->record;
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
                'render' => $this->render,
                'is_text' => $this->isText,
            ]);
        }
    }

    public function isText() : bool { 
        return $this->record()?->is_text ?? $this->isText; 
    }

    public function shouldRender() : bool { 
        return $this->record()?->render ?? $this->render; 
    }

    public function isModifiable() : bool { 
        return $this->record()?->modifiable ?? $this->modifiable;  
    }   

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): string
    {
        return $this->record()?->value ?? $this->value;
    }

    public function getTitle(): string
    {
        return $this->record()?->title ?? $this->title;
    }

    public function getPlaceholder(): string
    {
        return $this->record()?->placeholder ?? $this->placeholder;
    }

    public function setValue(string $value): void
    {
        $this->record = \App\Models\Field::updateOrCreate(
            ['key' => $this->key],
            ['value' => $value]
        );
        $this->recordLoaded = true;

        $this->value = $value;
    }
}