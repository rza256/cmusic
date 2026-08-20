<?php

namespace App\Enums;

enum JobType : int
{
    case GRAB_METADATA = 0;
    case TRANSCODE_AUDIO = 1;
}
