<?php

namespace App\Http\Controllers\Appointments;

use App\Http\Controllers\Controller;
use App\Services\Appointments\AppointmentChatbotService;
use App\Support\UserFacingError;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class AppointmentChatbotController extends Controller
{
    public function __construct(private readonly AppointmentChatbotService $chatbot) {}

    public function __invoke(Request $request): JsonResponse
    {
        $message = trim((string) $request->query('mensaje', ''));
        if ($message === '') {
            return response()->json(['ok' => false, 'error' => 'Debe ingresar una consulta.'], 400);
        }

        try {
            return response()->json(['ok' => true, ...$this->chatbot->answer($message)]);
        } catch (Throwable $exception) {
            $reference = UserFacingError::report($exception, 'CITAS-CHATBOT');
            return response()->json([
                'ok' => false,
                'error' => 'No se pudo consultar el asistente de citas.',
                'detalle' => config('app.debug') ? $exception->getMessage() : null,
                'reference' => $reference,
            ], 503);
        }
    }
}
