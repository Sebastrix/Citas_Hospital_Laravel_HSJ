# Arquitectura migrada - Portal Público de Citas HSJ

## Objetivo

Este proyecto convierte el sistema `Citas_Hospital` que estaba construido con Node.js + Express + HTML/CSS/JavaScript en una aplicación Laravel 13 siguiendo el mismo patrón estructural usado por `IntranetHSJ-USJB`.

## Antes

```text
Citas_Hospital/
├── Backend/
│   ├── server.js
│   ├── package.json
│   └── node_modules/
└── Frontend/
    ├── pages/
    │   ├── PrincipalCita.html
    │   └── SalaEspera.html
    ├── css/
    └── js/
```

La conexión SQL Server, las consultas, la sala de espera, el chatbot y todas las rutas estaban concentradas principalmente en `server.js`.

## Ahora

```text
Citas_Hospital_Laravel_HSJ/
├── app/
│   ├── Http/Controllers/Appointments/
│   │   ├── PublicAppointmentController.php
│   │   ├── AppointmentCatalogController.php
│   │   ├── WaitingRoomController.php
│   │   ├── AppointmentChatbotController.php
│   │   └── HealthController.php
│   ├── Models/Appointments/
│   │   ├── WaitingRoomTicket.php
│   │   └── WaitingRoomRegistration.php
│   ├── Services/Appointments/
│   │   ├── SighAppointmentService.php
│   │   ├── WaitingRoomService.php
│   │   └── AppointmentChatbotService.php
│   └── Support/UserFacingError.php
├── bootstrap/
├── config/
│   ├── database.php
│   └── services.php
├── database/migrations/
├── public/assets/
│   ├── css/
│   ├── js/
│   └── vendor/preline/
├── resources/views/
│   ├── layouts/appointments-public.blade.php
│   └── appointments/public/
│       ├── index.blade.php
│       └── waiting-room.blade.php
└── routes/web.php
```

## Stack resultante

- PHP 8.3+
- Laravel 13
- Blade
- Tailwind CSS 4
- Preline 4
- JavaScript
- Microsoft SQL Server mediante `pdo_sqlsrv` / `sqlsrv`
- Composer
- Node.js + npm únicamente para compilar los recursos frontend

## Conexiones SQL Server

### `sqlsrv`
Base propia `CITAS`. Guarda los tickets y registros de sala de espera.

### `sigh`
Base clínica institucional `SIGH`. Se utiliza para consultar programaciones, especialidades, servicios, médicos, cupos y pacientes.

Las credenciales ya no están escritas dentro del código. Se cargan desde `.env`.

## Sala de espera

En Node.js la cola se encontraba parcialmente en variables del proceso (`colaSalaEspera`, `turnoActualSalaEspera`, sets de tickets). Esa lógica no es segura al migrarla directamente a PHP porque no persiste entre solicitudes.

La versión Laravel utiliza SQL Server mediante `SalaEsperaTickets` para persistir:

- ticket;
- orden de llegada;
- estado `ESPERA`, `ACTIVO`, `FINALIZADO` o `EXPIRADO`;
- inicio del turno;
- vencimiento del turno;
- finalización.

El turno sigue durando 300 segundos (5 minutos), pero ya no se pierde al reiniciar el servidor web.

## Compatibilidad de URLs

Se conservaron las URLs principales del sistema original para reducir cambios en el JavaScript:

- `/`
- `/principal`
- `/sala-espera`
- `/api/disponibilidad`
- `/api/especialidades`
- `/api/especialidades-disponibles`
- `/api/servicios`
- `/api/medicos`
- `/api/turnos`
- `/api/estados-cita`
- `/api/sala-espera/entrar`
- `/api/sala-espera/estado/{ticket}`
- `/api/pacientes/buscar-sala`
- `/api/sala-espera/enviar`
- `/api/sala-espera/registros`
- `/api/chatbot-citas`

## Seguridad aplicada

- Las credenciales SQL Server fueron retiradas del código fuente.
- `.env` está ignorado por Git.
- Se entrega únicamente `.env.example` sin secretos.
- Los POST de sala de espera utilizan token CSRF de Laravel.
- Las entradas recibidas por API se validan en controladores.
- Los filtros SQL se pasan como parámetros, no concatenando valores de usuario.
- Los errores de conexión generan referencias de soporte en lugar de exponer excepciones en producción.

## Integración futura con IntranetHSJ-USJB

La estructura está preparada para que el módulo pueda integrarse posteriormente a la intranet. Al hacerlo, los controladores y servicios de `Appointments` pueden trasladarse al proyecto principal y las rutas administrativas pueden protegerse con los middlewares institucionales `central.auth`, `module.access` y `central.permission` que ya utiliza la intranet.
