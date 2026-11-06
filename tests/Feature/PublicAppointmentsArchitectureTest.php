<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PublicAppointmentsArchitectureTest extends TestCase
{
    public function test_public_pages_are_declared_by_laravel(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('appointments.public.index'));
        $this->assertNotNull(Route::getRoutes()->getByName('appointments.public.waiting-room'));
    }

    public function test_legacy_php_native_structure_is_not_used_as_runtime_backend(): void
    {
        $this->assertFileDoesNotExist(base_path('app/config/database_sqlserver.php'));
        $this->assertFileDoesNotExist(base_path('public/api/disponibilidad.php'));
        $this->assertFileDoesNotExist(base_path('public/router.php'));
    }

    public function test_appointment_layers_exist(): void
    {
        $this->assertFileExists(app_path('Http/Controllers/Appointments/AppointmentCatalogController.php'));
        $this->assertFileExists(app_path('Services/Appointments/SighAppointmentService.php'));
        $this->assertFileExists(app_path('Services/Appointments/WaitingRoomService.php'));
        $this->assertFileExists(resource_path('views/appointments/public/index.blade.php'));
        $this->assertFileExists(resource_path('views/appointments/public/waiting-room.blade.php'));
    }
}
