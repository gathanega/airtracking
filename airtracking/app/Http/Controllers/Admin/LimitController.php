<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParameterLimit;
use App\Services\Quality;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Ubah rentang normal (baku mutu) per parameter. */
class LimitController extends Controller
{
    public function index(): View
    {
        return view('admin.limits', ['limits' => ParameterLimit::orderBy('id')->get()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['limits' => 'required|array', 'limits.*.lo' => 'required|numeric', 'limits.*.hi' => 'required|numeric']);

        $in = $request->input('limits');
        foreach ($in as $param => $v) {
            if ((float) $v['hi'] <= (float) $v['lo']) {
                return back()->withErrors(['limits' => "Batas atas {$param} harus lebih besar dari batas bawah."])->withInput();
            }
        }
        foreach ($in as $param => $v) {
            ParameterLimit::where('param', $param)->update(['lo' => $v['lo'], 'hi' => $v['hi']]);
        }
        Quality::flushLimits();

        return redirect()->route('admin.limits')->with('ok', 'Rentang normal disimpan.');
    }
}
