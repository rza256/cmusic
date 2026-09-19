<?php

namespace CMusic\Plugins;

use CMusic\Fields;
use CMusic\Field;
use CMusic\Plugin;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\File;

class LastFm extends Plugin
{
    public function __construct() {
        /*
                'lastfm_session' => $session->session->key,
                'lastfm_username' => $session->session->name,
                'lastfm_token' => $token,
        */

        $fields = new Fields([
            new Field(
                sprintf('Please create an API account at <a href="%s">here</a> and make the callback URL point to <code>%s</code>.',
                        'https://www.last.fm/api/account/create', url('/plugins/lastfm_integration/callback')),
                'string', 'LASTFM_HOWTO_1', 'Text', '', true, true, true),

            new Field('Once you are finished, please input the key and shared secret below.', 'string', 'LASTFM_HOWTO_2', 'Text', '', true, true, true),
            new Field('', 'string', 'LASTFM_SECRET_KEY', 'Shared secret key', '', true, true, false),
            new Field('', 'string', 'LASTFM_API_KEY', 'API key', '', true, true, false),

            new Field(
                sprintf('When you are done putting in the secret and API key, click <a href="%s">here</a> to connect your LastFM account.',
                        url('http://www.last.fm/api/auth/', ['api_key' => ''])),
                'string', 'LASTFM_HOWTO_3', 'Text', '', true, true, true),

            new Field('', 'string', 'LASTFM_TOKEN', 'Token', '', false, true, false),
            new Field('', 'string', 'LASTFM_USERNAME', 'Username', '', false, true, false),
            new Field('', 'string', 'LASTFM_SESSION', 'Session key', '', false, true, false),
        ]);

        $authorInfo = (object)[
            'author' => 'd9mz',
            'plugin_qualifying' => 'lastfm_integration', // url
            'plugin_name' => 'LastFM integration',
            'plugin_description' => 'For LastFM integration on CMusic',
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
        Route::get('/plugins/lastfm_integration/callback', function (Request $request) {
            $plugin = new static();

            $token = $request->query('token');
            report('Last.fm callback hit with token: ' . $token);

            $secret = $plugin->getFields()->getByKey('LASTFM_SECRET_KEY')->getValue();
            $apiKey = $plugin->getFields()->getByKey('LASTFM_API_KEY')->getValue();

            $sig = self::create_api_sig([
                'api_key' => $apiKey,
                'method' => 'auth.getSession',
                'token' => $token,
            ], $secret);

            $url = self::construct_url([
                'method' => 'auth.getSession',
                'token' => $token,
                'api_key' => $apiKey,
                'api_sig' => $sig,
                'format' => 'json',
            ]);

            $session = self::tryHttpGet($url);

            if ($session === null || !isset($session->session)) {
                report(new \RuntimeException('Last.fm auth.getSession failed or returned unexpected payload.'));
                return redirect('/plugins/lastfm_integration')->with('error', 'Failed to connect to Last.fm.');
            }

            $plugin->getFields()->getByKey('LASTFM_TOKEN')->setValue($token);
            $plugin->getFields()->getByKey('LASTFM_USERNAME')->setValue($session->session->name);
            $plugin->getFields()->getByKey('LASTFM_SESSION')->setValue($session->session->key);
            return redirect('/plugins/lastfm_integration');
        })->name('plugins.lastfm.callback');
    }
    
    #[\Override]
    public function onSongPlay(File $file, bool $isResuming) {
        report('onSongPlay played on ' . $file->id);

        if ($isResuming) {
            report('isResuming true');
            return;
        }

        $secret = $this->getFields()->getByKey('LASTFM_SECRET_KEY')->getValue();
        $apiKey = $this->getFields()->getByKey('LASTFM_API_KEY')->getValue();
        $sk = $this->getFields()->getByKey('LASTFM_SESSION')->getValue();
        $token = $this->getFields()->getByKey('LASTFM_TOKEN')->getValue();

        if (empty($sk) || empty($token)) {
            report('sessionkey or token empty');
            return;
        }

        if (empty($file->artist) || empty($file->title)) {
            report('artist or title empty');
            return;
        }

        $params = [
            'method' => 'track.scrobble',
            'api_key' => $apiKey,
            'sk' => $sk,
            'artist' => $file->artist,
            'track' => $file->title,
            'timestamp' => time(),
        ];

        if (!empty($file->album)) {
            $params['album'] = $file->album;
        }

        $trackNumber = $file->metadata['track_number'] ?? null;
        if (!empty($trackNumber)) {
            $params['trackNumber'] = $trackNumber;
        }

        $duration = $file->metadata['duration_seconds'] ?? null;
        if (!empty($duration)) {
            $params['duration'] = $duration;
        }

        $sig = self::create_api_sig($params, $secret);

        $bodyParams = $params + [
            'api_sig' => $sig,
            'format' => 'json',
        ];

        report('scrobbling: ' . $file->artist . ' - ' . $file->title);

        $scrobble = self::tryHttpPost('https://ws.audioscrobbler.com/2.0/', $bodyParams);

        if ($scrobble === null) {
            report(new \RuntimeException("Scrobble request failed for file {$file->id}"));
            return;
        }

        if (isset($scrobble->error)) {
            report(new \RuntimeException("Last.fm scrobble error {$scrobble->error}: {$scrobble->message}"));
        }
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

    // helpers

    public static function create_api_sig(array $params, string $secret) : string {
        // Step 1: Sort parameters alphabetically
        ksort($params);

        // Step 2: Concatenate parameters
        $concatenatedString = '';
        foreach($params as $key => $value) {
            // Step 3: Ensure parameters are utf8 encoded
            $key = mb_convert_encoding($key, 'UTF-8');
            $value = mb_convert_encoding($value, 'UTF-8');
            
            $concatenatedString .= $key . $value;
        }

        // Step 4: Append secret
        $concatenatedString .= $secret;

        // Step 5: Return md5 hash
        return md5($concatenatedString);

        /*
            Why the fuck is the API so complicated?? 
            $params = [
                'api_key' => 'xxxxxxxx',
                'method' => 'auth.getSession',
                'token' => 'xxxxxxx'
            ];
            $secret = 'mysecret';

            $apiSignature = createApiSignature($params, $secret);
        */
    }

    public static function construct_url(array $params) : string {
        // http://ws.audioscrobbler.com/2.0/?method=artist.getsimilar&artist=cher&api_key=YOUR_API_KEY&format=json
        
        $queryString = http_build_query($params);
        $url = "http://ws.audioscrobbler.com/2.0/?%s";

        $url = sprintf($url, $queryString);
        return $url;
    }
}