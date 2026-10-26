<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** CRUD presensi manual: absen yang terlupa, izin, sakit, dan cuti per karyawan. */
class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->input('bulan')) ? $request->bulan : now()->format('Y-m');
        $from = Carbon::parse($bulan.'-01')->startOfMonth();
        $to = $from->copy()->endOfMonth();

        $rows = Attendance::with('user.division')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kw = '%'.$request->q.'%';
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', $kw)->orWhere('nip', 'like', $kw));
            })
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('admin.presensi.index', compact('rows', 'bulan'));
    }

    public function create()
    {
        return view('admin.presensi.form', ['att' => new Attendance, 'users' => $this->employees()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $user = User::where('role', 'karyawan')->findOrFail($data['user_id']);
        $date = Carbon::parse($data['date'])->startOfDay();

        if (Attendance::where('user_id', $user->id)->whereDate('date', $date->toDateString())->exists()) {
            return back()->withInput()->withErrors(['date' => "{$user->name} sudah punya data presensi pada {$date->format('d/m/Y')}. Edit data yang sudah ada dari daftar presensi."]);
        }

        $fields = $this->fields($user, $date, $data);
        if (is_string($fields)) {
            return back()->withInput()->withErrors(['check_out' => $fields]);
        }

        Attendance::create([
            'user_id' => $user->id,
            'date' => $date->toDateString(),
            'is_manual' => true,
            'manual_by' => $request->user()->id,
        ] + $fields);

        return redirect()->route('admin.presensi.index', ['bulan' => $date->format('Y-m')])
            ->with('success', "Presensi {$user->name} tanggal {$date->format('d/m/Y')} berhasil disimpan.");
    }

    public function edit(Attendance $presensi)
    {
        return view('admin.presensi.form', ['att' => $presensi->load('user.division'), 'users' => collect()]);
    }

    public function update(Request $request, Attendance $presensi)
    {
        $data = $this->validated($request, false);
        $fields = $this->fields($presensi->user, $presensi->date, $data);
        if (is_string($fields)) {
            return back()->withInput()->withErrors(['check_out' => $fields]);
        }

        // Diubah jadi izin/sakit/cuti: foto scan sebelumnya tidak berlaku lagi
        if ($data['type'] !== 'hadir') {
            $this->deletePhotos($presensi);
            $fields += ['check_in_photo' => null, 'check_out_photo' => null];
        }

        $presensi->update($fields + ['is_manual' => true, 'manual_by' => $request->user()->id]);

        return redirect()->route('admin.presensi.index', ['bulan' => $presensi->date->format('Y-m')])
            ->with('success', 'Presensi berhasil diperbarui.');
    }

    public function destroy(Attendance $presensi)
    {
        $this->deletePhotos($presensi);
        $bulan = $presensi->date->format('Y-m');
        $presensi->delete();

        return redirect()->route('admin.presensi.index', ['bulan' => $bulan])->with('success', 'Data presensi dihapus.');
    }

    private function employees()
    {
        return User::with('division')->where('role', 'karyawan')->where('is_active', true)->orderBy('name')->get();
    }

    private function validated(Request $request, bool $creating): array
    {
        return $request->validate(array_filter([
            'user_id' => $creating ? ['required', 'exists:users,id'] : null,
            'date' => $creating ? ['required', 'date'] : null,
            'type' => ['required', 'in:hadir,izin,sakit,cuti'],
            'check_in' => ['nullable', 'required_if:type,hadir', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'note' => ['nullable', 'string', 'max:300'],
        ]), [
            'check_in.required_if' => 'Jam masuk wajib diisi untuk status hadir.',
        ]);
    }

    /** Kolom presensi hasil perhitungan, atau string pesan error. */
    private function fields(User $user, Carbon $date, array $data): array|string
    {
        $note = ['note' => $data['note'] ?? null];

        if ($data['type'] !== 'hadir') {
            return $note + ['status' => $data['type'], 'check_in' => null, 'check_out' => null, 'late_minutes' => 0, 'overtime_minutes' => 0];
        }

        $in = $data['check_in'];
        $out = $data['check_out'] ?? null;
        if ($out && ! AttendanceService::shiftFor($user)->isOvernight() && $out <= $in) {
            return 'Jam pulang harus setelah jam masuk.';
        }

        return $note + AttendanceService::manualFields($user, $date, $in, $out);
    }

    private function deletePhotos(Attendance $a): void
    {
        $files = array_filter([$a->check_in_photo, $a->check_out_photo]);
        if ($files) {
            Storage::disk('local')->delete($files);
        }
    }
}
