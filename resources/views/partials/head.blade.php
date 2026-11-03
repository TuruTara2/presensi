{{-- Terapkan tema (terang/gelap) SEBELUM render supaya tidak berkedip --}}
<script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    })();
    function toggleTheme() {
        var dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
        if (document.querySelector('canvas')) location.reload(); // chart ikut ganti warna
    }
    function togglePw(btn) {
        var input = btn.parentElement.querySelector('input');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
        btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
        btn.querySelector('.eye').classList.toggle('hidden', show);
        btn.querySelector('.eye-off').classList.toggle('hidden', !show);
    }
</script>
{{-- Design system: Technolife Corporate (Crimson #DC143C, Poppins + Inter, radius 12px, 8pt grid) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Poppins:wght@600&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
    /* ===== Token warna: terang (default) & gelap ===== */
    :root {
        --c-page: 255 255 255; --c-card: 255 255 255;
        --c-ink: 18 18 18; --c-body: 74 74 74; --c-line: 234 234 234; --c-input-line: 229 229 229;
        --c-surface: 251 249 248; --c-surface-low: 245 243 243; --c-surface-high: 234 232 231;
        --c-primary: 220 20 60; --c-primary-dark: 198 17 54; --c-primary-deep: 177 0 44;
        --c-success: 40 167 69; --c-success-text: 30 126 52;
        --c-error: 186 26 26; --c-error-container: 255 218 214; --c-error-on: 147 0 10;
        --c-slate-50: 251 249 248; --c-slate-100: 245 243 243; --c-slate-200: 234 234 234; --c-slate-300: 229 229 229;
        --c-slate-400: 154 154 154; --c-slate-500: 107 107 107; --c-slate-600: 74 74 74; --c-slate-700: 51 51 51;
        --c-slate-800: 27 28 28; --c-slate-900: 18 18 18;
        --shadow-1: 0px 4px 12px rgba(0, 0, 0, .05); --shadow-2: 0px 8px 24px rgba(0, 0, 0, .08);
    }
    :root.dark {
        color-scheme: dark;
        --c-page: 18 18 18; --c-card: 30 30 30;
        --c-ink: 245 245 245; --c-body: 190 190 190; --c-line: 52 52 52; --c-input-line: 64 64 64;
        --c-surface: 24 24 24; --c-surface-low: 38 38 38; --c-surface-high: 48 48 48;
        --c-primary: 255 77 109; --c-primary-dark: 255 107 133; --c-primary-deep: 255 140 160;
        --c-success: 52 199 89; --c-success-text: 102 223 117;
        --c-error: 255 120 110; --c-error-container: 80 20 22; --c-error-on: 255 180 175;
        --c-slate-50: 36 36 36; --c-slate-100: 42 42 42; --c-slate-200: 52 52 52; --c-slate-300: 64 64 64;
        --c-slate-400: 130 130 130; --c-slate-500: 160 160 160; --c-slate-600: 190 190 190; --c-slate-700: 215 215 215;
        --c-slate-800: 235 235 235; --c-slate-900: 245 245 245;
        --shadow-1: 0px 4px 12px rgba(0, 0, 0, .35); --shadow-2: 0px 8px 24px rgba(0, 0, 0, .5);
    }
</style>

