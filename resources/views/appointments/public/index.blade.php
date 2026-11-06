@extends('layouts.appointments-public')

@section('title', 'Disponibilidad por consultorio')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/PrincipalCita.css') }}">
@endpush

@section('content')
<main class="contenedor">
      <section class="card-citas">
        <header class="header">
          <div class="header-left">
            <div class="logo-hospital" aria-hidden="true">
              <i class="fa-solid fa-hospital-user"></i>
            </div>

            <div>
              <div class="titulo-linea">
                <h1>Disponibilidad por consultorio</h1>

                <span class="fecha-pill">
                  <i class="fa-solid fa-calendar-days"></i>
                  <span id="fechaHeader">Hoy</span>
                </span>
              </div>

              <p>Elige una especialidad y revisa los cupos disponibles del día.</p>
            </div>
          </div>

          <!-- Fecha inicial fija: siempre inicia en el día actual. -->
          <input type="hidden" id="fechaInicio" />

          <div class="rango-fecha-box">
            <label class="fecha-principal fecha-rango" title="Fecha límite del rango">
              <i class="fa-solid fa-calendar-days"></i>
              <input type="date" id="fechaFin" />
            </label>

            <span class="texto-rango-fecha">Fecha seleccionada</span>
          </div>
        </header>

      <section class="panel-filtros">
        <div class="barra-filtros">
          <label class="filtro-pill filtro-busqueda">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input
              type="text"
              id="busquedaRapida"
              placeholder="Buscar médico o servicio..."
              autocomplete="off"
            />
          </label>

          <label class="filtro-pill filtro-select filtro-especialidad">
            <i class="fa-solid fa-tag"></i>
            <select id="idEspecialidad">
              <option value="">Todas las especialidades</option>
            </select>
          </label>

          <label class="filtro-pill filtro-select filtro-turno">
            <i class="fa-solid fa-clock"></i>
            <select id="idTurnoVista">
              <option value="">Ambos turnos</option>
              <option value="manana">Mañana</option>
              <option value="tarde">Tarde</option>
            </select>
          </label>

          <label class="filtro-pill filtro-check">
            <input type="checkbox" id="soloConCita" checked />
            <span>Solo Citas Disponibles</span>
          </label>
        </div>

        <select id="idServicio" class="select-oculto-js" aria-hidden="true" tabindex="-1">
          <option value="">Todos los servicios</option>
        </select>

        <select id="idMedico" class="select-oculto-js" aria-hidden="true" tabindex="-1">
          <option value="">Todos los médicos</option>
        </select>

        <div class="acciones">
          <button id="btnBuscar" type="button">
            <i class="fa-solid fa-magnifying-glass"></i>
            Buscar
          </button>

          <button id="btnLimpiar" class="secundario" type="button">
            <i class="fa-solid fa-rotate"></i>
            Limpiar
          </button>

          <a href="{{ route('appointments.public.waiting-room') }}" class="btn-sala-espera">
            <i class="fa-solid fa-users"></i>
            Sala de espera
          </a>
        </div>
      </section>

      <section class="panel-tabla">
        <div class="tabla-contenedor">
          <table>
            <thead>
              <tr>
                <th>
                  <i class="fa-regular fa-calendar"></i>
                  Fecha
                </th>

                <th>
                  <i class="fa-solid fa-stethoscope"></i>
                  Consultorio / Servicio
                </th>

                <th>
                  <i class="fa-solid fa-user-doctor"></i>
                  Médico(a)
                </th>

                <th>
                  <i class="fa-solid fa-clock"></i>
                  Horario
                </th>

                <th>
                  <i class="fa-solid fa-ticket"></i>
                  Citas disponibles
                </th>

                <th>
                  <i class="fa-solid fa-ellipsis-vertical"></i>
                  Acciones
                </th>
              </tr>
            </thead>

            <tbody id="tablaDisponibilidad">
              <tr>
                <td colspan="6" class="sin-datos">No hay datos cargados.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pie-tabla">
          <span id="estadoCarga">
            <i class="fa-solid fa-list"></i>
            Esperando búsqueda...
          </span>

          <span id="actualizadoTabla">
            <i class="fa-solid fa-rotate"></i>
            Listo para consultar
          </span>
        </div>
      </section>

      <section class="resumen resumen-compacto" aria-label="Resumen de citas">
        <div class="tarjeta">
          <span class="label">Programaciones</span>
          <strong id="totalProgramaciones">0</strong>
        </div>

        <div class="tarjeta">
          <span class="label">Cupos programados</span>
          <strong id="totalCupos">0</strong>
        </div>

        <div class="tarjeta">
          <span class="label">Citas otorgadas</span>
          <strong id="totalOtorgadas">0</strong>
        </div>

        <div class="tarjeta disponible">
          <span class="label">Cupos disponibles</span>
          <strong id="totalDisponibles">0</strong>
        </div>
      </section>

      <p class="nota-confidencial">
        <i class="fa-solid fa-shield-halved"></i>
        Información referencial para consulta de disponibilidad de citas.
      </p>
    </main>
    

    <div id="modalDetalle" class="modal oculto">
      <div class="modal-contenido">
        <div class="modal-header">
          <h2>Detalle de disponibilidad</h2>

          <button id="btnCerrarModal" class="btn-cerrar" type="button">×</button>
        </div>

        <div id="contenidoDetalle" class="detalle-grid"></div>
      </div>
    </div>

    <div id="fondoAlertaHorario" class="fondo-alerta-horario oculto"></div>

    <div id="alertaHorario" class="alerta-horario oculto">
      <div class="alerta-horario-icono">
        <i class="fa-solid fa-clock"></i>
      </div>

      <div class="alerta-horario-contenido">
        <strong id="alertaHorarioTitulo">Horario de especialidad</strong>

        <p id="alertaHorarioMensaje"></p>

        <div class="alerta-horario-acciones">
          <button id="btnEntendidoHorario" type="button">Entendido</button>
        </div>
      </div>

      <button
        id="btnCerrarAlertaHorario"
        class="alerta-horario-cerrar"
        type="button"
      >
        ×
      </button>
    </div>

    <div id="chatbotHospital" class="chatbot-hospital oculto">
      <div class="chatbot-header">
        <div>
          <strong>Asistente de citas</strong>
          <span>Consulta horarios y cupos rápidamente</span>
        </div>

        <button id="btnCerrarChatbot" type="button">×</button>
      </div>

      <div id="chatbotMensajes" class="chatbot-mensajes">
        <div class="chatbot-msg bot">
          Hola, soy el asistente del sistema de citas. Puedes preguntarme por
          horarios, especialidades, servicios o cupos disponibles.
        </div>

        <div class="chatbot-sugerencias">
          <button type="button" class="chatbot-sugerencia">
            Horario de cardiología
          </button>

          <button type="button" class="chatbot-sugerencia">
            Cupos para oftalmología
          </button>

          <button type="button" class="chatbot-sugerencia">
            Medicina general
          </button>
        </div>
      </div>

      <div class="chatbot-input-area">
        <input
          type="text"
          id="chatbotInput"
          placeholder="Ejemplo: horario de traumatología"
          autocomplete="off"
        />

        <button id="btnEnviarChatbot" type="button">
          <i class="fa-solid fa-paper-plane"></i>
        </button>
      </div>
    </div>

    <button id="btnAbrirChatbot" class="chatbot-burbuja" type="button">
      <i class="fa-solid fa-comments"></i>
    </button>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/PrincipalCita.js') }}"></script>
@endpush
