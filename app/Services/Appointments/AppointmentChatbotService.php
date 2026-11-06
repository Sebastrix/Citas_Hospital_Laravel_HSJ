<?php

namespace App\Services\Appointments;

use App\Models\Appointments\ChatbotLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Throwable;

final class AppointmentChatbotService
{
    public function __construct(private readonly SighAppointmentService $sigh) {}

    public function answer(string $message): array
    {
        $interpretation = $this->interpret($message);

        if ($interpretation['intencion'] === 'SALUDO') {
            return $this->finish($message, 'Hola, soy el asistente virtual de citas. Puedes preguntarme por horarios, cupos, médicos, servicios o sala de espera.', $interpretation);
        }
        if ($interpretation['intencion'] === 'SALA_ESPERA') {
            return $this->finish($message, "Para ingresar a sala de espera, presiona el botón azul 'Sala de espera'. Allí podrás registrar tus datos y esperar tu turno.", $interpretation);
        }
        if ($this->sigh->searchTerms($message) === []) {
            return $this->finish($message, '¿Sobre qué especialidad, servicio o médico deseas consultar? Ejemplo: horario de cardiología, cupos para oftalmología o médico de traumatología.', $interpretation);
        }

        $rows = $this->sigh->chatbotPrograms($interpretation['busqueda'], $interpretation['fechaConsulta']);
        if ($rows === []) {
            return $this->finish($message, 'No encontré programaciones próximas con esa consulta. Prueba con otra especialidad, servicio o fecha.', $interpretation);
        }

        $lines = [];
        foreach ($rows as $index => $item) {
            $available = (int) ($item->CuposDisponibles ?? 0);
            $state = $available > 0 ? $available.' cupo(s) disponible(s)' : 'Sin cupos disponibles';
            $lines[] = ($index + 1).'. '.($item->Especialidad ?? '')."\n".
                'Servicio: '.($item->Servicio ?? '')."\n".
                'Fecha: '.($item->FechaTexto ?? '')."\n".
                'Horario: '.($item->HoraInicio ?? '').' - '.($item->HoraFin ?? '')."\n".
                'Turno: '.(($item->Turno ?? '') ?: 'No registrado')."\n".
                'Médico: '.(($item->Medico ?? '') ?: 'No registrado')."\n".
                'Estado: '.$state;
        }

        $heading = match ($interpretation['intencion']) {
            'HORARIO' => 'Estos son los horarios encontrados:',
            'CUPOS' => 'Estos son los cupos encontrados:',
            'MEDICO' => 'Estos son los médicos encontrados:',
            default => 'Encontré estas programaciones próximas:',
        };

        return $this->finish($message, $heading."\n\n".implode("\n\n", $lines), $interpretation);
    }

    private function interpret(string $message): array
    {
        $text = mb_strtolower(trim($message), 'UTF-8');
        $normalized = mb_strtolower(Str::ascii($text), 'UTF-8');
        $today = CarbonImmutable::today();

        if (preg_match('/\b(hola|buenos dias|buenas tardes|buenas noches|saludos)\b/u', $normalized)) {
            return ['intencion' => 'SALUDO', 'busqueda' => '', 'fechaConsulta' => $today->toDateString()];
        }
        if (str_contains($normalized, 'sala') && str_contains($normalized, 'espera')) {
            return ['intencion' => 'SALA_ESPERA', 'busqueda' => '', 'fechaConsulta' => $today->toDateString()];
        }

        $intent = 'GENERAL';
        if (str_contains($normalized, 'horario') || str_contains($normalized, 'atiende') || str_contains($normalized, 'hora')) $intent = 'HORARIO';
        elseif (str_contains($normalized, 'cupo') || str_contains($normalized, 'disponible') || str_contains($normalized, 'cita')) $intent = 'CUPOS';
        elseif (str_contains($normalized, 'medico') || str_contains($normalized, 'doctor') || str_contains($normalized, 'personal')) $intent = 'MEDICO';

        $date = $today;
        if (str_contains($normalized, 'manana')) {
            $date = $today->addDay();
        } elseif (! str_contains($normalized, 'hoy')) {
            $days = ['domingo' => 0, 'lunes' => 1, 'martes' => 2, 'miercoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sabado' => 6];
            foreach ($days as $name => $number) {
                if (str_contains($normalized, $name)) {
                    $difference = $number - $today->dayOfWeek;
                    if ($difference <= 0) $difference += 7;
                    $date = $today->addDays($difference);
                    break;
                }
            }
        }

        return ['intencion' => $intent, 'busqueda' => $message, 'fechaConsulta' => $date->toDateString()];
    }

    private function finish(string $message, string $response, array $interpretation): array
    {
        try {
            ChatbotLog::query()->create([
                'MensajeUsuario' => $message,
                'RespuestaBot' => $response,
                'Intencion' => $interpretation['intencion'] ?? 'GENERAL',
                'FechaRegistro' => now(),
            ]);
        } catch (Throwable) {
            // El log local no debe interrumpir la consulta pública.
        }

        return ['respuesta' => $response, 'interpretacion' => $interpretation];
    }
}
