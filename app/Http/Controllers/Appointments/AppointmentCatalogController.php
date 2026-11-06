<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Services\Appointments\SighAppointmentService;
use App\Support\UserFacingError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class AppointmentCatalogController extends Controller
{
    public function __construct(private readonly SighAppointmentService $sigh) {}

    public function availability(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fechaInicio' => ['nullable', 'date_format:Y-m-d'],
            'fechaFin' => ['nullable', 'date_format:Y-m-d'],
            'idDepartamento' => ['nullable', 'integer', 'min:1'],
            'idEspecialidad' => ['nullable', 'integer', 'min:1'],
            'idServicio' => ['nullable', 'integer', 'min:1'],
            'idMedico' => ['nullable', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) return $this->validationError($validator->errors()->first());

        $data = $validator->validated();
        $selected = $data['fechaFin'] ?? $data['fechaInicio'] ?? now()->toDateString();
        $data['fechaInicio'] = $selected;
        $data['fechaFin'] = $selected;

        return $this->respond(fn () => $this->sigh->availability($data), 'disponibilidad', ['fechaConsultada' => $selected]);
    }

    public function specialties(): JsonResponse
    {
        return $this->respond(fn () => $this->sigh->specialties(), 'especialidades');
    }

    public function availableSpecialties(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fechaInicio' => ['nullable', 'date_format:Y-m-d'],
            'fechaFin' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if ($validator->fails()) return $this->validationError($validator->errors()->first());
        $data = $validator->validated();

        return $this->respond(fn () => $this->sigh->availableSpecialties($data['fechaInicio'] ?? null, $data['fechaFin'] ?? null), 'especialidades-disponibles');
    }

    public function services(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idEspecialidad' => ['nullable', 'integer', 'min:1'],
            'idDepartamento' => ['nullable', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) return $this->validationError($validator->errors()->first());
        $data = $validator->validated();

        return $this->respond(fn () => $this->sigh->services(
            isset($data['idEspecialidad']) ? (int) $data['idEspecialidad'] : null,
            isset($data['idDepartamento']) ? (int) $data['idDepartamento'] : null,
        ), 'servicios');
    }

    public function doctors(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idEspecialidad' => ['nullable', 'integer', 'min:1'],
            'idServicio' => ['nullable', 'integer', 'min:1'],
            'idDepartamento' => ['nullable', 'integer', 'min:1'],
        ]);
        if ($validator->fails()) return $this->validationError($validator->errors()->first());
        $data = $validator->validated();

        return $this->respond(fn () => $this->sigh->doctors(
            isset($data['idEspecialidad']) ? (int) $data['idEspecialidad'] : null,
            isset($data['idServicio']) ? (int) $data['idServicio'] : null,
            isset($data['idDepartamento']) ? (int) $data['idDepartamento'] : null,
        ), 'medicos');
    }

    public function shifts(): JsonResponse
    {
        return $this->respond(fn () => $this->sigh->shifts(), 'turnos');
    }

    public function appointmentStates(): JsonResponse
    {
        return $this->respond(fn () => $this->sigh->appointmentStates(), 'estados-cita');
    }

    private function respond(callable $callback, string $operation, array $extra = []): JsonResponse
    {
        try {
            $data = $callback();
            return response()->json(['ok' => true, ...$extra, 'total' => count($data), 'data' => $data]);
        } catch (Throwable $exception) {
            $reference = UserFacingError::report($exception, 'CITAS-SIGH', ['operation' => $operation]);
            return response()->json([
                'ok' => false,
                'error' => 'El servicio clínico no está disponible temporalmente.',
                'detalle' => config('app.debug') ? $exception->getMessage() : null,
                'reference' => $reference,
            ], 503);
        }
    }

    private function validationError(string $message): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $message], 422);
    }
}
