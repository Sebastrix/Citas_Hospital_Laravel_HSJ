<?php

use App\Http\Controllers\Appointments\AppointmentCatalogController;
use App\Http\Controllers\Appointments\AppointmentChatbotController;
use App\Http\Controllers\Appointments\HealthController;
use App\Http\Controllers\Appointments\PublicAppointmentController;
use App\Http\Controllers\Appointments\WaitingRoomController;
use App\Http\Controllers\Tools\RemoteQueryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicAppointmentController::class, 'index'])->name('appointments.public.index');
Route::get('/principal', [PublicAppointmentController::class, 'index']);
Route::get('/principal.php', [PublicAppointmentController::class, 'index']);
Route::get('/sala-espera', [PublicAppointmentController::class, 'waitingRoom'])->name('appointments.public.waiting-room');
Route::get('/sala-espera.php', [PublicAppointmentController::class, 'waitingRoom']);
Route::match(['get', 'post'], '/consulta-remota', RemoteQueryController::class)->name('tools.remote-query');
Route::match(['get', 'post'], '/consulta-remota.php', RemoteQueryController::class);
Route::get('/favicon.ico', fn () => response('', 204));

Route::prefix('api')->group(function (): void {
    Route::get('/disponibilidad', [AppointmentCatalogController::class, 'availability']);
    Route::get('/especialidades', [AppointmentCatalogController::class, 'specialties']);
    Route::get('/especialidades-disponibles', [AppointmentCatalogController::class, 'availableSpecialties']);
    Route::get('/servicios', [AppointmentCatalogController::class, 'services']);
    Route::get('/medicos', [AppointmentCatalogController::class, 'doctors']);
    Route::get('/turnos', [AppointmentCatalogController::class, 'shifts']);
    Route::get('/estados-cita', [AppointmentCatalogController::class, 'appointmentStates']);
    Route::get('/prueba', fn () => response()->json(['ok' => true]));
    Route::get('/pacientes/buscar-sala', [WaitingRoomController::class, 'findPatient']);
    Route::post('/sala-espera/entrar', [WaitingRoomController::class, 'enter']);
    Route::get('/sala-espera/estado/{ticket}', [WaitingRoomController::class, 'status'])->where('ticket', '[A-Za-z0-9\-]+');
    Route::post('/sala-espera/enviar', [WaitingRoomController::class, 'submit']);
    Route::get('/sala-espera/registros', [WaitingRoomController::class, 'registrations']);
    Route::get('/debug-sala-espera', [WaitingRoomController::class, 'debug']);
    Route::get('/chatbot-citas', AppointmentChatbotController::class);
});

// Alias antiguos del PHP nativo.
Route::get('/api/disponibilidad.php', [AppointmentCatalogController::class, 'availability']);
Route::get('/api/especialidades.php', [AppointmentCatalogController::class, 'specialties']);
Route::get('/api/especialidades_disponibles.php', [AppointmentCatalogController::class, 'availableSpecialties']);
Route::get('/api/servicios.php', [AppointmentCatalogController::class, 'services']);
Route::get('/api/medicos.php', [AppointmentCatalogController::class, 'doctors']);
Route::get('/api/turnos.php', [AppointmentCatalogController::class, 'shifts']);
Route::get('/api/estados_cita.php', [AppointmentCatalogController::class, 'appointmentStates']);
Route::get('/api/pacientes_buscar_sala.php', [WaitingRoomController::class, 'findPatient']);
Route::post('/api/sala_espera_entrar.php', [WaitingRoomController::class, 'enter']);
Route::get('/api/sala_espera_estado.php', fn (\Illuminate\Http\Request $request, WaitingRoomController $controller) => $controller->status((string) $request->query('ticket', '')));
Route::post('/api/sala_espera_enviar.php', [WaitingRoomController::class, 'submit']);
Route::get('/api/sala_espera_registros.php', [WaitingRoomController::class, 'registrations']);
Route::get('/api/debug_sala_espera.php', [WaitingRoomController::class, 'debug']);
Route::get('/api/chatbot_citas.php', AppointmentChatbotController::class);

Route::get('/estado-base', [HealthController::class, 'sigh']);
Route::get('/test-sigh', [HealthController::class, 'sigh']);
Route::get('/reconectar-sigh', [HealthController::class, 'reconnectSigh']);
Route::get('/estado-citas', [HealthController::class, 'appointments']);
Route::get('/test-citas', [HealthController::class, 'appointments']);
Route::get('/reconectar-citas', [HealthController::class, 'reconnectAppointments']);
