{{-- Pemakaian: @include('partials.avatar', ['user' => $user, 'size' => 40]) --}}
@php
    $size = $size ?? 40;
    $initials = collect(preg_split('/\s+/', trim($user->name)))->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
@endphp
@if ($user->photo)
    <img src="{{ Storage::url($user->photo) }}?v={{ $user->updated_at?->timestamp }}" alt="Foto {{ $user->name }}"
         class="shrink-0 rounded-full object-cover" style="width:{{ $size }}px;height:{{ $size }}px">
@else
    <span class="inline-flex shrink-0 items-center justify-center rounded-full bg-primary/10 font-heading font-semibold text-primary"
          style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * 0.38) }}px">{{ $initials }}</span>
@endif
