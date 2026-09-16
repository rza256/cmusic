<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Kiwilan\Audio\Audio;
use Illuminate\Support\Facades\Storage;

use App\Models\File;

class PluginController extends Controller {
    public function home(Request $request)
    {
        $pluginClasses = config('plugins.mods');
        $plugins = [];
        $authorInfo = [];

        foreach ($pluginClasses as $pluginClass)
        {
            $plugin = new $pluginClass();
            $plugins[] = $plugin;
            $authorInfo[] = $plugin->getAuthorInfo();

            $plugin->onSongPlay(File::orderBy('id','desc')->first());
        }

        return response()->json($authorInfo);
    }

    private function loadPlugins() {
        // todo
    }

    public function playHook(Request $request, int $id)
    {
        $pluginClasses = config('plugins.mods');
        $plugins = [];
        $authorInfo = [];

        foreach ($pluginClasses as $pluginClass)
        {
            $plugin = new $pluginClass();
            $plugins[] = $plugin;

            $plugin->onSongPlay(File::where('id', $id)->first());
        }

        return response()->json(['message' => 'OK']);
    }
}
