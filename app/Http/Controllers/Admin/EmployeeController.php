<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('division', 'shift')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('nip', 'like', "%$s%")->orWhere('email', 'like', "%$s%")))
            ->when($request->division_id, fn ($q, $d) => $q->where('division_id', $d))
            ->orderBy('name')->paginate(10)->withQueryString();

        return view('admin.employees.index', ['users' => $users, 'divisions' => Division::orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.employees.form', ['user' => new User(['is_active' => true, 'role' => 'karyawan']), ...$this->options()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        User::create($data);

        return redirect()->route('admin.karyawan.index')->with('success', 'Karyawan ditambahkan.');
    }

    public function edit(User $karyawan)
    {
        return view('admin.employees.form', ['user' => $karyawan, ...$this->options()]);
    }

    public function update(Request $request, User $karyawan)
    {
        $data = $this->validated($request, $karyawan);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $karyawan->update($data);

        return redirect()->route('admin.karyawan.index')->with('success', 'Data karyawan diperbarui.');
    }

    public function destroy(Request $request, User $karyawan)
    {
        if ($karyawan->id === $request->user()->id) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }
        $karyawan->delete();

        return back()->with('success', 'Karyawan dihapus.');
    }

    private function options(): array
    {
        return ['divisions' => Division::orderBy('name')->get(), 'shifts' => Shift::orderBy('name')->get()];
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user?->id)],
            'nip' => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(['admin', 'karyawan'])],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'password' => [$user ? 'nullable' : 'required', 'min:6'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
