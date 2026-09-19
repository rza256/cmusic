<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Kiwilan\Audio\Audio;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use App\Models\File;
use CMusic\Plugin;

class PluginController extends Controller {
    /** @var object[]|null */
    private ?array $plugins = null;

    public function home(Request $request)
    {
        $plugins = $this->loadPlugins();

        return response(view('plugins', [
            'plugins' => array_map(fn ($p) => $p->getAuthorInfo(), $plugins),
        ]));
    }


    public function playHook(Request $request, int $id)
    {
        $file = File::findOrFail($id);

        // taking in lit. string in GET so interpret as t/f
        $resume = $request->input('resume', 'true') == 'true' ? true : false;
        $this->dispatch('onSongPlay', $file, $resume);

        return response()->json(['message' => 'OK']);
    }

    private function findPluginByQualifying(string $qualifying): ?Plugin
    {
        $plugins = $this->loadPlugins();

        foreach ($plugins as $p) {
            if ($p->getAuthorInfo()->plugin_qualifying === $qualifying) {
                return $p;
            }
        }

        return null;
    }

    public function plugin(Request $request, string $qualifying)
    {
        $plugin = $this->findPluginByQualifying($qualifying);

        if ($plugin === null) {
            abort(404, "Plugin '{$qualifying}' not found.");
        }

        return view('plugin', [
            'plugin' => $plugin
        ]);
    }

    public function updateSettings(Request $request, string $qualifying) {
        $plugin = $this->findPluginByQualifying($qualifying);

        if ($plugin === null) {
            abort(404, "Plugin '{$qualifying}' not found.");
        }

        foreach ($plugin->getFields()->get() as $field) {
            if ($field->isText()) {
                continue; // display-only fields, nothing to save
            }

            if (!$field->isModifiable()) {
                continue; // skip read-only fields even if present in the request
            }

            if ($request->has($field->getKey())) {
                $field->setValue($request->input($field->getKey()));
            }
        }

        $plugin->onPluginUpdated();

        return redirect()->back()->with('status', 'Settings saved.');
    }

    private function loadPlugins(): array
    {
        if ($this->plugins !== null) {
            return $this->plugins;
        }

        $pluginClasses = config('plugins.mods');

        return $this->plugins = array_map(
            fn (string $class) => new $class(),
            $pluginClasses
        );
    }

    private function dispatch(string $method, mixed ...$args): void
    {
        foreach ($this->loadPlugins() as $plugin) {
            try {
                $plugin->{$method}(...$args);
            } catch (\Throwable $e) {
                Log::error("Plugin hook {$method} failed on " . get_class($plugin), [
                    'exception' => $e,
                ]);
            }
        }
    }
}
