<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminController extends Controller
{
    public function clearConfig()
    {
        Artisan::call('config:clear');
        return response()->json([
            'status' => true,
            'message' => 'Configuration cache cleared!'
        ]);
    }
}
