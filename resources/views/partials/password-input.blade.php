{{-- Pemakaian: @include('partials.password-input', ['name' => 'password', 'required' => true, 'autocomplete' => 'current-password']) --}}
<div class="relative">
    <input id="{{ $id ?? $name }}" type="password" name="{{ $name }}" class="input pr-12"
           @if (!empty($required)) required @endif
           @if (!empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif>
    <button type="button" onclick="togglePw(this)" class="pw-toggle" aria-label="Tampilkan password" aria-pressed="false">
        <svg class="eye" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
    </button>
</div>
