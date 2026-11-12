<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pantalla de comedor | COMEDOR-RH</title>
    <style>
        :root { font-family: Inter, "Segoe UI", sans-serif; color: #18312c; background: #f3f7f5; }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body { display: grid; grid-template-rows: auto 1fr; background: #f3f7f5; transition: background .25s ease; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding: 1rem 2rem; background: #165b49; color: white; }
        header img { display: block; height: 90px; width: auto; }
        #reloj { font-size: clamp(1.4rem, 3vw, 2.6rem); font-weight: 700; text-align: right; }
        #fecha { font-size: clamp(1rem, 1.6vw, 1.4rem); opacity: .9; text-align: right; }
        main { display: grid; place-items: center; padding: 2rem; text-align: center; }
        .estado { display: grid; justify-items: center; gap: 1.25rem; }
        .estado[hidden] { display: none; }
        h1 { margin: 0; font-size: clamp(2.5rem, 7vw, 6rem); line-height: 1.05; }
        p { margin: 0; font-size: clamp(1.3rem, 3vw, 2.6rem); }
        .icono { display: grid; place-items: center; width: clamp(110px, 14vw, 190px); aspect-ratio: 1; border-radius: 50%; color: white; }
        .icono svg { width: 62%; height: 62%; }
        #foto { width: clamp(180px, 22vw, 320px); aspect-ratio: 4 / 5; object-fit: cover; border-radius: 16px; border: 6px solid white; box-shadow: 0 16px 40px #0003; background: #dce8e2; }
        #foto[hidden] { display: none; }
        .detalle { color: #436157; font-size: clamp(1.1rem, 2.2vw, 2rem); }
        body.exito { background: #e3f4ea; }
        body.exito .icono { background: #1f8a55; }
        body.exito h1 { color: #175a37; }
        body.error { background: #fde8e5; }
        body.error .icono { background: #c0392b; }
        body.error h1 { color: #9c3028; }
        #pantalla-completa { position: fixed; right: 1rem; bottom: 1rem; border: 0; border-radius: 8px; padding: .6rem .9rem; background: #165b49; color: white; font: inherit; font-weight: 700; cursor: pointer; opacity: .85; }
        #aviso-compatibilidad { color: #9c3028; }
    </style>
</head>
<body>
    <header>
        <img src="{{ asset('images/logo-comedor.png') }}" alt="Comedor Hilaturas Los Ángeles">
        <div>
            <div id="reloj"></div>
            <div id="fecha"></div>
        </div>
    </header>
    <main>
        <section id="estado-espera" class="estado">
            <h1>¡Bienvenido!</h1>
            <p>Presenta tu gafete para registrar tu comida.</p>
            <p id="aviso-compatibilidad" hidden>Este navegador no permite conectar la pantalla con el registro.</p>
        </section>
        <section id="estado-resultado" class="estado" hidden aria-live="assertive">
            <img id="foto" alt="">
            <div class="icono" id="icono"></div>
            <h1 id="titulo"></h1>
            <p id="mensaje"></p>
            <div class="detalle" id="detalle"></div>
        </section>
    </main>
    <button id="pantalla-completa" type="button">Pantalla completa</button>
    <script>
    (() => {
        const DURACION_MS = 4000;
        const espera = document.getElementById('estado-espera');
        const resultado = document.getElementById('estado-resultado');
        const foto = document.getElementById('foto');
        const icono = document.getElementById('icono');
        const titulo = document.getElementById('titulo');
        const mensaje = document.getElementById('mensaje');
        const detalle = document.getElementById('detalle');
        const reloj = document.getElementById('reloj');
        const fecha = document.getElementById('fecha');
        const botonPantallaCompleta = document.getElementById('pantalla-completa');
        const iconoExito = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>';
        const iconoError = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M7 7l10 10M17 7L7 17"/></svg>';
        let temporizador;

        const actualizarReloj = () => {
            const ahora = new Date();
            reloj.textContent = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
            fecha.textContent = ahora.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        };

        const mostrarEspera = () => {
            document.body.classList.remove('exito', 'error');
            resultado.hidden = true;
            espera.hidden = false;
        };

        const mostrarResultado = (datos) => {
            clearTimeout(temporizador);
            const exito = datos.tipo === 'exito';
            document.body.classList.toggle('exito', exito);
            document.body.classList.toggle('error', !exito);
            icono.innerHTML = exito ? iconoExito : iconoError;
            titulo.textContent = exito ? '¡Comida registrada!' : 'No se pudo registrar';
            mensaje.textContent = exito ? '¡Buen provecho!' : (datos.mensaje || 'Acude con el encargado del comedor.');

            const empleado = datos.empleado;
            detalle.textContent = empleado
                ? [empleado.nombre, empleado.empresa_nombre, empleado.clave].filter(Boolean).join(' — ')
                : '';

            foto.hidden = true;
            icono.hidden = false;
            if (empleado?.foto_url) {
                foto.alt = `Foto de ${empleado.nombre}`;
                foto.onload = () => {
                    foto.hidden = false;
                    icono.hidden = true;
                };
                foto.onerror = () => {
                    foto.hidden = true;
                    icono.hidden = false;
                };
                foto.src = empleado.foto_url;
            }

            espera.hidden = true;
            resultado.hidden = false;
            temporizador = setTimeout(mostrarEspera, DURACION_MS);
        };

        botonPantallaCompleta.addEventListener('click', () => {
            document.documentElement.requestFullscreen?.().catch(() => {});
        });
        document.addEventListener('fullscreenchange', () => {
            botonPantallaCompleta.hidden = Boolean(document.fullscreenElement);
        });

        actualizarReloj();
        setInterval(actualizarReloj, 15000);

        if (!('BroadcastChannel' in window)) {
            document.getElementById('aviso-compatibilidad').hidden = false;
            return;
        }

        const canal = new BroadcastChannel('comedor-rh-pantalla-cliente');
        canal.addEventListener('message', (evento) => {
            if (evento.data && typeof evento.data === 'object') mostrarResultado(evento.data);
        });
    })();
    </script>
</body>
</html>
