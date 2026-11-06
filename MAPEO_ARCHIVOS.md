# Mapeo del sistema original a Laravel

| Sistema original | Nueva ubicación | Responsabilidad |
|---|---|---|
| `Backend/server.js` rutas de páginas | `PublicAppointmentController.php` | Renderizar vistas públicas |
| `Backend/server.js` disponibilidad y catálogos | `AppointmentCatalogController.php` + `SighAppointmentService.php` | Consultas de citas en SIGH |
| `Backend/server.js` cola en memoria | `WaitingRoomController.php` + `WaitingRoomService.php` | Sala de espera persistente |
| `Backend/server.js` búsqueda de paciente | `SighAppointmentService.php` | Consulta de pacientes SIGH |
| `Backend/server.js` chatbot | `AppointmentChatbotController.php` + `AppointmentChatbotService.php` | Asistente de citas |
| `Backend/server.js` conexiones | `config/database.php` + `.env` | Configuración SQL Server |
| `PrincipalCita.html` | `resources/views/appointments/public/index.blade.php` | Vista Blade principal |
| `SalaEspera.html` | `resources/views/appointments/public/waiting-room.blade.php` | Vista Blade sala de espera |
| `PrincipalCita.css` | `public/assets/css/PrincipalCita.css` | Estilos de la página principal |
| `SalaEspera.css` | `public/assets/css/SalaEspera.css` | Estilos de sala de espera |
| `PrincipalCita.js` | `public/assets/js/PrincipalCita.js` | Interacción del portal |
| `SalaEspera.js` | `public/assets/js/SalaEspera.js` | Interacción y polling de la cola |
| Express `app.get/app.post` | `routes/web.php` | Enrutamiento Laravel |
| variables JS de cola | `SalaEsperaTickets` en SQL Server | Persistencia de turnos |
| `SalaEsperaRegistros` | Modelo `WaitingRoomRegistration` | Registros de pacientes |

La interfaz se conserva, pero la lógica del servidor deja de depender de Node/Express y pasa a PHP/Laravel siguiendo MVC + Services.
