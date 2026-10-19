<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\View\View;

/** Panduan setup WiFi/AP ESP32 dan pembuat konfigurasi JSON. WiFi dan credential tidak pernah lewat sini. */
class WifiController extends Controller
{
    public function show(): View
    {
        return view('admin.wifi', ['devices' => Device::orderBy('code')->get(['code', 'name', 'location_code'])]);
    }
}
