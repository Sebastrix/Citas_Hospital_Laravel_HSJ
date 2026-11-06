<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Services\Appointments\SighAppointmentService;
use App\Services\Appointments\WaitingRoomService;
use App\Support\UserFacingError;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

final class WaitingRoomController extends Controller
{
    public function __construct(
        private readonly WaitingRoomService $waitingRoom,
        private readonly SighAppointmentService $sigh,
    ) {}

    public function enter(): JsonResponse
    {
        try {
            $state = $this->waitingRoom->enter();
            $state['mensaje'] = 'Ingresaste correctamente a la sala de espera';
            return response()->json($state);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'enter');
        }
    }

    public function status(string $ticket): JsonResponse
    {
        try {
            $state = $this->waitingRoom->status($ticket);
            return response()->json($state, $state['ok'] ? 200 : 404);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'status');
        }
    }

    public function findPatient(Request $request): JsonResponse
    {
        $dni = trim((string) $request->query('dni', ''));
        $history = trim((string) $request->query('historia', ''));

        if ($dni === '' && $history === '') {
            return response()->json(['ok' => false, 'error' => 'Debe ingresar DNI o historia clínica.'], 400);
        }
        if ($dni !== '' && ! preg_match('/^\d{8}$/', $dni)) {
            return response()->json(['ok' => false, 'error' => 'DNI inválido. Debe tener 8 dígitos.'], 400);
        }

        try {
            $patient = $this->sigh->findPatient($history ?: null, $dni ?: null);
            if (! $patient) {
                return response()->json(['ok' => true, 'existe' => false, 'mensaje' => 'Paciente no encontrado.']);
            }
            return response()->json(['ok' => true, 'existe' => true, 'data' => $patient]);
        } catch (Throwable $exception) {
            $reference = UserFacingError::report($exception, 'CITAS-PACIENTE', ['operation' => 'find']);
            return response()->json([
                'ok' => false,
                'existe' => false,
                'error' => 'Error buscando paciente en la base central.',
                'detalle' => config('app.debug') ? $exception->getMessage() : null,
                'reference' => $reference,
            ], 503);
        }
    }

    public function submit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ticket' => ['required', 'string', 'max:30'],
            'documento' => ['nullable', 'string', 'max:30', 'required_without:dni'],
            'dni' => ['nullable', 'string', 'max:30', 'required_without:documento'],
            'historiaClinica' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'sexo' => ['required', 'string'],
            'telefono' => ['required', 'regex:/^9\d{8}$/'],
            'idEspecialidad' => ['required', 'integer', 'min:1'],
            'especialidad' => ['required', 'string', 'max:150'],
            'idMedico' => ['required', 'integer', 'min:1'],
            'medico' => ['required', 'string', 'max:200'],
            'tieneSis' => ['required', 'string'],
            'motivoConsulta' => ['required', 'string', 'max:500'],
            'esGinecologia' => ['nullable', 'string'],
            'esGestante' => ['nullable', 'string'],
            'semanaGestacion' => ['nullable'],
            'edadPaciente' => ['nullable'],
            'esAdolescente' => ['nullable', 'string'],
            'cesareaAnterior' => ['nullable', 'string'],
            'diabetes' => ['nullable', 'string'],
            'hipertension' => ['nullable', 'string'],
            'infeccionesUrinariasRepetitivas' => ['nullable', 'string'],
            'hemofilia' => ['nullable', 'string'],
            'numeroGestacion' => ['nullable'],
            'otros' => ['nullable', 'string', 'max:500'],
        ], [
            'telefono.regex' => 'El teléfono debe tener 9 dígitos y empezar con 9.',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'error' => $validator->errors()->first()], 400);
        }

        try {
            $data = $validator->validated();
            $document = $this->processDocument((string) ($data['documento'] ?? $data['dni'] ?? ''));
            $data['sexo'] = $this->normalizeSex((string) $data['sexo']);
            $data['tieneSis'] = $this->normalizeYesNo($data['tieneSis'], '¿Tiene SIS?');
            $data['esGinecologia'] = $this->normalizeYesNo($data['esGinecologia'] ?? 'NO', 'especialidad ginecología', false);
            $data['esGestante'] = '';
            $data['semanaGestacion'] = null;
            $data['edadPaciente'] = $this->optionalInteger($data['edadPaciente'] ?? null, 'edad', 0, 120);
            $data['esAdolescente'] = $this->normalizeYesNo($data['esAdolescente'] ?? '', 'edad adolescente', false);
            $data['cesareaAnterior'] = $this->normalizeYesNo($data['cesareaAnterior'] ?? '', 'cesárea anterior', false);
            $data['diabetes'] = $this->normalizeYesNo($data['diabetes'] ?? '', 'diabetes', false);
            $data['hipertension'] = $this->normalizeYesNo($data['hipertension'] ?? '', 'hipertensión', false);
            $data['infeccionesUrinariasRepetitivas'] = $this->normalizeYesNo($data['infeccionesUrinariasRepetitivas'] ?? '', 'infecciones urinarias repetitivas', false);
            $data['hemofilia'] = $this->normalizeYesNo($data['hemofilia'] ?? '', 'hemofilia', false);
            $data['numeroGestacion'] = $this->optionalInteger($data['numeroGestacion'] ?? null, 'número de gestación', 0, 20);
            $data['otros'] = $this->clean((string) ($data['otros'] ?? ''));
            $data['motivoConsulta'] = $this->clean((string) $data['motivoConsulta']);

            if ($data['esGinecologia'] === 'SI') {
                $data['esGestante'] = $this->normalizeYesNo($request->input('esGestante', ''), '¿Es gestante?');
                if ($data['esGestante'] === 'SI') {
                    $data['semanaGestacion'] = $this->requiredInteger($request->input('semanaGestacion'), 'semana de gestación', 1, 42);
                }
            }

            $result = $this->waitingRoom->submit([...$data, ...$document]);
            return response()->json(['ok' => true, 'mensaje' => 'Registro enviado correctamente', 'data' => $result]);
        } catch (DomainException $exception) {
            return response()->json(['ok' => false, 'error' => $exception->getMessage()], 400);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'submit');
        }
    }

    public function registrations(): JsonResponse
    {
        try {
            $data = $this->waitingRoom->registrations();
            return response()->json(['ok' => true, 'total' => count($data), 'data' => $data]);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'registrations');
        }
    }

    public function debug(): JsonResponse
    {
        try {
            return response()->json(['ok' => true, ...$this->waitingRoom->debug()]);
        } catch (Throwable $exception) {
            return $this->serverError($exception, 'debug');
        }
    }

    private function processDocument(string $value): array
    {
        $document = mb_strtoupper(trim($value), 'UTF-8');
        if (preg_match('/^\d{8}$/', $document)) return ['tipoDocumento' => 'DNI', 'docIden' => $document];
        if (preg_match('/^1\d{8}$/', $document)) return ['tipoDocumento' => 'DNI', 'docIden' => substr($document, 1)];
        if (preg_match('/^2[A-Z0-9]{4,20}$/', $document)) return ['tipoDocumento' => 'PASAPORTE', 'docIden' => substr($document, 1)];
        throw new DomainException('Documento inválido. Para DNI ingrese solo 8 dígitos.');
    }

    private function normalizeSex(string $sex): string
    {
        $sex = mb_strtoupper($this->clean($sex), 'UTF-8');
        if (! in_array($sex, ['MASCULINO', 'FEMENINO'], true)) throw new DomainException('Sexo inválido. Use MASCULINO o FEMENINO.');
        return $sex;
    }

    private function normalizeYesNo(mixed $value, string $field, bool $required = true): string
    {
        $value = mb_strtoupper($this->clean((string) $value), 'UTF-8');
        if ($value === 'SÍ') $value = 'SI';
        if ($value === '' && ! $required) return '';
        if (! in_array($value, ['SI', 'NO'], true)) throw new DomainException("El campo {$field} debe ser Sí o No.");
        return $value;
    }

    private function optionalInteger(mixed $value, string $field, int $min, int $max): ?int
    {
        if ($value === null || trim((string) $value) === '') return null;
        return $this->requiredInteger($value, $field, $min, $max);
    }

    private function requiredInteger(mixed $value, string $field, int $min, int $max): int
    {
        if ($value === null || trim((string) $value) === '') throw new DomainException("El campo {$field} es obligatorio.");
        if (! preg_match('/^-?\d+$/', trim((string) $value))) throw new DomainException("El campo {$field} debe ser numérico.");
        $number = (int) $value;
        if ($number < $min || $number > $max) throw new DomainException("El campo {$field} debe estar entre {$min} y {$max}.");
        return $number;
    }

    private function clean(string $value): string
    {
        return preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
    }

    private function serverError(Throwable $exception, string $operation): JsonResponse
    {
        $reference = UserFacingError::report($exception, 'CITAS-SALA', ['operation' => $operation]);
        return response()->json([
            'ok' => false,
            'error' => 'No se pudo completar la operación de sala de espera.',
            'detalle' => config('app.debug') ? $exception->getMessage() : null,
            'reference' => $reference,
        ], 500);
    }
}
