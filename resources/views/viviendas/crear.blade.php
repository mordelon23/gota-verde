@extends('layouts.app')

@section('titulo', 'Registrar vivienda')

@section('contenido')
@if(session('guia_primer_uso'))
    @include('partials.guia-primer-uso', ['mostrarAlEntrar' => true])
@endif
<div class="gv-registration">
    <section class="gv-card">
        <p class="mb-2 text-xs font-bold uppercase tracking-widest text-[#2E7D6B]">Configuración inicial</p>
        <h1 class="mb-2 text-2xl font-bold text-[#182d2b]">Registrar vivienda</h1>
        <p class="mb-8 text-sm leading-6 text-slate-600">Estos datos identifican tu vivienda. Solo las personas autorizadas podrán consultar sus registros.</p>
        <form method="POST" action="{{ route('viviendas.store') }}" class="grid gap-5 sm:grid-cols-2" data-limpiar-formulario autocomplete="off">
            @csrf
            @include('partials.codigo-postal', ['codigoActual' => null, 'alcaldiaActual' => null])
            <div class="sm:col-span-2">
                <label for="alias" class="mb-2 block text-sm font-semibold">Nombre o alias</label>
                <input id="alias" name="alias" type="text" required maxlength="100" placeholder="Ejemplo: Casa principal" value="{{ old('alias') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                @error('alias') <p class="mt-1 text-xs text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="tipo" class="mb-2 block text-sm font-semibold">Tipo de vivienda</label>
                <select id="tipo" name="tipo" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                    <option value="">Seleccionar</option>
                    <option value="Casa" @selected(old('tipo') === 'Casa')>Casa</option>
                    <option value="Departamento" @selected(old('tipo') === 'Departamento')>Departamento</option>
                    <option value="Otro" @selected(old('tipo') === 'Otro')>Otro</option>
                </select>
            </div>
            <div>
                <label for="habitantes" class="mb-2 block text-sm font-semibold">Número de habitantes</label>
                <input id="habitantes" name="habitantes" type="number" min="1" max="30" required value="{{ old('habitantes', 1) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label for="tarifa_m3" class="mb-2 block text-sm font-semibold">Tarifa por m³ en pesos (opcional)</label>
                <input id="tarifa_m3" name="tarifa_m3" type="number" min="0" max="10000" step="0.01" value="{{ old('tarifa_m3') }}" placeholder="Ejemplo: 20.00" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-[#2E7D6B] focus:outline-none">
                <p class="mt-2 text-xs leading-5 text-slate-500">¿No conoces tu tarifa? <button type="button" class="gv-link" onclick="document.getElementById('tarifa_m3').value='{{ number_format($tarifaSugerida, 2, '.', '') }}'">Usar valor de referencia (${{ number_format($tarifaSugerida, 2) }}/m³)</button>. Puedes cambiarlo después. Los resultados monetarios son orientativos.</p>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="gv-btn w-full">Guardar vivienda</button>
            </div>
        </form>
        <div class="gv-return"><a class="gv-btn alt" href="{{ route('dashboard') }}">Volver al inicio</a></div>
    </section>
    @include('partials.recompensas')
</div>
@endsection
