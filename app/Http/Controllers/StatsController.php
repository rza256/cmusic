<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StatsController extends Controller {
    public function home(Request $request) {
        return view('home');
    }
}