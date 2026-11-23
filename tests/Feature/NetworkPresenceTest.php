<?php

namespace Tests\Feature;

use App\Models\NetworkPresence;
use App\Models\OfficeNetwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_heartbeat_is_recorded_only_from_an_active_office_network(): void
    {
        $employee = $this->employee('Karyawan', 'employee@example.test');
        $this->actingAs($employee)
            ->withServerVariables(['REMOTE_ADDR' => '192.168.1.25'])
            ->postJson(route('karyawan.network-presence'))
            ->assertForbidden();

        OfficeNetwork::create(['label' => 'Wifi Kantor', 'cidr' => '192.168.1.0/24']);

        $this->postJson(route('karyawan.network-presence'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('network_presences', [
            'user_id' => $employee->id,
            'ip_address' => '192.168.1.25',
        ]);

        $otherEmployee = $this->employee('Karyawan Luar', 'outside@example.test');
        $this->actingAs($otherEmployee)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])
            ->postJson(route('karyawan.network-presence'))
            ->assertForbidden();

        $this->assertDatabaseMissing('network_presences', ['user_id' => $otherEmployee->id]);
    }

    public function test_admin_presence_list_marks_entries_online_only_within_one_minute(): void
    {
        OfficeNetwork::create(['label' => 'Wifi Kantor', 'cidr' => '192.168.1.0/24']);
        $onlineEmployee = $this->employee('Karyawan Online', 'online@example.test', 'Android · Chrome');
        $offlineEmployee = $this->employee('Karyawan Offline', 'offline@example.test', 'Windows · Chrome');

        NetworkPresence::create([
            'user_id' => $onlineEmployee->id,
            'ip_address' => '192.168.1.25',
            'last_seen_at' => now()->subSeconds(30),
        ]);
        NetworkPresence::create([
            'user_id' => $offlineEmployee->id,
            'ip_address' => '192.168.1.30',
            'last_seen_at' => now()->subMinutes(2),
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.jaringan.presence'))
            ->assertOk()
            ->assertJsonPath('0.name', $onlineEmployee->name)
            ->assertJsonPath('0.online', true)
            ->assertJsonPath('1.name', $offlineEmployee->name)
            ->assertJsonPath('1.online', false);

        $this->get(route('admin.jaringan'))
            ->assertOk()
            ->assertSee($onlineEmployee->name)
            ->assertSee($offlineEmployee->name)
            ->assertSee('Online')
            ->assertSee('Offline');
    }

    private function employee(string $name, string $email, ?string $deviceName = null): User
    {
        $employee = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => 'karyawan',
        ]);

        if ($deviceName) {
            $employee->forceFill(['device_name' => $deviceName])->save();
        }

        return $employee;
    }
}
