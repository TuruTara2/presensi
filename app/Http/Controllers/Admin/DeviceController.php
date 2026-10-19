<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceRequest;
use App\Models\User;
use App\Support\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    public function index()
    {
        return view('admin.perangkat', [
            'requests' => DeviceRequest::with('user')->where('status', 'pending')->latest()->get(),
            'users' => User::where('role', 'karyawan')->orderBy('name')->get(),
        ]);
    }

    public function decide(Request $request, DeviceRequest $permintaan)
    {
        $data = $request->validate(['status' => ['required', 'in:disetujui,ditolak']]);

        if ($permintaan->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $permintaan->update(['status' => $data['status'], 'reviewed_by' => $request->user()->id]);

        if ($data['status'] === 'disetujui') {
            $user = $permintaan->user;
            $user->forceFill([
                'device_hash' => $permintaan->device_hash,
                'device_name' => Device::label($permintaan->user_agent),
                'device_bound_at' => now(),
            ])->save();

            // permintaan lain dari karyawan yang sama dibatalkan, perangkat lama otomatis logout
            DeviceRequest::where('user_id', $user->id)->where('status', 'pending')
                ->update(['status' => 'ditolak', 'reviewed_by' => $request->user()->id]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return back()->with('success', 'Permintaan perangkat '.$data['status'].'.');
    }

    public function reset(User $karyawan)
    {
        $karyawan->forceFill(['device_hash' => null, 'device_name' => null, 'device_bound_at' => null])->save();
        DB::table('sessions')->where('user_id', $karyawan->id)->delete();

        return back()->with('success', "Perangkat {$karyawan->name} direset. Login berikutnya akan menjadi perangkat terdaftar yang baru.");
    }
}
