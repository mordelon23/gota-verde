@php
    [$symbol, $tone] = match ($badgeKey) {
        'primer_registro' => ['drop', 'sage'],
        'registros_10' => ['list', 'olive'],
        'registros_50', 'racha_7' => ['leaf', 'sage'],
        'racha_3' => ['flame', 'gold'],
        'racha_30' => ['trophy', 'gold'],
        'ahorro_100' => ['bucket', 'olive'],
        'ahorro_1000' => ['tank', 'sage'],
        'meta_1' => ['target', 'olive'],
        'meta_5' => ['compass', 'gold'],
        'gota_bronce' => ['award', 'bronze'],
        'gota_plata' => ['award', 'silver'],
        'gota_oro' => ['award', 'gold'],
        'guardian_agua' => ['shield', 'olive'],
        default => ['award', 'olive'],
    };
@endphp
<span class="gv-badge-icon gv-badge-{{ $tone }}" aria-hidden="true">
    @include('partials.icon', ['icon' => $symbol])
</span>
