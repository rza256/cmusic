<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Jobs\ProcessAudio;
use App\Enums\JobType;

class AudioController extends Controller {
    public function home(Request $request) {
        $songs = File::orderBy('id', 'desc')->paginate(100);

        return view('songs', [
            'songs' => $songs
        ]);
    }

    public function file(Request $request, int $id)
    {
        $file = File::where('id', $id)->firstOrFail();
        return response()->file(Storage::disk('music')->path($file->file_path));
    }

    public function albumCover(Request $request, int $id)
    {
        $fileM = File::where('id', $id)->firstOrFail();
        $files = Storage::disk('music')->allFiles(dirname($fileM->file_path));

        // method 1: look for
        // cover.png in same dir

        // todo:
        // just reencode these and put them in some covers
        // folder after you find them

        $lookFor = [
            'cover',
            'album_cover',
            'albumcover',
            'album-art',
            'album_art',
            'albumart',
            'artwork',
            'album_artwork',
            'albumartwork',
            'front',
            'front_cover',
            'frontcover',
            'cover_art',
            'coverart',
            'folder',
            'default_cover',
            'default_cover_art',
            'thumbnail',
            'thumb',
            'image',
        ];

        foreach($files as $file) {
            foreach ($lookFor as $lf)
            {
                if (str_contains($file, $lf))
                {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    // echo $ext;
                    
                    return response()->file(Storage::disk('music')->path(dirname($fileM->file_path) . "/" . $lf . "." . $ext));
                }    
            }
            // echo $file . "<br>";
        }
    }
}
