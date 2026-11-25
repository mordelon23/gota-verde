{{ match ($activityKey ?? null) {
    'ducha' => 'shower',
    'lavado_trastes' => 'dishes',
    'cepillado_dientes' => 'toothbrush',
    'lavado_manos' => 'faucet',
    'riego', 'riego_manguera' => 'hose',
    'riego_plantas' => 'watering',
    'lavadora', 'lavado_ropa' => 'washer',
    'inodoro', 'uso_inodoro' => 'toilet',
    'lavado_auto', 'lavado_automovil' => 'car',
    'limpieza', 'limpieza_domestica' => 'bucket',
    'tina', 'bano_tina' => 'bath',
    default => 'drop',
} }}