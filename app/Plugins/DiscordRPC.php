<?php

namespace CMusic\Plugins;

use CMusic\Fields;
use CMusic\Field;
use CMusic\Plugin;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\File;

class DiscordRPC extends Plugin
{
    // generally this plugin is just for the dotnet to
    // request the current playing song route

    public function __construct() {
        /*
                'lastfm_session' => $session->session->key,
                'lastfm_username' => $session->session->name,
                'lastfm_token' => $token,
        */

        $fields = new Fields([
            new Field('', 'string', 'RPC_TITLE', 'JSON', '', false, true, false),
            new Field('', 'string', 'RPC_ALBUM', 'JSON', '', false, true, false),
            new Field('', 'string', 'RPC_ARTIST', 'JSON', '', false, true, false),
            new Field('{}', 'string', 'RPC_FULL', 'JSON', '', false, true, false),
        ]);

        $authorInfo = (object)[
            'author' => 'd9mz',
            'plugin_qualifying' => 'discord_rpc', // url
            'plugin_name' => 'Discord RPC',
            'plugin_description' => 'For showing your music on Discord',
            'plugin_version' => '1.0',

            'minimum_api_version' => 1,
        ];

        parent::__construct($fields, $authorInfo);
    }

    public static function tryHttpGet(string $url) : ?object
    {
        $max_attempts = 1;
        $attempt = 0;
        $success = false;
        $response = null;
        
        while ($attempt < $max_attempts && !$success) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'cmusic/1.0');
            
            $response = curl_exec($ch);
            
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // echo $httpCode;

            if ($httpCode == 200) { // HTTP OK
                $success = true;
            } else {
                $attempt++;
                sleep(1); 
            }
            
            curl_close($ch);
        }        

        if (!$success) {
            report(new \RuntimeException("tryHttpGet failed for {$url}: " . ($lastError ?? 'unknown error')));
            return null;
        }

        return json_decode($response);
    }

    public static function tryHttpPost(string $baseUrl, array $params): ?object
    {
        $max_attempts = 1;
        $attempt = 0;
        $success = false;
        $response = null;
        $lastError = null;

        while ($attempt < $max_attempts && !$success) {
            $ch = curl_init($baseUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'cmusic/1.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

            $response = curl_exec($ch);

            if ($response === false) {
                $lastError = curl_error($ch);
                curl_close($ch);
                $attempt++;
                sleep(1);
                continue;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $success = true;
            } else {
                $lastError = "HTTP {$httpCode}: {$response}";
                $attempt++;
                sleep(1);
            }
        }

        if (!$success) {
            report(new \RuntimeException("tryHttpPost failed for {$baseUrl}: " . ($lastError ?? 'unknown error')));
            return null;
        }

        return json_decode($response);
    }

    #[\Override]
    public static function registerRoutes(): void
    {
        Route::get('/plugins/discord_rpc/get', function (Request $request) {
            $plugin = new static();
            return response()->json(json_decode($plugin->getFields()->getByKey('RPC_FULL')->getValue()));
        })->name('plugins.rpc.get');
    }

    #[\Override]
    public function onSongPlay(File $file, bool $isResuming) {
        report('onSongPlay rpc on ' . $file->id);

        if ($file->album && $file->title && $file->artist)
        {  
            $this->getFields()->getByKey('RPC_ALBUM')->setValue($file->album);
            $this->getFields()->getByKey('RPC_TITLE')->setValue($file->title);
            $this->getFields()->getByKey('RPC_ARTIST')->setValue($file->artist);

            $this->getFields()->getByKey('RPC_FULL')->setValue(json_encode($file));
        }

        return;
    }

    #[\Override]
    public function onPluginUpdated() {
        report('onPluginUpdated called');

        // is LASTFM_SECRET_KEY LASTFM_API_KEY set?
        $secret = $this->getFields()->getByKey('LASTFM_SECRET_KEY')->getValue();
        $apiKey = $this->getFields()->getByKey('LASTFM_API_KEY')->getValue();

        if (!empty($secret) && !empty($apiKey)) {
            report('secret && apiKey');
            report($secret);
            report($apiKey);

            $authUrl = 'http://www.last.fm/api/auth/?' . http_build_query(['api_key' => $apiKey]);

            $this->getFields()->getByKey('LASTFM_HOWTO_3')->setValue(
                sprintf('When you are done putting in the secret and API key, click <a href="%s">here</a> to connect your LastFM account.', $authUrl)
            );
        }
    }
}