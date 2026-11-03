<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\DeviceRequest;
use App\Models\Division;
use App\Models\LeaveRequest;
use App\Models\OfficeNetwork;
use App\Models\Shift;
use App\Models\User;
use App\Services\NetworkGuard;
use App\Services\QrToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    private Shift $day;
    private Shift $night;
    private Division $div;
    private User $admin;
    private User $emp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->day = Shift::create(['name' => 'Reguler', 'jam_masuk' => '08:00', 'jam_pulang' => '17:00', 'toleransi_menit' => 15]);
        $this->night = Shift::create(['name' => 'Malam', 'jam_masuk' => '20:00', 'jam_pulang' => '08:00', 'toleransi_menit' => 15]);
        $this->div = Division::create(['name' => 'IT']);
        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@t.test', 'password' => 'password', 'role' => 'admin', 'nip' => 'A1']);
        $this->emp = $this->makeEmp('Budi Santoso', 'budi@t.test', 'K1', $this->day);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeEmp(string $name, string $email, string $nip, Shift $shift): User
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => 'password', 'role' => 'karyawan',
            'nip' => $nip, 'division_id' => $this->div->id, 'shift_id' => $shift->id]);
    }

    private function at(int $d, int $h, int $m): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, $d, $h, $m, 0, config('app.timezone')));
    }

    private function jpg(): UploadedFile
    {
        $bytes = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=');

        return UploadedFile::fake()->createWithContent('wajah.jpg', $bytes);
    }

    private function scan(User $u, ?string $type, bool $photo = true)
    {
        $payload = QrToken::make($u, $type);

        return $this->post('/scan', ['payload' => $payload] + ($photo ? ['photo' => $this->jpg()] : []), ['Accept' => 'application/json']);
    }

    // ---------------------------------------------------------------- halaman
    public function test_public_pages_and_login_script(): void
    {
        $this->get('/')->assertOk();
        $this->get('/lupa-password')->assertOk();
        $r = $this->get('/login')->assertOk();
        $r->assertSee('id="loginForm"', false);
        $r->assertSee('pageshow', false);
    }

    public function test_all_admin_pages_render(): void
    {
        $att = Attendance::create(['user_id' => $this->emp->id, 'date' => '2026-11-03', 'check_in' => '08:00:00', 'check_out' => '18:00:00',
            'status' => 'hadir', 'check_in_photo' => 'attendance/x_masuk.jpg', 'check_out_photo' => 'attendance/x_pulang.jpg', 'overtime_minutes' => 60]);
        Storage::disk('local')->put('attendance/x_masuk.jpg', 'x');
        LeaveRequest::create(['user_id' => $this->emp->id, 'type' => 'izin', 'start_date' => '2026-11-05', 'end_date' => '2026-11-06', 'reason' => 'x']);
        OfficeNetwork::create(['label' => 'Wifi', 'cidr' => '192.168.1.0/24']);
        DeviceRequest::create(['user_id' => $this->emp->id, 'device_hash' => str_repeat('a', 64), 'user_agent' => 'Mozilla Android Chrome/1', 'ip' => '1.1.1.1']);
        $this->emp->forceFill(['device_hash' => str_repeat('b', 64), 'device_name' => 'Android · Chrome', 'device_bound_at' => now()])->save();

        $urls = [
            route('admin.dashboard'), route('admin.karyawan.index'), route('admin.karyawan.create'), route('admin.karyawan.edit', $this->emp),
            route('admin.divisi'), route('admin.shift'), route('admin.izin'), route('admin.izin', ['status' => 'semua']),
            route('admin.jaringan'), route('admin.perangkat'),
            route('admin.presensi.index'), route('admin.presensi.index', ['status' => 'hadir', 'q' => 'bud']),
            route('admin.presensi.create'), route('admin.presensi.edit', $att),
            route('admin.laporan', ['bulan' => '2026-11']), route('admin.laporan', ['bulan' => '2026-11', 'minggu' => 1]),
            route('admin.laporan', ['bulan' => '2026-11', 'minggu' => 99]), route('admin.laporan', ['bulan' => 'ngawur']),
            route('karyawan.profil'),
        ];
        $this->actingAs($this->admin);
        foreach ($urls as $u) {
            $this->get($u)->assertOk();
        }
        $this->get(route('admin.laporan.export', ['bulan' => '2026-11']))->assertOk();
        $this->get(route('admin.laporan.export', ['bulan' => '2026-11', 'minggu' => 1]))->assertOk();
        $this->get(route('admin.laporan.foto', [$att, 'masuk']))->assertOk();
        $this->get(route('admin.laporan.foto', [$att, 'pulang']))->assertNotFound(); // file tidak ada
    }

    public function test_all_karyawan_pages_render(): void
    {
        $this->actingAs($this->emp);
        foreach (['karyawan.dashboard', 'karyawan.status', 'karyawan.riwayat', 'karyawan.profil', 'karyawan.izin'] as $r) {
            $this->get(route($r))->assertOk();
        }
        $this->get(route('karyawan.qr', ['type' => 'masuk']))->assertOk()->assertJsonStructure(['ok', 'payload']);
        $this->get(route('karyawan.qr', ['type' => 'pulang']))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    // ---------------------------------------------------------------- login & perangkat
    public function test_login_binds_device_and_blocks_other_device(): void
    {
        $this->withCookie('device_token', 'TOKEN-A')->post('/login', ['email' => 'budi@t.test', 'password' => 'password'])
            ->assertRedirect();
        $this->assertSame(hash('sha256', 'TOKEN-A'), $this->emp->fresh()->device_hash);
        auth()->logout();

        $this->withCookie('device_token', 'TOKEN-B')->post('/login', ['email' => 'budi@t.test', 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseHas('device_requests', ['user_id' => $this->emp->id, 'status' => 'pending', 'device_hash' => hash('sha256', 'TOKEN-B')]);

        // admin menyetujui
        $req = DeviceRequest::first();
        $this->actingAs($this->admin)->put(route('admin.perangkat.decide', $req), ['status' => 'disetujui'])->assertRedirect();
        $this->assertSame(hash('sha256', 'TOKEN-B'), $this->emp->fresh()->device_hash);

        $this->delete(route('admin.perangkat.reset', $this->emp))->assertRedirect();
        $this->assertNull($this->emp->fresh()->device_hash);
    }

    public function test_wrong_password_and_admin_login(): void
    {
        $this->post('/login', ['email' => 'budi@t.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'admin@t.test', 'password' => 'password'])->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);
    }

    // ---------------------------------------------------------------- scan
    public function test_scan_masuk_pulang_overtime_and_photo(): void
    {
        $this->at(3, 8, 5);
        $this->post('/scan/verify', ['payload' => QrToken::make($this->emp, 'masuk')], ['Accept' => 'application/json'])
            ->assertOk()->assertJson(['ok' => true, 'type' => 'masuk']);
        $this->scan($this->emp, 'masuk')->assertOk()->assertJson(['ok' => true, 'type' => 'masuk', 'status' => 'hadir']);
        $a = Attendance::first();
        $this->assertNotNull($a->check_in_photo);
        Storage::disk('local')->assertExists($a->check_in_photo);

        // masuk dua kali ditolak
        $this->at(3, 8, 20);
        $this->scan($this->emp, 'masuk')->assertStatus(422);
        $this->post('/scan/verify', ['payload' => QrToken::make($this->emp, 'masuk')], ['Accept' => 'application/json'])->assertStatus(422);

        // pulang jam 18:30 => lembur 90 menit
        $this->at(3, 18, 30);
        $this->scan($this->emp, 'pulang')->assertOk()->assertJson(['type' => 'pulang', 'overtime_minutes' => 90]);
        $a->refresh();
        $this->assertSame(90, $a->overtime_minutes);
        $this->assertNotNull($a->check_out_photo);

        // sudah lengkap
        $this->at(3, 19, 0);
        $this->scan($this->emp, 'pulang')->assertStatus(422);
    }

    public function test_scan_late_and_pulang_without_masuk_and_no_photo(): void
    {
        $this->at(3, 9, 0);
        $this->scan($this->emp, 'pulang')->assertStatus(422);      // belum masuk
        $this->scan($this->emp, 'masuk', false)->assertStatus(422); // tanpa foto
        $this->scan($this->emp, 'masuk')->assertOk()->assertJson(['status' => 'terlambat']);
        $this->assertSame(60, Attendance::first()->late_minutes);
    }

    public function test_scan_legacy_untyped_qr_and_invalid_qr(): void
    {
        $this->at(3, 8, 0);
        $this->scan($this->emp, null)->assertOk()->assertJson(['type' => 'masuk']);
        $this->post('/scan', ['payload' => 'ABSEN|1|2|M|abc', 'photo' => $this->jpg()], ['Accept' => 'application/json'])->assertStatus(422);
        $this->post('/scan/verify', ['payload' => 'sembarang'], ['Accept' => 'application/json'])->assertStatus(422);

        // QR kedaluwarsa (slot 10 menit lalu) walau tanda tangannya benar
        $slot = intdiv(time(), 60) - 10;
        $sig = substr(hash_hmac('sha256', "{$this->emp->id}|{$slot}|M", config('app.key')), 0, 24);
        $this->post('/scan/verify', ['payload' => "ABSEN|{$this->emp->id}|{$slot}|M|{$sig}"], ['Accept' => 'application/json'])->assertStatus(422);

        // QR Masuk tidak bisa diubah jadi QR Pulang
        $p = QrToken::make($this->emp, 'masuk');
        $tampered = preg_replace('/\|M\|/', '|P|', $p);
        $this->post('/scan/verify', ['payload' => $tampered], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_scan_rejected_when_on_leave_or_inactive(): void
    {
        $this->at(3, 8, 0);
        LeaveRequest::create(['user_id' => $this->emp->id, 'type' => 'sakit', 'start_date' => '2026-11-03', 'end_date' => '2026-11-04', 'reason' => 'x', 'status' => 'disetujui']);
        $this->scan($this->emp, 'masuk')->assertStatus(422);
        $this->assertSame(0, Attendance::count());

        $this->emp->update(['is_active' => false]);
        $this->post('/scan/verify', ['payload' => QrToken::make($this->emp, 'masuk')], ['Accept' => 'application/json'])->assertStatus(422);
    }

    public function test_night_shift_flow(): void
    {
        $g = $this->makeEmp('Joko Satpam', 'joko@t.test', 'K9', $this->night);

        $this->at(3, 20, 5);
        $this->scan($g, 'masuk')->assertOk()->assertJson(['status' => 'hadir']);

        $this->at(4, 8, 10); // pagi hari berikutnya
        $this->scan($g, 'masuk')->assertStatus(422);              // sudah masuk (shift kemarin)
        $this->scan($g, 'pulang')->assertOk()->assertJson(['type' => 'pulang', 'overtime_minutes' => 10]);
        $this->assertSame(1, Attendance::where('user_id', $g->id)->count());
        $this->assertSame('2026-11-03', Attendance::where('user_id', $g->id)->first()->date->toDateString());

        // masuk telat setelah tengah malam => tetap shift kemarin
        $g2 = $this->makeEmp('Satpam Dua', 'dua@t.test', 'K10', $this->night);
        $this->at(4, 1, 0);
        $this->scan($g2, 'masuk')->assertOk()->assertJson(['status' => 'terlambat']);
        $a = Attendance::where('user_id', $g2->id)->first();
        $this->assertSame('2026-11-03', $a->date->toDateString());
        $this->assertSame(300, $a->late_minutes);

        // dashboard karyawan malam memuat data shift yang benar pada pagi hari
        $this->at(4, 8, 30);
        $this->actingAs($g2)->get(route('karyawan.dashboard'))->assertOk();
    }

    // ---------------------------------------------------------------- presensi manual
    public function test_manual_attendance_crud(): void
    {
        $this->actingAs($this->admin);
        $r = $this->post(route('admin.presensi.store'), ['user_id' => $this->emp->id, 'date' => '2026-11-02', 'type' => 'hadir', 'check_in' => '09:00', 'check_out' => '18:00', 'note' => 'lupa scan']);
        $r->assertRedirect();
        $a = Attendance::first();
        $this->assertTrue($a->is_manual);
        $this->assertSame('terlambat', $a->status);
        $this->assertSame(60, $a->late_minutes);
        $this->assertSame(60, $a->overtime_minutes);

        // duplikat tanggal
        $this->post(route('admin.presensi.store'), ['user_id' => $this->emp->id, 'date' => '2026-11-02', 'type' => 'hadir', 'check_in' => '08:00'])->assertSessionHasErrors('date');
        // jam pulang sebelum masuk
        $this->post(route('admin.presensi.store'), ['user_id' => $this->emp->id, 'date' => '2026-11-03', 'type' => 'hadir', 'check_in' => '09:00', 'check_out' => '08:00'])->assertSessionHasErrors('check_out');
        // jam masuk wajib untuk hadir
        $this->post(route('admin.presensi.store'), ['user_id' => $this->emp->id, 'date' => '2026-11-04', 'type' => 'hadir'])->assertSessionHasErrors('check_in');
        // sakit tanpa jam
        $this->post(route('admin.presensi.store'), ['user_id' => $this->emp->id, 'date' => '2026-11-05', 'type' => 'sakit', 'note' => 'surat dokter'])->assertRedirect();
        $this->assertSame('sakit', Attendance::whereDate('date', '2026-11-05')->first()->status);

        // edit jadi izin lalu hapus
        $this->put(route('admin.presensi.update', $a), ['type' => 'izin'])->assertRedirect();
        $this->assertSame('izin', $a->fresh()->status);
        $this->assertNull($a->fresh()->check_in);
        $this->delete(route('admin.presensi.destroy', $a))->assertRedirect();
        $this->assertDatabaseMissing('attendances', ['id' => $a->id]);
    }

    public function test_manual_attendance_overnight_shift(): void
    {
        $g = $this->makeEmp('Joko', 'joko@t.test', 'K9', $this->night);
        $this->actingAs($this->admin)->post(route('admin.presensi.store'), ['user_id' => $g->id, 'date' => '2026-11-02', 'type' => 'hadir', 'check_in' => '20:00', 'check_out' => '09:00'])->assertRedirect();
        $a = Attendance::first();
        $this->assertSame('hadir', $a->status);
        $this->assertSame(60, $a->overtime_minutes);
    }

    // ---------------------------------------------------------------- lupa password
    public function test_forgot_password_rules(): void
    {
        $body = ['email' => 'budi@t.test', 'nip' => 'K1', 'password' => 'baru123', 'password_confirmation' => 'baru123'];

        // NIP salah
        $this->post('/lupa-password', ['nip' => 'XX'] + $body)->assertSessionHasErrors('email');
        // perangkat terdaftar tapi cookie berbeda
        $this->emp->forceFill(['device_hash' => hash('sha256', 'TOKEN-A')])->save();
        $this->withCookie('device_token', 'TOKEN-B')->post('/lupa-password', $body)->assertSessionHasErrors('email');
        // perangkat benar
        $this->withCookie('device_token', 'TOKEN-A')->post('/lupa-password', $body)->assertRedirect(route('login'));
        $this->assertTrue(\Hash::check('baru123', $this->emp->fresh()->password));
        // admin tidak bisa reset lewat sini
        $this->post('/lupa-password', ['email' => 'admin@t.test', 'nip' => 'A1'] + $body)->assertSessionHasErrors('email');
    }

    public function test_forgot_password_for_account_without_device_is_rejected(): void
    {
        $body = ['email' => 'budi@t.test', 'nip' => 'K1', 'password' => 'baru123', 'password_confirmation' => 'baru123'];
        $this->post('/lupa-password', $body)->assertSessionHasErrors('email');
        $this->assertTrue(\Hash::check('password', $this->emp->fresh()->password)); // password tidak berubah
    }

    // ---------------------------------------------------------------- profil, jaringan
    public function test_profile_update_photo_and_password(): void
    {
        $this->actingAs($this->emp);
        $this->put(route('karyawan.profil.update'), ['name' => 'Budi Baru', 'email' => 'budi@t.test', 'phone' => '0812', 'photo' => $this->jpg()])->assertRedirect();
        $u = $this->emp->fresh();
        $this->assertSame('Budi Baru', $u->name);
        $this->assertNotNull($u->photo);
        Storage::disk('public')->assertExists($u->photo);
        $this->get(route('karyawan.profil'))->assertOk();

        $this->put(route('karyawan.profil.update'), ['name' => 'Budi Baru', 'email' => 'budi@t.test', 'remove_photo' => 1])->assertRedirect();
        $this->assertNull($this->emp->fresh()->photo);

        $this->put(route('karyawan.profil.password'), ['current_password' => 'salah', 'password' => 'abcdef', 'password_confirmation' => 'abcdef'])->assertSessionHasErrors('current_password');
        $this->put(route('karyawan.profil.password'), ['current_password' => 'password', 'password' => 'abcdef', 'password_confirmation' => 'abcdef'])->assertSessionHasNoErrors();
    }

    public function test_network_guard(): void
    {
        $this->assertTrue(NetworkGuard::allows('10.1.1.1')); // belum ada jaringan => nonaktif
        OfficeNetwork::create(['label' => 'LAN', 'cidr' => '192.168.1.0/24']);
        OfficeNetwork::create(['label' => 'IPv6', 'cidr' => '2001:db8::/32']);
        OfficeNetwork::create(['label' => 'Publik', 'cidr' => '103.12.34.56']);
        $this->assertTrue(NetworkGuard::allows('192.168.1.77'));
        $this->assertFalse(NetworkGuard::allows('192.168.2.1'));
        $this->assertTrue(NetworkGuard::allows('2001:db8::1'));
        $this->assertFalse(NetworkGuard::allows('2001:db9::1'));
        $this->assertTrue(NetworkGuard::allows('103.12.34.56'));
        $this->assertFalse(NetworkGuard::allows('103.12.34.57'));

        $this->actingAs($this->emp)->get(route('karyawan.qr'))->assertStatus(403);
        $this->actingAs($this->emp)->get(route('karyawan.dashboard'))->assertOk()->assertSee('tidak terhubung', false);
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.20'])->actingAs($this->emp)->get(route('karyawan.qr'))->assertOk();
    }

    public function test_leave_request_and_approval(): void
    {
        $this->actingAs($this->emp)->post(route('karyawan.izin.store'), ['type' => 'sakit', 'start_date' => '2026-11-09', 'end_date' => '2026-11-13', 'reason' => 'demam', 'attachment' => UploadedFile::fake()->create('surat.pdf', 100, 'application/pdf')])->assertRedirect();
        $req = LeaveRequest::first();
        $this->assertNotNull($req->attachment);
        $this->actingAs($this->admin)->put(route('admin.izin.decide', $req), ['status' => 'disetujui', 'admin_note' => 'ok'])->assertRedirect();
        $this->assertSame(5, Attendance::where('status', 'sakit')->count()); // Sen-Jum
    }

    public function test_divisi_shift_employee_crud(): void
    {
        $this->actingAs($this->admin);
        $this->post(route('admin.divisi.store'), ['name' => 'HRD'])->assertRedirect();
        $d = Division::where('name', 'HRD')->first();
        $this->put(route('admin.divisi.update', $d), ['name' => 'HR'])->assertRedirect();
        $this->delete(route('admin.divisi.destroy', $d))->assertRedirect();

        $this->post(route('admin.shift.store'), ['name' => 'Malam2', 'jam_masuk' => '22:00', 'jam_pulang' => '06:00', 'toleransi_menit' => 10])->assertSessionHasNoErrors();
        $this->post(route('admin.shift.store'), ['name' => 'X', 'jam_masuk' => '08:00', 'jam_pulang' => '08:00', 'toleransi_menit' => 10])->assertSessionHasErrors('jam_pulang');

        $this->post(route('admin.karyawan.store'), ['name' => 'Baru', 'email' => 'baru@t.test', 'role' => 'karyawan', 'password' => 'secret1', 'is_active' => 1, 'shift_id' => $this->day->id])->assertSessionHasNoErrors();
        $u = User::where('email', 'baru@t.test')->first();
        $this->put(route('admin.karyawan.update', $u), ['name' => 'Baru2', 'email' => 'baru@t.test', 'role' => 'karyawan', 'is_active' => 1])->assertSessionHasNoErrors();
        $this->delete(route('admin.karyawan.destroy', $u))->assertRedirect();
        $this->post(route('admin.jaringan.store'), ['label' => 'x', 'cidr' => '300.1.1.1'])->assertSessionHasErrors('cidr');
        $this->post(route('admin.jaringan.store'), ['label' => 'x', 'cidr' => '10.0.0.0/8'])->assertSessionHasNoErrors();
    }
}
