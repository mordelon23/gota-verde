<?php

use App\Http\Controllers\Admin\ActividadController as AdminActividadController;
use App\Http\Controllers\Admin\LogroController as AdminLogroController;
use App\Http\Controllers\Admin\UsuarioController as AdminUsuarioController;
use App\Http\Controllers\Auth\RecuperarContrasenaController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Controllers\Auth\SesionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitacionController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\LogroController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\MiembroController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RegistroHabitoController;
use App\Http\Controllers\ViviendaController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('inicio');

Route::middleware('guest')->group(function (): void {
    Route::get('/registro', [RegistroController::class, 'create'])->name('register');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/ingresar', [SesionController::class, 'create'])->name('login');
    // El bloqueo tras 5 intentos fallidos se aplica dentro del controlador (CU-02 4b).
    Route::post('/ingresar', [SesionController::class, 'store'])->name('login.store');

    // RF-03: recuperación de contraseña.
    Route::get('/recuperar', [RecuperarContrasenaController::class, 'solicitar'])->name('password.request');
    Route::post('/recuperar', [RecuperarContrasenaController::class, 'enviarEnlace'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer/{token}', [RecuperarContrasenaController::class, 'formulario'])->name('password.reset');
    Route::post('/restablecer', [RecuperarContrasenaController::class, 'restablecer'])->middleware('throttle:5,1')->name('password.update');
});

// Página pública de la invitación: si no hay sesión, pide iniciarla o crear cuenta.
Route::get('/invitaciones/{token}', [InvitacionController::class, 'mostrar'])->name('invitaciones.mostrar');

Route::middleware('auth')->group(function (): void {
    Route::post('/salir', [SesionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/invitaciones', [InvitacionController::class, 'recibidas'])->name('invitaciones.recibidas');
    Route::post('/invitaciones/enlace', [InvitacionController::class, 'abrirEnlace'])->name('invitaciones.enlace');
    Route::post('/invitaciones/recibidas/{invitacion}/aceptar', [InvitacionController::class, 'aceptarRecibida'])
        ->whereNumber('invitacion')->name('invitaciones.recibidas.aceptar');
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::patch('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
    Route::get('/perfil/foto', [PerfilController::class, 'foto'])->name('perfil.foto');
    Route::delete('/perfil/foto', [PerfilController::class, 'eliminarFoto'])->name('perfil.foto.destroy');

    // Viviendas (RF-14, RF-15, RF-16).
    Route::get('/viviendas', [ViviendaController::class, 'index'])->name('viviendas.index');
    Route::get('/viviendas/crear', [ViviendaController::class, 'create'])->name('viviendas.create');
    Route::post('/viviendas', [ViviendaController::class, 'store'])->name('viviendas.store');
    Route::get('/viviendas/{vivienda}/editar', [ViviendaController::class, 'edit'])->name('viviendas.edit');
    Route::put('/viviendas/{vivienda}', [ViviendaController::class, 'update'])->name('viviendas.update');
    Route::delete('/viviendas/{vivienda}', [ViviendaController::class, 'destroy'])->name('viviendas.destroy');
    Route::post('/viviendas/{vivienda}/seleccionar', [ViviendaController::class, 'seleccionar'])->name('viviendas.seleccionar');

    // Miembros e invitaciones (RF-20, RF-22).
    Route::post('/viviendas/{vivienda}/invitaciones', [MiembroController::class, 'invitar'])->middleware('throttle:10,1')->name('viviendas.invitar');
    Route::delete('/viviendas/{vivienda}/invitaciones/{invitacion}', [MiembroController::class, 'cancelarInvitacion'])->name('viviendas.invitaciones.cancelar');
    Route::delete('/viviendas/{vivienda}/miembros/{usuario}', [MiembroController::class, 'eliminar'])->name('viviendas.miembros.eliminar');
    Route::post('/viviendas/{vivienda}/salir', [MiembroController::class, 'salir'])->name('viviendas.salir');
    Route::post('/invitaciones/{token}/aceptar', [InvitacionController::class, 'aceptar'])->name('invitaciones.aceptar');

    // Hábitos (RF-07, RF-21).
    Route::get('/habitos/crear', [RegistroHabitoController::class, 'create'])->name('habitos.create');
    Route::post('/habitos', [RegistroHabitoController::class, 'store'])->name('habitos.store');

    // Lecturas de consumo (RF-06, RF-17).
    Route::get('/lecturas', [LecturaController::class, 'index'])->name('lecturas.index');
    Route::post('/lecturas', [LecturaController::class, 'store'])->name('lecturas.store');
    Route::get('/lecturas/{lectura}/editar', [LecturaController::class, 'edit'])->name('lecturas.edit');
    Route::put('/lecturas/{lectura}', [LecturaController::class, 'update'])->name('lecturas.update');
    Route::delete('/lecturas/{lectura}', [LecturaController::class, 'destroy'])->name('lecturas.destroy');

    // Metas (RF-08, RF-18).
    Route::get('/metas', [MetaController::class, 'index'])->name('metas.index');
    Route::post('/metas', [MetaController::class, 'store'])->name('metas.store');
    Route::delete('/metas/{meta}', [MetaController::class, 'destroy'])->name('metas.destroy');

    // Logros, rachas y recompensas (RF-09, RF-10, RF-11).
    Route::get('/logros', [LogroController::class, 'index'])->name('logros.index');

    // Administración del sistema (RF-04, CU-04, CU-16).
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/usuarios', [AdminUsuarioController::class, 'index'])->name('usuarios.index');
        Route::patch('/usuarios/{usuario}/rol', [AdminUsuarioController::class, 'actualizarRol'])->name('usuarios.rol');

        Route::get('/actividades', [AdminActividadController::class, 'index'])->name('actividades.index');
        Route::post('/actividades', [AdminActividadController::class, 'store'])->name('actividades.store');
        Route::get('/actividades/{actividad}/editar', [AdminActividadController::class, 'edit'])->name('actividades.edit');
        Route::put('/actividades/{actividad}', [AdminActividadController::class, 'update'])->name('actividades.update');
        Route::patch('/actividades/{actividad}/alternar', [AdminActividadController::class, 'alternar'])->name('actividades.alternar');

        Route::get('/logros', [AdminLogroController::class, 'index'])->name('logros.index');
        Route::post('/logros', [AdminLogroController::class, 'store'])->name('logros.store');
        Route::get('/logros/{logro}/editar', [AdminLogroController::class, 'edit'])->name('logros.edit');
        Route::put('/logros/{logro}', [AdminLogroController::class, 'update'])->name('logros.update');
    });
});
