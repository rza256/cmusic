<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\ProcessingJob;
use App\Models\Setting;

class SettingsController extends Controller {
    public function settings(Request $request)
    {
        $settings = Setting::orderBy('id', 'desc')->get();

        return view('settings', [
            'settings' => $settings
        ]);
    }
}
