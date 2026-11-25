@extends('layouts.app')

@section('titulo', 'Registrar vivienda')

@section('contenido')
@if(session('guia_primer_uso'))
    @include('partials.guia-primer-uso', ['mostrarAlEntrar' => true])
@endif
<div class="gv-registration gv-house-registration">
    <section class="gv-card gv-form-panel">
        <h1 class="mb-2 text-2xl font-bold text-[#182d2b]">Registrar vivienda</h1>
        <p class="mb-8 text-sm leading-6 text-slate-600">Estos datos identifican tu vivienda. Solo las personas autorizadas podrán consultar sus registros.</p>
        <form method="POST" action="{{ route('viviendas.store') }}" class="gv-house-editor" enctype="multipart/form-data" data-limpiar-formulario autocomplete="off">
            @csrf
            @include('partials.home-photo-field', ['hogar' => null])
            <div class="gv-house-fields">
            @include('partials.codigo-postal', ['codigoActual' => null, 'alcaldiaActual' => null])
            <div class="gv-field wide">
                <label for="alias">Nombre o alias</label>
                <input id="alias" name="alias" type="text" required maxlength="100" placeholder="Ejemplo: Casa principal" value="{{ old('alias') }}">
                @error('alias') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="gv-field">
                <label for="tipo">Tipo de vivienda</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Seleccionar</option>
                    <option value="Casa" @selected(old('tipo') === 'Casa')>Casa</option>
                    <option value="Departamento" @selected(old('tipo') === 'Departamento')>Departamento</option>
                    <option value="Otro" @selected(old('tipo') === 'Otro')>Otro</option>
                </select>
            </div>
            <div class="gv-field">
                <label for="habitantes">Número de habitantes</label>
                <input id="habitantes" name="habitantes" type="number" min="1" max="30" required value="{{ old('habitantes', 1) }}">
            </div>
            <div class="gv-field wide">
                <label for="tarifa_m3">Tarifa por m³ en pesos (opcional)</label>
                <input id="tarifa_m3" name="tarifa_m3" type="number" min="0" max="10000" step="0.01" value="{{ old('tarifa_m3') }}" placeholder="Ejemplo: 20.00">
                <p class="mt-2 text-xs leading-5 text-slate-500">Puedes cambiarlo después. Los resultados monetarios son orientativos.</p>
                <div class="gv-actions"><button type="button" class="gv-link" onclick="document.getElementById('tarifa_m3').value='{{ number_format($tarifaSugerida, 2, '.', '') }}'">Usar valor de referencia (${{ number_format($tarifaSugerida, 2) }}/m³)</button></div>
            </div>
            <div class="wide gv-actions gv-actions-between">
                <a class="gv-btn alt" href="{{ route('dashboard') }}">Volver al inicio</a>
                <button type="submit" class="gv-btn gv-right">Guardar vivienda</button>
            </div>
            </div>
        </form>
    </section>
    @include('partials.recompensas')
</div>
@endsection
