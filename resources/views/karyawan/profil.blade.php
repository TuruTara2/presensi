@extends('layouts.app')
@section('title', 'Profil Saya')
@section('content')

{{-- Kartu identitas --}}
<div class="card mb-6">
    <div class="flex flex-wrap items-center gap-6">
        @include('partials.avatar', ['user' => $user, 'size' => 96])
        <div class="min-w-0 flex-1">
            <div class="font-heading text-[28px] font-semibold leading-tight text-ink">{{ $user->name }}</div>
            <div class="text-sm text-slate-500">{{ $user->email }}</div>
            <div class="mt-4 flex flex-wrap gap-2 text-sm">
                <span class="rounded bg-primary/10 px-2 py-1 font-semibold text-primary">{{ $user->isAdmin() ? 'Admin' : 'Karyawan' }}</span>
                @if ($user->nip)<span class="rounded bg-slate-100 px-2 py-1">NIP {{ $user->nip }}</span>@endif
                <span class="rounded bg-slate-100 px-2 py-1">{{ $user->division->name ?? 'Tanpa divisi' }}</span>
                @if ($user->shift)
                    <span class="rounded bg-slate-100 px-2 py-1">Shift {{ $user->shift->name }} &middot; {{ substr($user->shift->jam_masuk,0,5) }}&ndash;{{ substr($user->shift->jam_pulang,0,5) }}</span>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Ringkasan bulan ini --}}
@unless ($user->isAdmin())
<h2 class="mb-4 font-heading text-[22px] font-semibold text-ink">Ringkasan {{ now()->translatedFormat('F Y') }}</h2>
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="card"><div class="text-sm text-slate-500">Tepat waktu</div><div class="font-heading text-3xl font-semibold text-success-text">{{ $stats['hadir'] }}</div><div class="text-sm text-slate-400">hari</div></div>
    <div class="card"><div class="text-sm text-slate-500">Terlambat</div><div class="font-heading text-3xl font-semibold text-amber-600">{{ $stats['terlambat'] }}</div><div class="text-sm text-slate-400">hari</div></div>
    <div class="card"><div class="text-sm text-slate-500">Izin / Sakit / Cuti</div><div class="font-heading text-3xl font-semibold text-slate-600">{{ $stats['izin'] }}</div><div class="text-sm text-slate-400">hari</div></div>
    <div class="card"><div class="text-sm text-slate-500">Total lembur</div><div class="font-heading text-3xl font-semibold text-primary">{{ \App\Support\Duration::format($stats['lembur']) }}</div><div class="text-sm text-slate-400">bulan ini</div></div>
</div>
@endunless

<div class="grid gap-6 lg:grid-cols-3">
    {{-- Data diri + foto --}}
    <form method="POST" action="{{ route('karyawan.profil.update') }}" enctype="multipart/form-data" class="card space-y-6 lg:col-span-2">
        @csrf @method('PUT')
        <h2>Data Diri</h2>

        <div class="flex flex-wrap items-center gap-6">
            <div id="avatarWrap">@include('partials.avatar', ['user' => $user, 'size' => 96])</div>
            <img id="pv" alt="Pratinjau foto" class="hidden shrink-0 rounded-full object-cover" style="width:96px;height:96px">
            <div class="space-y-2">
                <label for="photo" class="btn btn-light cursor-pointer">Pilih foto</label>
                <input id="photo" type="file" name="photo" accept="image/png,image/jpeg,image/webp" class="sr-only">
                @if ($user->photo)
                    <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="remove_photo" value="1"> Hapus foto saat ini</label>
                @endif
                <p class="text-sm text-slate-500">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                <p id="fileName" class="text-sm text-slate-400"></p>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="label" for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required></div>
            <div><label class="label" for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required></div>
            <div><label class="label" for="phone">No. HP</label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input" placeholder="08xxxxxxxxxx"></div>
        </div>
        <button class="btn btn-lg btn-primary">Simpan Perubahan</button>
    </form>

    <div class="space-y-6">
        {{-- Ganti password --}}
        <form method="POST" action="{{ route('karyawan.profil.password') }}" class="card space-y-4">
            @csrf @method('PUT')
            <h2>Keamanan</h2>
            <div><label class="label" for="current_password">Password saat ini</label>
                @include('partials.password-input', ['name' => 'current_password', 'required' => true, 'autocomplete' => 'current-password'])</div>
            <div><label class="label" for="password">Password baru</label>
                @include('partials.password-input', ['name' => 'password', 'required' => true, 'autocomplete' => 'new-password'])</div>
            <div><label class="label" for="password_confirmation">Ulangi password baru</label>
                @include('partials.password-input', ['name' => 'password_confirmation', 'required' => true, 'autocomplete' => 'new-password'])</div>
            <button class="btn btn-light w-full">Ganti Password</button>
        </form>

        {{-- Perangkat terdaftar --}}
        @unless ($user->isAdmin())
        <div class="card">
            <h2 class="mb-4">Perangkat Terdaftar</h2>
            @if ($user->device_name)
                <div class="font-semibold text-ink">{{ $user->device_name }}</div>
                <div class="text-sm text-slate-500">Terdaftar {{ $user->device_bound_at?->translatedFormat('d M Y, H:i') }}</div>
            @else
                <p class="text-sm text-slate-500">Belum ada. Perangkat ini akan terdaftar saat kamu login berikutnya.</p>
            @endif
            <p class="mt-4 text-sm text-slate-400">Akunmu hanya bisa dipakai dari perangkat ini. Ganti HP? Hubungi admin untuk reset perangkat.</p>
        </div>
        @endunless
    </div>
</div>
@endsection
@push('scripts')
<script>
    const photoInput = document.getElementById('photo');
    photoInput.addEventListener('change', () => {
        const f = photoInput.files[0];
        if (!f) return;
        const pv = document.getElementById('pv');
        pv.src = URL.createObjectURL(f);
        pv.classList.remove('hidden');
        document.getElementById('avatarWrap').classList.add('hidden');
        document.getElementById('fileName').textContent = f.name + ' (' + Math.round(f.size / 1024) + ' KB)';
    });
</script>
@endpush
