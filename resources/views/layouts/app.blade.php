<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'COMEDOR-RH')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { font-family: Inter, "Segoe UI", sans-serif; color: #18312c; background: #f3f7f5; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        header { background: #165b49; color: white; padding: 1rem max(1rem, calc((100vw - 1120px) / 2)); display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        header .brand { display: inline-flex; align-items: center; color: white; text-decoration: none; }
        header .brand img { display: block; width: auto; height: 80px; }
        .header-nav { display: flex; align-items: center; flex-wrap: wrap; gap: .8rem; }
        .header-nav a { color: white; font-size: .9rem; text-decoration: none; }
        .header-nav a:hover, .header-nav a:focus-visible { text-decoration: underline; }
        header form { margin: 0; }
        main { max-width: 1120px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: white; border: 1px solid #dce8e2; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 8px 24px #18312c0b; }
        h1, h2 { margin: 0 0 .75rem; }
        h1 { font-size: 1.6rem; }
        h2 { font-size: 1.1rem; }
        p { line-height: 1.5; }
        label { display: block; font-weight: 650; margin-bottom: .35rem; }
        input, select, button { font: inherit; }
        input, select { min-height: 42px; width: 100%; border: 1px solid #aebfb7; border-radius: 8px; padding: .55rem .7rem; background: white; }
        button, .button { display: inline-flex; align-items: center; justify-content: center; min-height: 40px; border: 0; border-radius: 8px; padding: .6rem .9rem; background: #165b49; color: white; font-weight: 700; cursor: pointer; text-decoration: none; }
        button.secondary { background: #e5efea; color: #174b3e; }
        button:disabled { cursor: not-allowed; opacity: .55; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); align-items: end; gap: .9rem; }
        .search-term { grid-column: span 2; }
        .inline { display: flex; gap: .6rem; align-items: end; }
        .inline > :first-child { flex: 1; }
        .muted { color: #60736b; }
        .notice { border-radius: 8px; padding: .8rem 1rem; margin: 0 0 1rem; background: #e8f5ed; color: #175a37; }
        .error { border-radius: 8px; padding: .8rem 1rem; margin: .5rem 0; background: #fff0ee; color: #9c3028; }
        .results { list-style: none; padding: 0; margin: .6rem 0; display: grid; gap: .45rem; }
        .results button { width: 100%; justify-content: flex-start; text-align: left; background: #f0f6f2; color: #18312c; border: 1px solid #dce8e2; }
        .results button span { display: block; }
        .results small { display: block; color: #60736b; font-weight: 400; margin-top: .15rem; }
        .selected { display: flex; align-items: center; gap: 1rem; padding: .75rem; margin: .75rem 0; border-radius: 8px; background: #e8f5ed; }
        .selected img { width: 88px; height: 112px; border-radius: 6px; object-fit: cover; background: #dce8e2; }
        .meal-confirmation { width: min(92vw, 420px); border: 0; border-radius: 14px; padding: 0; box-shadow: 0 18px 60px #0005; }
        .meal-confirmation::backdrop { background: #10231db3; }
        .meal-confirmation-content { display: grid; justify-items: center; gap: .75rem; padding: 1.5rem; text-align: center; }
        .meal-confirmation-content h2, .meal-confirmation-content p { margin: 0; }
        .meal-confirmation-content img { width: 140px; height: 176px; border-radius: 8px; object-fit: cover; background: #dce8e2; }
        .notification-modal { width: min(92vw, 420px); border: 0; border-radius: 14px; padding: 0; box-shadow: 0 18px 60px #0005; }
        .notification-modal::backdrop { background: #10231db3; }
        .notification-modal-content { display: grid; justify-items: center; gap: .75rem; padding: 1.5rem; text-align: center; }
        .notification-modal-content h2, .notification-modal-content p { margin: 0; }
        .notification-modal.is-error h2 { color: #9c3028; }
        .notification-modal.is-success h2 { color: #175a37; }
        .summary { display: inline-flex; gap: .5rem; align-items: baseline; border-radius: 9px; background: #e8f5ed; padding: .7rem 1rem; margin: .4rem 0 1rem; }
        .summary strong { font-size: 1.4rem; }
        .table-wrap { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border-bottom: 1px solid #e5ece8; padding: .7rem; text-align: left; white-space: nowrap; }
        th { color: #436157; background: #f5f8f6; font-size: .82rem; text-transform: uppercase; }
        .pagination { margin-top: 1rem; }
        .login { max-width: 430px; margin: 4rem auto; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .menu-card { display: flex; min-height: 210px; flex-direction: column; align-items: flex-start; gap: .75rem; padding: 1.4rem; border: 1px solid #dce8e2; border-radius: 14px; background: white; color: #18312c; text-decoration: none; box-shadow: 0 8px 24px #18312c0b; transition: transform .15s ease, box-shadow .15s ease; }
        .menu-card:hover, .menu-card:focus-visible { transform: translateY(-3px); box-shadow: 0 14px 30px #18312c18; outline: 2px solid #61a88c; outline-offset: 2px; }
        .menu-icon { display: grid; width: 54px; height: 54px; place-items: center; border-radius: 13px; background: #e8f5ed; color: #165b49; }
        .menu-icon svg { width: 30px; height: 30px; }
        .menu-card strong { font-size: 1.15rem; }
        .menu-card p { margin: 0; color: #60736b; }
        .menu-card .menu-open { margin-top: auto; color: #165b49; font-weight: 700; }
        @media (max-width: 600px) {
            main { margin-top: 1rem; }
            .card { padding: 1rem; }
            .inline { align-items: stretch; flex-direction: column; }
            .inline button { width: 100%; }
            .search-term { grid-column: auto; }
            header { padding: .5rem 1rem; }
            header .brand img { height: 64px; }
        }
    </style>
</head>
<body>
    <header>
        <a class="brand" href="@auth{{ route('menu') }}@else{{ route('login') }}@endauth">
            <img src="{{ asset('images/logo-comedor.png') }}" alt="Comedor Hilaturas Los Ángeles">
        </a>
        @auth
            <div class="header-nav">
                <a href="{{ route('menu') }}">Menú</a>
                <a href="{{ route('consumos.index') }}">Registro</a>
                <a href="{{ route('reportes.index') }}">Reporte</a>
                <span>{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button class="secondary" type="submit">Cerrar sesión</button>
                </form>
            </div>
        @endauth
    </header>
    <main>
        @yield('content')
    </main>
    <dialog id="notification-modal" class="notification-modal" aria-labelledby="notification-title" aria-describedby="notification-message">
        <div class="notification-modal-content">
            <h2 id="notification-title"></h2>
            <p id="notification-message"></p>
        </div>
    </dialog>
    <script>
        (() => {
            const modal = document.getElementById('notification-modal');
            const title = document.getElementById('notification-title');
            const message = document.getElementById('notification-message');
            const searchInput = document.getElementById('termino-empleado');
            let closeTimer;
            let audioContext;

            const prepareErrorAudio = () => {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                audioContext ??= new AudioContext();
                if (audioContext.state === 'suspended') {
                    audioContext.resume().catch((error) => console.error('No se pudo activar el audio de alerta.', error));
                }
            };

            const playErrorSound = () => {
                if (!audioContext) prepareErrorAudio();
                if (!audioContext) return;
                audioContext.resume().then(() => {
                    const oscillator = audioContext.createOscillator();
                    const gain = audioContext.createGain();
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(660, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(440, audioContext.currentTime + 0.14);
                    gain.gain.setValueAtTime(0.18, audioContext.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.28);
                    oscillator.connect(gain);
                    gain.connect(audioContext.destination);
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.28);
                }).catch((error) => console.error('No se pudo reproducir el audio de alerta.', error));
            };

            document.addEventListener('pointerdown', prepareErrorAudio, { passive: true });
            document.addEventListener('keydown', prepareErrorAudio);

            window.showNotification = (text, type = 'error') => {
                clearTimeout(closeTimer);
                if (modal.open) modal.close();
                if (searchInput) searchInput.value = '';
                modal.classList.toggle('is-error', type === 'error');
                modal.classList.toggle('is-success', type === 'success');
                title.textContent = type === 'error' ? 'Error' : 'Aviso';
                message.textContent = text;
                modal.showModal();
                if (type === 'error') playErrorSound();
                closeTimer = setTimeout(() => modal.close(), 2000);
            };

            modal.addEventListener('close', () => clearTimeout(closeTimer));

            @if (session('status'))
                window.showNotification(@json(session('status')), 'success');
            @endif
            @if ($errors->any())
                window.showNotification(@json(implode(' ', $errors->all())), 'error');
            @endif
        })();
    </script>
    @stack('scripts')
</body>
</html>
