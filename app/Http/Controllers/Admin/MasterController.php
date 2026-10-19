<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;

/** Divisi + Pengaturan jam kerja (shift) & toleransi keterlambatan */
class MasterController extends Controller
{
    public function divisi()
    {
        $items = Division::with(['users' => fn ($q) => $q->where('role', 'karyawan')->orderBy('name')])
            ->orderBy('name')->get();

        return view('admin.divisi', [
            'items' => $items,
            // karyawan yang sudah absen hari ini (hadir/terlambat) -> untuk bar kehadiran per divisi
            'presentIds' => Attendance::whereDate('date', today())->whereIn('status', ['hadir', 'terlambat'])->pluck('user_id')->all(),
            'totalEmployees' => User::where('role', 'karyawan')->where('is_active', true)->count(),
            'unassigned' => User::where('role', 'karyawan')->whereNull('division_id')->count(),
        ]);
    }

    public function divisiStore(Request $r)
    {
        $d = $r->validate(['name' => ['required', 'max:100', 'unique:divisions,name']]);
        Division::create($d);

        return back()->with('success', 'Divisi ditambahkan.');
    }

    public function divisiUpdate(Request $r, Division $divisi)
    {
        $d = $r->validate(['name' => ['required', 'max:100', 'unique:divisions,name,'.$divisi->id]]);
        $divisi->update($d);

        return back()->with('success', 'Divisi diperbarui.');
    }

    public function divisiDestroy(Division $divisi)
    {
        User::where('division_id', $divisi->id)->update(['division_id' => null]);
        $divisi->delete();

        return back()->with('success', 'Divisi dihapus.');
    }

    public function shift()
    {
        return view('admin.shift', ['items' => Shift::withCount('users')->orderBy('name')->get()]);
    }

    public function shiftStore(Request $r)
    {
        Shift::create($this->shiftData($r));

        return back()->with('success', 'Shift ditambahkan.');
    }

    public function shiftUpdate(Request $r, Shift $shift)
    {
        $shift->update($this->shiftData($r));

        return back()->with('success', 'Pengaturan shift diperbarui.');
    }

    public function shiftDestroy(Shift $shift)
    {
        User::where('shift_id', $shift->id)->update(['shift_id' => null]);
        $shift->delete();

        return back()->with('success', 'Shift dihapus.');
    }

    private function shiftData(Request $r): array
    {
        return $r->validate([
            'name' => ['required', 'max:100'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'jam_pulang' => ['required', 'date_format:H:i', 'different:jam_masuk'], // boleh lebih kecil = shift malam
            'toleransi_menit' => ['required', 'integer', 'min:0', 'max:240'],
        ]);
    }
}
