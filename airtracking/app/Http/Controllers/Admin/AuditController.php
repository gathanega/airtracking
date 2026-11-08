<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

/** Menampilkan jejak audit terbaru untuk admin. */
class AuditController extends Controller
{
    public function index(): View
    {
        return view('admin.audit', [
            'logs' => AuditLog::with('user')->orderByDesc('id')->limit(100)->get(),
        ]);
    }
}
