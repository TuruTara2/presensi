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
<script src="https://cdn.tailwindcss.com"></script>
<script>
    const cv = n => `rgb(var(--c-${n}) / <alpha-value>)`;
    tailwind.config = {
        darkMode: 'class',
        theme: { extend: {
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                heading: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            maxWidth: { page: '1280px' },
            colors: {
                page: cv('page'), card: cv('card'),
                primary: { DEFAULT: cv('primary'), dark: cv('primary-dark'), deep: cv('primary-deep') },
                ink: cv('ink'), body: cv('body'), line: cv('line'),
                success: { DEFAULT: cv('success'), text: cv('success-text') },
                error: { DEFAULT: cv('error'), container: cv('error-container'), on: cv('error-on') },
                surface: { DEFAULT: cv('surface'), low: cv('surface-low'), high: cv('surface-high') },
                // abu-abu netral (menggantikan slate bawaan Tailwind yang kebiruan)
                slate: Object.fromEntries([50,100,200,300,400,500,600,700,800,900].map(n => [n, cv('slate-' + n)])),
            },
        } },
    };
</script>
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
<style type="text/tailwindcss">
    @layer base {
        body { font-family: 'Inter', sans-serif; font-size: 16px; line-height: 1.6; color: rgb(var(--c-body)); background: rgb(var(--c-page)); }
        h1, h2, h3, h4 { font-family: 'Poppins', sans-serif; font-weight: 600; color: rgb(var(--c-ink)); }
        ::placeholder { color: rgb(var(--c-slate-400)); }
    }
    @layer components {
        /* Tipografi */
        .h3 { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 28px; line-height: 1.4; color: rgb(var(--c-ink)); }
        .h4, .card h2 { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 22px; line-height: 1.4; color: rgb(var(--c-ink)); }
        .label { display: block; margin-bottom: 8px; font-size: 14px; font-weight: 600; line-height: 1; letter-spacing: .02em; color: rgb(var(--c-ink)); }
        .caption { font-size: 14px; }

        /* Tombol: 40px (medium), radius 12px */
        .btn, .btn-primary, .btn-light, .btn-secondary, .btn-danger { display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 24px; border-radius: 12px;
               font-size: 14px; font-weight: 600; letter-spacing: .02em; transition: background-color .15s, color .15s; white-space: nowrap; cursor: pointer; }
        .btn-lg { height: 48px; }
        .btn-primary { background: #DC143C; color: #fff; border: 1.5px solid #DC143C; }
        .btn-primary:hover { background: #C61136; border-color: #C61136; }
        .btn-light, .btn-secondary { background: transparent; color: rgb(var(--c-primary)); border: 1.5px solid rgb(var(--c-primary)); }
        .btn-light:hover, .btn-secondary:hover { background: rgb(var(--c-primary) / .08); }
        .btn-danger { background: rgb(var(--c-error-container)); color: rgb(var(--c-error-on)); border: 1.5px solid rgb(var(--c-error-container)); }
        .btn-danger:hover { filter: brightness(.95); }
        .btn:focus-visible, .nav-link:focus-visible, .icon-btn:focus-visible, .pw-toggle:focus-visible { outline: 2px solid rgb(var(--c-primary) / .5); outline-offset: 2px; }

        /* Tombol ikon (ganti tema) & ikon intip password */
        .icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 12px;
                    border: 1.5px solid rgb(var(--c-line)); color: rgb(var(--c-body)); background: transparent; cursor: pointer; transition: background-color .15s; }
        .icon-btn:hover { background: rgb(var(--c-surface-low)); }
        .pw-toggle { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center;
                     width: 40px; height: 40px; border-radius: 8px; color: rgb(var(--c-slate-500)); cursor: pointer; }
        .pw-toggle:hover { color: rgb(var(--c-ink)); }

        /* Input: radius 12px, fokus crimson + glow 2px */
        .input { width: 100%; min-height: 48px; padding: 12px 16px; border-radius: 12px; border: 1px solid rgb(var(--c-input-line)); background: rgb(var(--c-card));
                 font-size: 16px; color: rgb(var(--c-ink)); transition: border-color .15s, box-shadow .15s; }
        textarea.input { min-height: 96px; }
        .input:focus { outline: none; border-color: rgb(var(--c-primary)); box-shadow: 0 0 0 2px rgb(var(--c-primary) / .25); }
        input[type=checkbox] { accent-color: #DC143C; border-radius: 4px; }

        /* Kartu: elevasi level 1 -> level 2 saat hover */
        .card { border-radius: 12px; background: rgb(var(--c-card)); padding: 24px; border: 1px solid rgb(var(--c-line));
                box-shadow: var(--shadow-1); transition: box-shadow .2s; }
        .card:hover { box-shadow: var(--shadow-2); }

        /* Tabel */
        .th { padding: 16px; text-align: left; font-size: 14px; font-weight: 600; letter-spacing: .02em; color: rgb(var(--c-ink)); }
        .td { padding: 16px; font-size: 14px; color: rgb(var(--c-body)); }
        tbody tr:hover { background: rgb(var(--c-surface)); }

        /* Navigasi: link aktif SemiBold + garis crimson 2px di bawah */
        .nav-link { display: inline-block; padding: 16px 0 14px; border-bottom: 2px solid transparent; white-space: nowrap; color: rgb(var(--c-body)); }
        .nav-link:hover { color: rgb(var(--c-ink)); }
        .nav-link-active { font-weight: 600; color: rgb(var(--c-ink)); border-bottom-color: #DC143C; }
    }
</style>
