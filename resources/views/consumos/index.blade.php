@extends('layouts.app')

@section('title', 'Registro de comidas | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>{{ today()->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</h1>
        <h2>Registro de comidas</h2>
        <p class="muted">Cada empleado puede tener un registro de comida por día. El padrón de HLA se consulta sin modificarlo.</p>

        <form id="busqueda-empleado" data-url="{{ route('empleados.buscar') }}">
            <div class="grid">
                <div>
                    <label for="empresa-busqueda">Empresa</label>
                    <select id="empresa-busqueda">
                        <option value="">Todas</option>
                        @foreach ($empresas as $empresa)
                            <option value="{{ $empresa['clave'] }}">{{ $empresa['nombre'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="search-term">
                    <label for="termino-empleado">Buscar o escanear clave</label>
                    <div class="inline">
                        <input id="termino-empleado" type="search" maxlength="100" autocomplete="off" placeholder="Clave o nombre del empleado" autofocus>
                        <button type="submit">Buscar</button>
                    </div>
                </div>
            </div>
        </form>
        <div id="estado-busqueda" class="muted" aria-live="polite"></div>
        <ul id="resultados-empleado" class="results"></ul>

        <div id="empleado-seleccionado" class="selected" hidden></div>
        <form method="post" action="{{ route('consumos.store') }}">
            @csrf
            <input type="hidden" name="empresa" id="empleado-empresa" value="{{ old('empresa') }}">
            <input type="hidden" name="empleado_clave" id="empleado-clave" value="{{ old('empleado_clave') }}">
            <button id="registrar-consumo" type="submit" disabled>Registrar una comida</button>
        </form>
    </section>
    <dialog id="comida-registrada-modal" class="meal-confirmation" aria-labelledby="comida-registrada-titulo">
        <div class="meal-confirmation-content">
            <h2 id="comida-registrada-titulo">Comida registrada</h2>
            <img id="comida-registrada-foto" alt="">
            <p id="comida-registrada-empleado"></p>
            <button id="cerrar-comida-registrada" type="button">Aceptar</button>
        </div>
    </dialog>
@endsection

@push('scripts')
<script>
(() => {
    const searchForm = document.getElementById('busqueda-empleado');
    const searchInput = document.getElementById('termino-empleado');
    const companySelect = document.getElementById('empresa-busqueda');
    const results = document.getElementById('resultados-empleado');
    const searchStatus = document.getElementById('estado-busqueda');
    const selectedPanel = document.getElementById('empleado-seleccionado');
    const selectedCompany = document.getElementById('empleado-empresa');
    const selectedKey = document.getElementById('empleado-clave');
    const recordButton = document.getElementById('registrar-consumo');
    const registrationModal = document.getElementById('comida-registrada-modal');
    const registrationPhoto = document.getElementById('comida-registrada-foto');
    const registrationEmployee = document.getElementById('comida-registrada-empleado');
    const closeRegistrationModal = document.getElementById('cerrar-comida-registrada');
    let qrRegistrationInProgress = false;
    let registrationModalTimeout;

    const clearSelection = () => {
        selectedCompany.value = '';
        selectedKey.value = '';
        selectedPanel.hidden = true;
        recordButton.disabled = true;
    };

    const selectEmployee = (employee) => {
        selectedCompany.value = employee.empresa;
        selectedKey.value = employee.clave;
        const photo = document.createElement('img');
        const details = document.createElement('div');
        const fallback = document.createElement('span');
        photo.alt = `Foto de ${employee.nombre}`;
        photo.loading = 'lazy';
        fallback.className = 'muted';
        fallback.textContent = 'Foto no disponible.';
        photo.addEventListener('error', () => photo.replaceWith(fallback), { once: true });
        photo.src = employee.foto_url;
        details.textContent = `${employee.nombre} — ${employee.empresa_nombre} — ${employee.clave}`;
        selectedPanel.replaceChildren(photo, details);
        selectedPanel.hidden = false;
        recordButton.disabled = false;
        searchStatus.textContent = '';
        results.replaceChildren();
    };

    const normalizeCodePart = (value) => value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleUpperCase('es-MX')
        .replace(/[^A-Z0-9]/g, '');

    const decodeScannedCode = (value) => {
        const sections = value.split('.-');
        if (sections.length !== 3 || sections[0].length <= 6
            || sections[1].length <= 10 || sections[2].length <= 10) {
            return null;
        }

        const employeeKey = sections[0].slice(3, -3);
        const reverseUnwrappedSection = (section) => Array.from(section.slice(5, -5))
            .reverse()
            .join('');
        const employeeName = reverseUnwrappedSection(sections[1]);
        const companyName = reverseUnwrappedSection(sections[2]);

        if (!/^\d+$/.test(employeeKey) || !employeeName || !companyName) {
            return null;
        }

        return { employeeKey, employeeName, companyName };
    };

    const requestEmployees = async (term, company) => {
        const params = new URLSearchParams({ q: term });
        if (company) params.set('empresa', company);
        const response = await fetch(`${searchForm.dataset.url}?${params}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const payload = await response.json().catch(() => null);
        if (!response.ok) {
            throw new Error(payload?.message || 'No fue posible consultar el padrón de HLA.');
        }

        return payload.empleados;
    };

    const registerScannedEmployee = async (employee) => {
        searchStatus.textContent = 'Registrando comida…';
        const response = await fetch('{{ route('consumos.store') }}', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                empresa: employee.empresa,
                empleado_clave: employee.clave,
            }),
        });
        const payload = await response.json().catch(() => null);
        if (!response.ok) {
            const validationMessage = payload?.errors
                ? Object.values(payload.errors).flat()[0]
                : null;
            throw new Error(validationMessage || payload?.message || 'No fue posible registrar la comida.');
        }

        registrationPhoto.alt = `Foto de ${payload.empleado.nombre}`;
        registrationPhoto.hidden = false;
        registrationPhoto.onerror = () => {
            registrationPhoto.hidden = true;
        };
        registrationPhoto.src = payload.empleado.foto_url;
        registrationEmployee.textContent =
            `${payload.empleado.nombre} — ${payload.empleado.empresa_nombre} — ${payload.empleado.clave}`;
        searchInput.value = '';
        registrationModal.showModal();
        clearTimeout(registrationModalTimeout);
        registrationModalTimeout = setTimeout(() => registrationModal.close(), 2000);
        searchStatus.textContent = '';
    };

    const showResults = (employees) => {
        searchStatus.textContent = 'Selecciona el empleado para registrar la comida:';
        employees.forEach((employee) => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            const label = document.createElement('span');
            const detail = document.createElement('small');
            button.type = 'button';
            label.textContent = employee.nombre;
            detail.textContent = `${employee.empresa_nombre} · ${employee.clave}`;
            button.append(label, detail);
            button.addEventListener('click', () => selectEmployee(employee));
            item.append(button);
            results.append(item);
        });
    };

    const searchScannedCode = async (value) => {
        if (qrRegistrationInProgress) return;
        qrRegistrationInProgress = true;
        clearSelection();
        results.replaceChildren();
        const decoded = decodeScannedCode(value);
        if (!decoded) {
            window.showNotification('El código escaneado no tiene un formato válido.', 'error');
            searchStatus.textContent = '';
            qrRegistrationInProgress = false;
            return;
        }

        const normalizedCompany = normalizeCodePart(decoded.companyName);
        const companyOption = Array.from(companySelect.options).find((option) =>
            normalizeCodePart(option.textContent) === normalizedCompany && option.value
        );
        if (!companyOption) {
            window.showNotification('La empresa indicada en el código no está registrada.', 'error');
            searchStatus.textContent = '';
            qrRegistrationInProgress = false;
            return;
        }

        searchStatus.textContent = 'Verificando empleado y empresa…';
        try {
            const employees = await requestEmployees(decoded.employeeKey, companyOption.value);
            const employee = employees.find((candidate) =>
                candidate.clave === decoded.employeeKey
                && normalizeCodePart(candidate.empresa_nombre) === normalizedCompany
                && normalizeCodePart(candidate.nombre).includes(normalizeCodePart(decoded.employeeName))
            );

            if (!employee) {
                window.showNotification('El código no coincide con un empleado activo de esa empresa.', 'error');
                searchStatus.textContent = '';
                return;
            }

            await registerScannedEmployee(employee);
        } catch (error) {
            window.showNotification(error.message, 'error');
            searchStatus.textContent = '';
        } finally {
            qrRegistrationInProgress = false;
        }
    };

    closeRegistrationModal.addEventListener('click', () => registrationModal.close());
    registrationModal.addEventListener('close', () => {
        clearTimeout(registrationModalTimeout);
        searchInput.focus();
    });

    searchInput.addEventListener('input', clearSelection);
    companySelect.addEventListener('change', clearSelection);

    searchInput.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' || event.isComposing || !searchInput.value.includes('.-')) {
            return;
        }

        event.preventDefault();
        searchScannedCode(searchInput.value.trim());
    });

    searchForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearSelection();
        results.replaceChildren();
        const term = searchInput.value.trim();
        if (!term) {
            window.showNotification('Escribe o escanea una clave o nombre.', 'error');
            return;
        }

        searchStatus.textContent = 'Buscando empleado…';
        try {
            const employees = await requestEmployees(term, companySelect.value);
            if (employees.length === 0) {
                window.showNotification('No se encontraron empleados activos.', 'error');
                return;
            }

            showResults(employees);
        } catch (error) {
            window.showNotification(error.message, 'error');
        }
    });
})();
</script>
@endpush
