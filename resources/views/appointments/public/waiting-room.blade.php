@extends('layouts.appointments-public')

@section('title', 'Sala de Espera Virtual')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/SalaEspera.css') }}">
@endpush

@section('content')
<header class="hero-sala">
        <div class="hero-decoracion hero-decoracion-uno"></div>
        <div class="hero-decoracion hero-decoracion-dos"></div>

        <nav class="barra-superior-sala">
            <a href="{{ route('appointments.public.index') }}" class="marca-sala">
                <span class="marca-icono">🏥</span>
                <span>
                    <strong>Citas Hospitalarias</strong>
                    <small>Sala de espera virtual</small>
                </span>
            </a>

            <a href="{{ route('appointments.public.index') }}" class="btn-regresar">
                ← Regresar
            </a>
        </nav>

        <div class="contenido-hero-sala">
            <div class="hero-texto">
                <span class="etiqueta-hero">Atención ordenada y segura</span>

                <h1>Sala de Espera Virtual</h1>

                <p>
                    Ingresa a la cola, espera tu turno y completa los datos del paciente
                    cuando el sistema te habilite el formulario.
                </p>

                <div class="hero-puntos">
                    <span>Validación con central</span>
                    <span>Turno por orden de llegada</span>
                    <span>Formulario guiado</span>
                </div>
            </div>

            <div class="hero-card">
                <span class="hero-card-icono">⏱️</span>
                <strong>5 minutos</strong>
                <p>
                    Tiempo disponible para completar tus datos cuando llegue tu turno.
                </p>
            </div>
        </div>
    </header>

    <main class="contenedor-sala">
        <section class="progreso-sala">
            <div id="pasoIngreso" class="paso-sala activo">
                <span>1</span>
                <strong>Ingreso</strong>
                <small>Entrar a sala</small>
            </div>

            <div id="pasoEspera" class="paso-sala">
                <span>2</span>
                <strong>Ticket / Cola</strong>
                <small>Turno generado</small>
            </div>

            <div id="pasoFormulario" class="paso-sala">
                <span>3</span>
                <strong>Turno activo</strong>
                <small>Registrar datos</small>
            </div>

            <div id="pasoFinal" class="paso-sala">
                <span>4</span>
                <strong>Enviado</strong>
                <small>Registro finalizado</small>
            </div>
        </section>

        <section id="panelIngreso" class="panel-sala panel-bienvenida">
            <div class="panel-icono">👋</div>

            <div class="panel-centro">
                <span class="mini-etiqueta">Primer paso</span>

                <h2>Ingresar a sala de espera</h2>

                <p class="texto-ayuda">
                    Presiona el botón para generar tu ticket. Si no hay pacientes antes que tú,
                    el sistema habilitará el formulario de inmediato. Si hay cola, verás tu posición
                    hasta que llegue tu turno.
                </p>
            </div>

            <div class="requisitos-grid">
                <article>
                    <span>🪪</span>
                    <strong>DNI o historia clínica</strong>
                    <p>Ten listo tu documento para validar el registro del paciente.</p>
                </article>

                <article>
                    <span>📱</span>
                    <strong>Teléfono celular</strong>
                    <p>Se solicitará un número válido de 9 dígitos.</p>
                </article>

                <article>
                    <span>🩺</span>
                    <strong>Preferencia médica</strong>
                    <p>Selecciona una especialidad y el médico disponible.</p>
                </article>
            </div>

            <button id="btnEntrarCola" type="button" class="btn-principal grande">
                Entrar a sala de espera
            </button>
        </section>

        <section id="panelEspera" class="panel-sala oculto">
            <div class="cabecera-panel-linea">
                <div>
                    <span class="mini-etiqueta">Turno generado</span>

                    <h2>Esperando turno</h2>

                    <p>
                        Mantén esta ventana abierta. La pantalla cambiará automáticamente
                        cuando el sistema habilite tu registro.
                    </p>
                </div>

                <span id="badgeEstadoGlobal" class="badge-estado">En espera</span>
            </div>

            <div class="tarjeta-ticket">
                <span>Tu ticket</span>
                <strong id="ticketUsuario">---</strong>
                <small>Guarda este código hasta terminar el registro.</small>
            </div>

            <div class="estado-grid">
                <div class="estado-item">
                    <span>Estado</span>
                    <strong id="estadoTurno">En espera</strong>
                </div>

                <div class="estado-item destacado">
                    <span>Posición en cola</span>
                    <strong id="posicionCola">0</strong>
                </div>

                <div class="estado-item">
                    <span>Tiempo estimado</span>
                    <strong id="tiempoEstimado">0 min</strong>
                </div>
            </div>

            <div class="barra-cola">
                <div class="barra-cola-info">
                    <strong>Avance de atención</strong>
                    <span id="textoProgresoCola">Calculando...</span>
                </div>

                <div class="barra-cola-base">
                    <div id="barraProgresoCola" class="barra-cola-relleno"></div>
                </div>
            </div>
        </section>

        <section id="panelFormulario" class="panel-sala oculto">
            <div class="encabezado-formulario">
                <div>
                    <span class="mini-etiqueta">Turno activo</span>

                    <h2>Es tu turno</h2>

                    <p>Completa los datos antes de que termine el tiempo.</p>
                </div>

                <div class="contador">
                    <span>Tiempo restante</span>
                    <strong id="contadorTurno">05:00</strong>

                    <div class="barra-tiempo-base">
                        <div id="barraTiempoTurno" class="barra-tiempo-relleno"></div>
                    </div>
                </div>
            </div>

            <form id="formRegistroPaciente" class="formulario-paciente">
                <div class="grupo-formulario">
                    <div class="subtitulo-formulario">
                        <span>🔎</span>

                        <div>
                            <h3>Búsqueda del paciente</h3>
                            <p>
                                Ingrese DNI o historia clínica para validar al paciente en la central.
                            </p>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="dniPaciente">Documento de identidad</label>
                        <input
                            type="text"
                            id="dniPaciente"
                            placeholder="Ingrese DNI de 8 dígitos"
                            maxlength="8"
                            inputmode="numeric"
                            autocomplete="off"
                        >
                    </div>

                    <div class="campo">
                        <label for="historiaClinica">Historia clínica</label>
                        <input
                            type="text"
                            id="historiaClinica"
                            placeholder="Ingrese historia clínica"
                            autocomplete="off"
                        >
                    </div>

                    <p id="estadoValidacionPaciente" class="estado-validacion neutro">
                        Ingresa DNI o historia clínica y sal del campo para validar.
                    </p>
                </div>

                <div class="grupo-formulario">
                    <div class="subtitulo-formulario">
                        <span>👤</span>

                        <div>
                            <h3>Datos del paciente</h3>
                            <p>
                                Estos datos se completan automáticamente si el paciente existe.
                            </p>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="nombrePaciente">Nombre</label>
                        <input
                            type="text"
                            id="nombrePaciente"
                            placeholder="Se autocompleta"
                            required
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="apellidoPaciente">Apellido</label>
                        <input
                            type="text"
                            id="apellidoPaciente"
                            placeholder="Se autocompleta"
                            required
                            readonly
                        >
                    </div>

                    <div class="campo">
                        <label for="sexoPaciente">Sexo</label>
                        <select id="sexoPaciente" required disabled>
                            <option value="">Se autocompleta</option>
                            <option value="MASCULINO">Masculino</option>
                            <option value="FEMENINO">Femenino</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="telefonoPaciente">Teléfono</label>
                        <input
                            type="tel"
                            id="telefonoPaciente"
                            placeholder="Ejemplo: 987654321"
                            maxlength="9"
                            inputmode="numeric"
                            autocomplete="tel"
                            required
                        >
                    </div>
                </div>

                <div class="grupo-formulario">
                    <div class="subtitulo-formulario">
                        <span>🏥</span>

                        <div>
                            <h3>Preferencia de atención</h3>
                            <p>
                                Selecciona solamente la especialidad y luego el médico disponible.
                            </p>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="buscarEspecialidadSala">Especialidad</label>
                        <input
                            type="text"
                            id="buscarEspecialidadSala"
                            list="listaEspecialidadesSala"
                            placeholder="Escriba o seleccione especialidad"
                            autocomplete="off"
                            required
                        >
                        <datalist id="listaEspecialidadesSala"></datalist>
                        <input type="hidden" id="idEspecialidadSala">
                    </div>

                    <div class="campo">
                        <label for="buscarMedicoSala">Médico</label>
                        <input
                            type="text"
                            id="buscarMedicoSala"
                            list="listaMedicosSala"
                            placeholder="Primero seleccione una especialidad"
                            autocomplete="off"
                            required
                            disabled
                        >
                        <datalist id="listaMedicosSala"></datalist>
                        <input type="hidden" id="idMedicoSala">
                    </div>
                </div>

                <div class="grupo-formulario">
                    <div class="subtitulo-formulario">
                        <span>📋</span>

                        <div>
                            <h3>Datos de consulta</h3>
                            <p>
                                Registra si cuenta con SIS y el motivo principal de la atención.
                            </p>
                        </div>
                    </div>

                    <div class="campo">
                        <label for="tieneSisPaciente">¿Tiene SIS?</label>
                        <select id="tieneSisPaciente" required>
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo campo-completo">
                        <label for="motivoConsulta">Motivo de consulta</label>
                        <textarea
                            id="motivoConsulta"
                            rows="4"
                            placeholder="Describa brevemente el motivo de consulta"
                            maxlength="500"
                            required
                        ></textarea>
                    </div>
                </div>

                <div id="panelGinecologia" class="grupo-formulario grupo-ginecologia oculto">
                    <div class="subtitulo-formulario">
                        <span>🤰</span>

                        <div>
                            <h3>Preguntas de Ginecología</h3>
                            <p>
                                Estos campos aparecen solamente cuando la especialidad seleccionada es Ginecología u Obstetricia.
                            </p>
                        </div>
                    </div>

                    <div class="aviso-ginecologia campo-completo">
                        Complete esta información para derivar correctamente la atención ginecológica.
                    </div>

                    <div class="campo">
                        <label for="esGestante">¿Es gestante?</label>
                        <select id="esGestante">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="semanaGestacion">Semana de gestación</label>
                        <input
                            type="number"
                            id="semanaGestacion"
                            placeholder="Ejemplo: 24"
                            min="1"
                            max="42"
                            disabled
                        >
                    </div>

                    <div class="campo">
                        <label for="edadPaciente">Edad</label>
                        <input
                            type="number"
                            id="edadPaciente"
                            placeholder="Ejemplo: 28"
                            min="0"
                            max="120"
                        >
                    </div>

                    <div class="campo">
                        <label for="esAdolescente">¿Edad adolescente?</label>
                        <select id="esAdolescente">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="cesareaAnterior">¿Cesárea anterior?</label>
                        <select id="cesareaAnterior">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="diabetesPaciente">¿Diabética?</label>
                        <select id="diabetesPaciente">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="hipertensionPaciente">¿Hipertensión?</label>
                        <select id="hipertensionPaciente">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="infeccionesUrinariasPaciente">¿Infecciones urinarias repetitivas?</label>
                        <select id="infeccionesUrinariasPaciente">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="hemofiliaPaciente">¿Hemofilia?</label>
                        <select id="hemofiliaPaciente">
                            <option value="">Seleccione</option>
                            <option value="SI">Sí</option>
                            <option value="NO">No</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="numeroGestacion">Número de gestación</label>
                        <input
                            type="number"
                            id="numeroGestacion"
                            placeholder="Ejemplo: 2"
                            min="0"
                            max="20"
                        >
                    </div>

                    <div class="campo campo-completo">
                        <label for="otrosGinecologia">Otros</label>
                        <textarea
                            id="otrosGinecologia"
                            rows="3"
                            placeholder="Ingrese alguna observación adicional si corresponde"
                            maxlength="500"
                        ></textarea>
                    </div>
                </div>

                <div class="acciones-formulario">
                    <button id="btnEnviarRegistro" type="submit" class="btn-principal">
                        Enviar registro
                    </button>
                </div>
            </form>
        </section>

        <section id="panelFinal" class="panel-sala panel-final oculto">
            <div class="panel-icono exito">✅</div>

            <span class="mini-etiqueta">Proceso finalizado</span>

            <h2>Registro enviado</h2>

            <p class="mensaje-final">
                Tus datos fueron registrados correctamente.
            </p>

            <a href="{{ route('appointments.public.index') }}" class="btn-volver">
                Volver al inicio
            </a>
        </section>
    </main>

    <div id="fondoAlertaSala" class="fondo-alerta-sala oculto"></div>

    <div id="alertaSala" class="alerta-sala oculto" role="dialog" aria-modal="true">
        <div class="alerta-sala-icono">
            <span id="alertaSalaIcono">⚠️</span>
        </div>

        <div class="alerta-sala-contenido">
            <h3 id="alertaSalaTitulo">Aviso importante</h3>
            <p id="alertaSalaMensaje"></p>
        </div>

        <button id="btnCerrarAlertaSala" class="btn-cerrar-alerta-sala" type="button">
            Entendido
        </button>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/SalaEspera.js') }}"></script>
@endpush
