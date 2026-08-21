<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Kiwilan\Audio\Audio;
use Illuminate\Support\Facades\Storage;

class DebugController extends Controller {
    public function testAudio(Request $request)
    {
        $audio = Audio::read(Storage::disk('music')->path("complete/johncheetham1961@gmail.com/Steely Dan - AJA MFSL (Steve Hoffman Edit)/01 - Black Cow.flac"));
        $metadata = $audio->getMetadata();
        $metadata = $metadata->toArray(); // audio information
        $raw_all = $audio->getRaw(); // metadata 

        $full = array_merge($metadata, $raw_all);

        dd($full);
    }
}
