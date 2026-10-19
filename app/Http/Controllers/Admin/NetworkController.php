<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficeNetwork;
use App\Services\NetworkGuard;
use Illuminate\Http\Request;

class NetworkController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.jaringan', [
            'items' => OfficeNetwork::orderBy('label')->get(),
            'enabled' => NetworkGuard::enabled(),
            'myIp' => $request->ip(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => ['required', 'max:100'],
            'cidr' => ['required', 'max:64', function ($attr, $value, $fail) {
                [$ip, $bits] = array_pad(explode('/', trim($value), 2), 2, null);
                if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                    $fail('Format IP tidak valid. Contoh: 192.168.1.0/24 atau 103.12.34.56');
                } elseif ($bits !== null && (! ctype_digit($bits) || (int) $bits > (str_contains($ip, ':') ? 128 : 32))) {
                    $fail('Panjang prefix (bagian setelah /) tidak valid.');
                }
            }],
        ]);
        $data['cidr'] = trim($data['cidr']);
        OfficeNetwork::create($data + ['is_active' => true]);

        return back()->with('success', 'Jaringan kantor ditambahkan. Validasi IP sekarang aktif.');
    }

    public function toggle(OfficeNetwork $jaringan)
    {
        $jaringan->update(['is_active' => ! $jaringan->is_active]);

        return back()->with('success', 'Status jaringan diperbarui.');
    }

    public function destroy(OfficeNetwork $jaringan)
    {
        $jaringan->delete();

        return back()->with('success', 'Jaringan dihapus.');
    }
}
