<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Kiwilan\Audio\Audio;
use Illuminate\Support\Facades\Storage;

class DebugController extends Controller {
    public function testAudio(Request $request)
    {
        $audio = Audio::read(Storage::disk('music')->path("Linux/1992 - Suffocating the Bloom/01. 21.mp3"));
        $metadata = $audio->getMetadata();
        $metadata = $metadata->toArray(); // audio information
        $raw_all = $audio->getRaw(); // metadata 

        $full = array_merge($metadata, $raw_all);

        echo json_encode($full);

        $audio = Audio::read(Storage::disk('music')->path("Linux/Gens/Renard - BEST OF LAPFOX VOL 1 Because Maybe! (2010)/18. Renard - BEST OF LAPFOX VOL 1 Because Maybe! - Let's Move.opus"));
        $metadata = $audio->getMetadata();
        $metadata = $metadata->toArray(); // audio information
        $raw_all = $audio->getRaw(); // metadata 

        $full = array_merge($metadata, $raw_all);

        dd($full);
    }
}
