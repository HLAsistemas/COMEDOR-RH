@extends('layouts.app')

@section('title', 'Menú del día | COMEDOR-RH')

@section('content')
    <section class="card">
        <h1>Menú del día</h1>
        <p class="muted">{{ \Illuminate\Support\Carbon::parse($fecha)->locale('es')->translatedFormat('l, j \d\e F \d\e Y') }}</p>

        <form method="post" action="{{ route('menu-dia.store') }}">
            @csrf
            <h2>Platillos incluidos en el menú</h2>
            <div id="menu-dish-list" class="dish-list">
                @foreach (old('platillos', [['nombre' => '', 'descripcion' => '']]) as $index => $platillo)
                    <div class="dish-entry">
                        <div>
                            <label for="dish-name-{{ $index }}">Nombre del platillo</label>
                            <input id="dish-name-{{ $index }}" name="platillos[{{ $index }}][nombre]" value="{{ $platillo['nombre'] ?? '' }}" maxlength="150" required>
                        </div>
                        <div>
                            <label for="dish-description-{{ $index }}">Descripción</label>
                            <textarea id="dish-description-{{ $index }}" name="platillos[{{ $index }}][descripcion]" maxlength="2000" required>{{ $platillo['descripcion'] ?? '' }}</textarea>
                        </div>
                        <button class="secondary remove-dish" type="button">Quitar</button>
                        @error("platillos.$index.nombre")
                            <p class="error">{{ $message }}</p>
                        @enderror
                        @error("platillos.$index.descripcion")
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
            @error('platillos')
                <p class="error">{{ $message }}</p>
            @enderror
            <div class="inline" style="margin-top: 1rem">
                <button class="secondary" id="add-menu-dish" type="button">Agregar otro platillo</button>
                <button type="submit">Guardar menú del día</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Platillos extra</h2>
        <p class="muted">Registra aquí los platillos que se ofrecen aparte y no forman parte del menú del día.</p>
        <form method="post" action="{{ route('menu-dia.extras.store') }}">
            @csrf
            <div id="extra-dish-list" class="dish-list">
                @foreach (old('extras', [['nombre' => '', 'descripcion' => '']]) as $index => $platillo)
                    <div class="dish-entry">
                        <div>
                            <label for="extra-name-{{ $index }}">Nombre del platillo extra</label>
                            <input id="extra-name-{{ $index }}" name="extras[{{ $index }}][nombre]" value="{{ $platillo['nombre'] ?? '' }}" maxlength="150" required>
                        </div>
                        <div>
                            <label for="extra-description-{{ $index }}">Descripción</label>
                            <textarea id="extra-description-{{ $index }}" name="extras[{{ $index }}][descripcion]" maxlength="2000" required>{{ $platillo['descripcion'] ?? '' }}</textarea>
                        </div>
                        <button class="secondary remove-dish" type="button">Quitar</button>
                        @error("extras.$index.nombre")
                            <p class="error">{{ $message }}</p>
                        @enderror
                        @error("extras.$index.descripcion")
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
            @error('extras')
                <p class="error">{{ $message }}</p>
            @enderror
            <div class="inline" style="margin-top: 1rem">
                <button class="secondary" id="add-extra-dish" type="button">Agregar otro extra</button>
                <button type="submit">Guardar platillos extra</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Platillos capturados para hoy</h2>
        @if ($platillos->isEmpty())
            <p class="muted">Aún no hay platillos registrados para hoy.</p>
        @else
            <div class="dish-list">
                @foreach ($platillos as $platillo)
                    <article class="dish-entry">
                        <div>
                            <strong>{{ $platillo->nombre }}</strong>
                        </div>
                        <p style="margin: 0">{{ $platillo->descripcion }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <h2>Platillos extra capturados para hoy</h2>
        @if ($extras->isEmpty())
            <p class="muted">Aún no hay platillos extra registrados para hoy.</p>
        @else
            <div class="dish-list">
                @foreach ($extras as $extra)
                    <article class="dish-entry">
                        <div>
                            <strong>{{ $extra->nombre }}</strong>
                        </div>
                        <p style="margin: 0">{{ $extra->descripcion }}</p>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @push('scripts')
        <script>
            (() => {
                const initializeDishList = (listId, addButtonId, fieldName, labelSuffix) => {
                    const list = document.getElementById(listId);
                    const addButton = document.getElementById(addButtonId);
                    let nextIndex = list.children.length;
                    while (list.querySelector(`[name="${fieldName}[${nextIndex}][nombre]"]`)) {
                        nextIndex++;
                    }

                    addButton.addEventListener('click', () => {
                        const entry = document.createElement('div');
                        entry.className = 'dish-entry';

                        const nameField = document.createElement('div');
                        const nameLabel = document.createElement('label');
                        const nameInput = document.createElement('input');
                        nameLabel.htmlFor = `${fieldName}-name-${nextIndex}`;
                        nameLabel.textContent = `Nombre del platillo ${labelSuffix}`;
                        nameInput.id = nameLabel.htmlFor;
                        nameInput.name = `${fieldName}[${nextIndex}][nombre]`;
                        nameInput.maxLength = 150;
                        nameInput.required = true;
                        nameField.append(nameLabel, nameInput);

                        const descriptionField = document.createElement('div');
                        const descriptionLabel = document.createElement('label');
                        const descriptionInput = document.createElement('textarea');
                        descriptionLabel.htmlFor = `${fieldName}-description-${nextIndex}`;
                        descriptionLabel.textContent = 'Descripción';
                        descriptionInput.id = descriptionLabel.htmlFor;
                        descriptionInput.name = `${fieldName}[${nextIndex}][descripcion]`;
                        descriptionInput.maxLength = 2000;
                        descriptionInput.required = true;
                        descriptionField.append(descriptionLabel, descriptionInput);

                        const removeButton = document.createElement('button');
                        removeButton.className = 'secondary remove-dish';
                        removeButton.type = 'button';
                        removeButton.textContent = 'Quitar';
                        entry.append(nameField, descriptionField, removeButton);
                        list.append(entry);
                        nextIndex++;
                        nameInput.focus();
                    });

                    list.addEventListener('click', (event) => {
                        if (event.target.matches('.remove-dish') && list.children.length > 1) {
                            event.target.closest('.dish-entry').remove();
                        }
                    });
                };

                initializeDishList(
                    'menu-dish-list',
                    'add-menu-dish',
                    'platillos',
                    'del menú'
                );
                initializeDishList(
                    'extra-dish-list',
                    'add-extra-dish',
                    'extras',
                    'extra'
                );
            })();
        </script>
    @endpush
@endsection
