<?php
namespace App\Formats;

use FFMpeg\Format\Audio\DefaultAudio;

class Opus extends DefaultAudio
{
    public function __construct($audioCodec = 'libopus')
    {
        $this->setAudioCodec($audioCodec);
    }

    public function getAvailableAudioCodecs()
    {
        return ['libopus'];
    }

    public function getExtraParams()
    {
        return [
            '-vn',
            '-ar', '48000',
            '-vbr', 'on',
            '-application', 'audio',
        ];
    }
}