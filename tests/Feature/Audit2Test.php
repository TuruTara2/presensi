<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Audit2Test extends TestCase
{
    use RefreshDatabase;

    public function test_odd_inputs_do_not_500(): void
    {
        $shift = Shift::create(['name' => 'R', 'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'toleransi_menit' => 15]);
        $admin = User::create(['name' => 'A', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'admin']);
        $emp = User::create(['name' => 'B', 'email' => 'b@t.test', 'password' => 'password', 'role' => 'karyawan', 'shift_id' => $shift->id]);

        $bad = [];
        $this->actingAs($emp);
        foreach (['abc', '2026', '2026-13', '', '9999-99', "' OR 1=1"] as $b) {
            $s = $this->get(route('karyawan.riwayat', ['bulan' => $b]))->getStatusCode();
            if ($s >= 500) { $bad[] = "karyawan.riwayat bulan=[$b] => $s"; }
        }
        $this->actingAs($admin);
        foreach (['abc', '2026-13', 'x-y'] as $b) {
            foreach ([route('admin.laporan', ['bulan' => $b, 'minggu' => 'abc']), route('admin.presensi.index', ['bulan' => $b]),
                      route('admin.laporan.export', ['bulan' => $b, 'minggu' => 'zz'])] as $u) {
                $s = $this->get($u)->getStatusCode();
                if ($s >= 500) { $bad[] = "$u => $s"; }
            }
        }
        foreach ([route('admin.izin', ['status' => 'aneh']), route('admin.karyawan.index', ['q' => '%', 'division_id' => 'x']), route('admin.laporan', ['division_id' => 'x'])] as $u) {
            $s = $this->get($u)->getStatusCode();
            if ($s >= 500) { $bad[] = "$u => $s"; }
        }
        $this->assertSame([], $bad, implode("\n", $bad));
    }

    public function test_report_weeks_every_month(): void
    {
        $admin = User::create(['name' => 'A', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'admin']);
        $this->actingAs($admin);
        $bad = [];
        foreach (range(2026, 2028) as $y) {
            foreach (range(1, 12) as $m) {
                $bulan = sprintf('%04d-%02d', $y, $m);
                foreach ([1, 2, 3, 4, 5, 6] as $w) {
                    $s = $this->get(route('admin.laporan', ['bulan' => $bulan, 'minggu' => $w]))->getStatusCode();
                    if ($s !== 200) { $bad[] = "$bulan minggu $w => $s"; }
                }
            }
        }
        $this->assertSame([], $bad, implode("\n", $bad));
    }
}
