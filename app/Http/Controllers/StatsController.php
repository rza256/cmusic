<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class StatsController extends Controller {
    public function home(Request $request) {
        return view('home');
    }
}