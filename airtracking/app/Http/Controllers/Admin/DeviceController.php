<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/** CRUD alat (khusus admin). Credential hanya tampil sekali saat dibuat atau diganti. */
class DeviceController extends Controller
{
    public function index(): View
    {
        return view('admin.devices', ['devices' => Device::with('credential')->orderBy('code')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'code' => ['required', 'regex:/^[A-Za-z0-9_-]{2,32}$/', 'unique:devices,code'],
            'name' => 'required|string|max:100',
            'location_code' => 'required|string|max:32',
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'credential' => 'nullable|string|min:8|max:128',
        ], ['code.regex' => 'ID alat: 2-32 karakter huruf, angka, - atau _.']);

        $plain = $v['credential'] ?? Str::random(24);
        $device = Device::create([
            'code' => $v['code'], 'name' => $v['name'], 'location_code' => $v['location_code'],
            'latitude' => $v['latitude'] ?? null, 'longitude' => $v['longitude'] ?? null,
        ]);
        $device->setCredential($plain);

        return redirect()->route('admin.devices')->with('credential', ['code' => $device->code, 'key' => $plain]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $v = $request->validate(['name' => 'required|string|max:100', 'location_code' => 'required|string|max:32']);
        $device->update($v + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.devices')->with('ok', "Alat {$device->code} diperbarui.");
    }

    /** Halaman peta admin: pilih alat, klik/geser titik di peta, simpan koordinat. */
    public function locations(): View
    {
        $devices = Device::orderBy('code')->get()->map(fn (Device $d) => [
            'id' => $d->id, 'code' => $d->code, 'name' => $d->name, 'loc' => $d->location_code,
            'lat' => $d->latitude, 'lng' => $d->longitude,
        ]);

        return view('admin.lokasi', ['devices' => $devices]);
    }

    public function setLocation(Request $request, Device $device): RedirectResponse
    {
        $v = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ], [
            'latitude.required' => 'Pilih titik di peta atau isi latitude.',
            'longitude.required' => 'Pilih titik di peta atau isi longitude.',
            'latitude.between' => 'Latitude harus antara -90 dan 90.',
            'longitude.between' => 'Longitude harus antara -180 dan 180.',
        ]);
        $device->update($v);

        return redirect()->route('admin.locations', ['alat' => $device->code])->with('ok', "Titik {$device->code} disimpan.");
    }

    public function rotate(Device $device): RedirectResponse
    {
        $plain = Str::random(24);
        $device->setCredential($plain);

        return redirect()->route('admin.devices')->with('credential', ['code' => $device->code, 'key' => $plain]);
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();   // riwayat, prediksi, dan credential ikut terhapus (cascade)
        return redirect()->route('admin.devices')->with('ok', "Alat {$device->code} dan seluruh riwayatnya dihapus.");
    }
}
