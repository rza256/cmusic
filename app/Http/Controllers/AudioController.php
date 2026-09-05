<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\File;
use App\Models\Transcode;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use App\Jobs\ProcessAudio;
use App\Enums\JobType;

class AudioController extends Controller {
    public function home(Request $request) {
        $songs = File::orderBy('album', 'desc')
            ->orderBy('metadata->track_number', 'asc');

        $songs = $songs->paginate(100);

        return view('songs', ['songs' => $songs]);
    }

    public function dynamic(Request $request, string $type)
    {
        $valid = ['songs'];

        if (!in_array($type, $valid)) {
            return abort(403);
        }

        $view = 'dynamic.' . $type;
        if (!view()->exists($view)) {
            return response("You should not be seeing this!", 500);
        }

        $data = [];

        if ($type == 'songs') {
            $songs = File::orderBy('album', 'desc')
                ->orderBy('metadata->track_number', 'asc');

            // does the user want only a specific album / artist
            !empty($request->input('album')) ? $songs->where('album', $request->input('album')) : '';
            !empty($request->input('artist')) ? $songs->where('artist', $request->input('artist')) : '';

            if (!empty($request->input('q')) && !empty($request->input('t')))
            {
                switch ($request->input('t'))
                {
                    case "title":
                        $songs->where('title', 'LIKE', '%' . $request->input('q') . '%');
                        break;
                    case "author":
                        $songs->where('artist', 'LIKE', '%' . $request->input('q') . '%');
                        break;
                    case "album":
                        $songs->where('album', 'LIKE', '%' . $request->input('q') . '%');
                        break;
                    case "filename":
                        $songs->where('file_path', 'LIKE', '%' . $request->input('q') . '%');
                        break;
                    default:
                        $songs->where('metadata', 'LIKE', '%' . $request->input('q') . '%');

                        // should just be all
                        // $songs->where('artist', $request->input('artist'));
                        // APPARENTLY you can't do LIKE across different columns?
                        // so we settle with metadata
                        break;
                };
            }

            $songs = $songs->paginate(100);
            $songs = $songs->filter(function (File $file) {
                return $file->transcode()->exists();
            });

            $data = [
                'songs' => $songs,
            ];
        }

        return view($view, $data);
    }

    public function transcodes(Request $request)
    {
        $tc = Transcode::orderBy('id', 'desc')->paginate(20);

        return view('transcodes', [
            'transcodes' => $tc
        ]);
    }

    public function file(Request $request, int $id)
    {
        $file = File::where('id', $id)->firstOrFail();
        if ($file->transcode)
        {
            return response()->file(Storage::disk('transcodes')->path(pathinfo($file->file_path, PATHINFO_FILENAME) . ".ogg"));
        }

        return response()->file(Storage::disk('music')->path($file->file_path));
    }

    public function json(Request $request, int $id)
    {
        $file = File::where('id', $id)->firstOrFail();
        return response()->json($file);
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
                    
                    if(Storage::disk('music')->exists(dirname($fileM->file_path) . "/" . $lf . "." . $ext))
                    {
                        return response()->file(Storage::disk('music')->path(dirname($fileM->file_path) . "/" . $lf . "." . $ext));
                    }
                }    
            }
            // echo $file . "<br>";
        }

        return response()->file(Storage::disk('public')->path('logo.png'));
    }
}
