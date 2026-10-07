<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $shift = Shift::create(['name' => 'Reguler', 'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'toleransi_menit' => 15]);
        $divs = collect(['IT', 'HRD', 'Keuangan'])->map(fn ($n) => Division::create(['name' => $n]));

        User::create([
            'name' => 'Admin Kantor', 'email' => 'admin@presensi.test', 'password' => 'password',
            'role' => 'admin', 'nip' => 'ADM001', 'shift_id' => $shift->id,
        ]);

        foreach (['Budi Santoso', 'Siti Aminah', 'Andi Pratama'] as $i => $name) {
            User::create([
                'name' => $name, 'email' => 'karyawan'.($i + 1).'@presensi.test', 'password' => 'password',
                'role' => 'karyawan', 'nip' => 'KRY00'.($i + 1),
                'division_id' => $divs[$i]->id, 'shift_id' => $shift->id,
            ]);
        }
    }
}
