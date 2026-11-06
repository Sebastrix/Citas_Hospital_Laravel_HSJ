let ticketActual = localStorage.getItem("ticketSalaEspera") || null;

const APP_BASE_PATH = (() => {
  const pathname = window.location.pathname;
  const match = pathname.match(/^(.*\/public)(?:\/|$)/i);

  if (match) {
    return match[1];
  }

  return "";
})();

const API_URL = `${window.location.origin}${APP_BASE_PATH}`;
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || "";
const DURACION_TURNO_SEGUNDOS = 300;

const panelIngreso = document.getElementById("panelIngreso");
const panelEspera = document.getElementById("panelEspera");
const panelFormulario = document.getElementById("panelFormulario");
const panelFinal = document.getElementById("panelFinal");

const btnEntrarCola = document.getElementById("btnEntrarCola");
const ticketUsuario = document.getElementById("ticketUsuario");
const estadoTurno = document.getElementById("estadoTurno");
const posicionCola = document.getElementById("posicionCola");
const tiempoEstimado = document.getElementById("tiempoEstimado");
const contadorTurno = document.getElementById("contadorTurno");
const formRegistroPaciente = document.getElementById("formRegistroPaciente");
const barraTiempoTurno = document.getElementById("barraTiempoTurno");
const barraProgresoCola = document.getElementById("barraProgresoCola");
const textoProgresoCola = document.getElementById("textoProgresoCola");
const badgeEstadoGlobal = document.getElementById("badgeEstadoGlobal");
const estadoValidacionPaciente = document.getElementById("estadoValidacionPaciente");

const pasoIngreso = document.getElementById("pasoIngreso");
const pasoEspera = document.getElementById("pasoEspera");
const pasoFormulario = document.getElementById("pasoFormulario");
const pasoFinal = document.getElementById("pasoFinal");

const nombrePaciente = document.getElementById("nombrePaciente");
const apellidoPaciente = document.getElementById("apellidoPaciente");
const sexoPaciente = document.getElementById("sexoPaciente");
const historiaClinica = document.getElementById("historiaClinica");
const dniPaciente = document.getElementById("dniPaciente");
const telefonoPaciente = document.getElementById("telefonoPaciente");

const fondoAlertaSala = document.getElementById("fondoAlertaSala");
const alertaSala = document.getElementById("alertaSala");
const alertaSalaIcono = document.getElementById("alertaSalaIcono");
const alertaSalaTitulo = document.getElementById("alertaSalaTitulo");
const alertaSalaMensaje = document.getElementById("alertaSalaMensaje");
const btnCerrarAlertaSala = document.getElementById("btnCerrarAlertaSala");

const buscarEspecialidadSala = document.getElementById("buscarEspecialidadSala");
const listaEspecialidadesSala = document.getElementById("listaEspecialidadesSala");
const idEspecialidadSala = document.getElementById("idEspecialidadSala");

const buscarMedicoSala = document.getElementById("buscarMedicoSala");
const listaMedicosSala = document.getElementById("listaMedicosSala");
const idMedicoSala = document.getElementById("idMedicoSala");

const tieneSisPaciente = document.getElementById("tieneSisPaciente");
const motivoConsulta = document.getElementById("motivoConsulta");

const panelGinecologia = document.getElementById("panelGinecologia");
const esGestante = document.getElementById("esGestante");
const semanaGestacion = document.getElementById("semanaGestacion");
const edadPaciente = document.getElementById("edadPaciente");
const esAdolescente = document.getElementById("esAdolescente");
const cesareaAnterior = document.getElementById("cesareaAnterior");
const diabetesPaciente = document.getElementById("diabetesPaciente");
const hipertensionPaciente = document.getElementById("hipertensionPaciente");
const infeccionesUrinariasPaciente = document.getElementById("infeccionesUrinariasPaciente");
const hemofiliaPaciente = document.getElementById("hemofiliaPaciente");
const numeroGestacion = document.getElementById("numeroGestacion");
const otrosGinecologia = document.getElementById("otrosGinecologia");

let intervaloEstado = null;
let intervaloContador = null;
let segundosRestantesActual = DURACION_TURNO_SEGUNDOS;
let pacienteValidado = false;

let especialidadesSala = [];
let medicosSala = [];

const VALOR_SI = "SI";
const VALOR_NO = "NO";

const normalizadoresEspecialidad = [
  "GINECO",
  "OBSTET",
  "MATERNO",
];

document.addEventListener("DOMContentLoaded", async () => {
  if (nombrePaciente) nombrePaciente.readOnly = true;
  if (apellidoPaciente) apellidoPaciente.readOnly = true;
  if (sexoPaciente) sexoPaciente.disabled = true;

  mostrarPanelInicial();
  configurarEventosFormulario();

  await cargarEspecialidadesSala();

  if (ticketActual) {
    mostrarPanel(panelEspera);
    ticketUsuario.textContent = ticketActual;
    await consultarEstadoTurno();
  }

  iniciarConsultaAutomatica();
});

function configurarEventosFormulario() {
  if (btnEntrarCola) {
    btnEntrarCola.addEventListener("click", async () => {
      await entrarSalaEspera();
    });
  }

  if (formRegistroPaciente) {
    formRegistroPaciente.addEventListener("submit", async (event) => {
      event.preventDefault();
      await enviarRegistroPaciente();
    });
  }

  if (historiaClinica) {
    historiaClinica.addEventListener("blur", async () => {
      await buscarPacienteSala("historia");
    });

    historiaClinica.addEventListener("input", () => {
      pacienteValidado = false;

      mostrarEstadoPaciente(
        "neutro",
        "Validación pendiente. Sal del campo para buscar la historia clínica.",
      );

      dniPaciente.value = "";
      nombrePaciente.value = "";
      apellidoPaciente.value = "";
      sexoPaciente.value = "";
    });
  }

  if (dniPaciente) {
    dniPaciente.addEventListener("blur", async () => {
      await buscarPacienteSala("dni");
    });

    dniPaciente.addEventListener("input", () => {
      pacienteValidado = false;

      mostrarEstadoPaciente(
        "neutro",
        "Validación pendiente. Completa los 8 dígitos y sal del campo.",
      );

      dniPaciente.value = dniPaciente.value.replace(/\D/g, "").slice(0, 8);

      historiaClinica.value = "";
      nombrePaciente.value = "";
      apellidoPaciente.value = "";
      sexoPaciente.value = "";
    });
  }

  if (telefonoPaciente) {
    telefonoPaciente.addEventListener("input", () => {
      telefonoPaciente.value = telefonoPaciente.value
        .replace(/\D/g, "")
        .slice(0, 9);
    });
  }

  if (btnCerrarAlertaSala) {
    btnCerrarAlertaSala.addEventListener("click", cerrarAlertaSala);
  }

  if (fondoAlertaSala) {
    fondoAlertaSala.addEventListener("click", cerrarAlertaSala);
  }

  if (buscarEspecialidadSala) {
    buscarEspecialidadSala.addEventListener("change", async () => {
      await seleccionarEspecialidadSala();
    });

    buscarEspecialidadSala.addEventListener("blur", async () => {
      await seleccionarEspecialidadSala();
    });

    buscarEspecialidadSala.addEventListener("input", () => {
      idEspecialidadSala.value = "";
      buscarMedicoSala.value = "";
      idMedicoSala.value = "";
      buscarMedicoSala.disabled = true;
      medicosSala = [];
      listaMedicosSala.innerHTML = "";
      evaluarBloqueGinecologia();
    });
  }

  if (buscarMedicoSala) {
    buscarMedicoSala.addEventListener("change", () => {
      seleccionarMedicoSala();
    });

    buscarMedicoSala.addEventListener("blur", () => {
      seleccionarMedicoSala();
    });
  }

  if (esGestante) {
    esGestante.addEventListener("change", actualizarCampoSemanaGestacion);
  }

  if (semanaGestacion) {
    semanaGestacion.addEventListener("input", () => {
      limitarNumero(semanaGestacion, 1, 42);
    });
  }

  if (edadPaciente) {
    edadPaciente.addEventListener("input", () => {
      limitarNumero(edadPaciente, 0, 120);
      marcarAdolescentePorEdad();
    });
  }

  if (numeroGestacion) {
    numeroGestacion.addEventListener("input", () => {
      limitarNumero(numeroGestacion, 0, 20);
    });
  }
}

async function entrarSalaEspera() {
  try {
    btnEntrarCola.disabled = true;
    btnEntrarCola.textContent = "Ingresando...";

    const response = await fetch(`${API_URL}/api/sala-espera/entrar`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": CSRF_TOKEN,
      },
    });

    const result = await response.json();

    if (!result.ok) {
      mostrarAlertaSala({
        titulo: "No se pudo ingresar",
        mensaje: result.error || "No se pudo ingresar a la sala de espera.",
        tipo: "error",
        icono: "🚫",
      });
      return;
    }

    ticketActual = result.ticket;
    localStorage.setItem("ticketSalaEspera", ticketActual);
    ticketUsuario.textContent = ticketActual;

    actualizarEstadoPantalla(result);
  } catch (error) {
    mostrarAlertaSala({
      titulo: "Error de conexión",
      mensaje: "No se pudo conectar con la sala de espera: " + error.message,
      tipo: "error",
      icono: "📡",
    });
  } finally {
    btnEntrarCola.disabled = false;
    btnEntrarCola.textContent = "Entrar a sala de espera";
  }
}

async function consultarEstadoTurno() {
  try {
    if (!ticketActual) {
      mostrarPanelInicial();
      return;
    }

    const response = await fetch(
      `${API_URL}/api/sala-espera/estado/${ticketActual}`,
    );
    const result = await response.json();

    if (!result.ok) {
      limpiarTicket();
      mostrarPanelInicial();
      return;
    }

    actualizarEstadoPantalla(result);
  } catch (error) {
    console.error("Error consultando estado:", error);
  }
}

function actualizarEstadoPantalla(data) {
  ticketUsuario.textContent = ticketActual || "---";

  if (data.estado === "ACTIVO") {
    actualizarBadgeEstado("Turno activo", "activo");
    mostrarPanel(panelFormulario);

    segundosRestantesActual = Number(
      data.segundosRestantes || DURACION_TURNO_SEGUNDOS,
    );

    iniciarContador(segundosRestantesActual);
    return;
  }

  if (data.estado === "ESPERA") {
    actualizarBadgeEstado("En espera", "espera");
    mostrarPanel(panelEspera);

    estadoTurno.textContent = "En espera";
    posicionCola.textContent = data.posicion || 0;
    tiempoEstimado.textContent = formatearTiempoEstimado(
      data.segundosEstimados || 0,
    );

    actualizarProgresoCola(Number(data.posicion || 0));
    detenerContador();
    return;
  }

  if (data.estado === "FINALIZADO") {
    actualizarBadgeEstado("Finalizado", "finalizado");
    limpiarTicket();
    mostrarPanel(panelFinal);
    detenerContador();
    return;
  }

  if (data.estado === "EXPIRADO") {
    mostrarAlertaSala({
      titulo: "Turno expirado",
      mensaje: "Tu turno expiró porque se terminó el tiempo.",
      tipo: "advertencia",
      icono: "⏱️",
    });

    limpiarTicket();
    limpiarFormulario();
    mostrarPanelInicial();
    detenerContador();
    return;
  }

  mostrarPanelInicial();
}

async function buscarPacienteSala(tipoBusqueda) {
  try {
    mostrarEstadoPaciente("neutro", "Buscando paciente en la central...");

    const historia = historiaClinica.value.trim();
    const documentoIngresado = dniPaciente.value.trim().toUpperCase();

    let url = "";

    if (tipoBusqueda === "historia") {
      if (!historia) {
        pacienteValidado = false;
        mostrarEstadoPaciente(
          "neutro",
          "Ingresa una historia clínica para validar.",
        );
        return;
      }

      url = `${API_URL}/api/pacientes/buscar-sala?historia=${encodeURIComponent(historia)}`;
    }

    if (tipoBusqueda === "dni") {
      if (!documentoIngresado) {
        pacienteValidado = false;
        mostrarEstadoPaciente("neutro", "Ingresa un DNI para validar.");
        return;
      }

      if (!/^\d{8}$/.test(documentoIngresado)) {
        mostrarAlertaSala({
          titulo: "DNI no válido",
          mensaje: "Debe ingresar solo 8 dígitos del DNI.",
          tipo: "error",
          icono: "🪪",
        });

        pacienteValidado = false;
        mostrarEstadoPaciente("error", "DNI no válido. Debe tener 8 dígitos.");
        dniPaciente.focus();
        return;
      }

      url = `${API_URL}/api/pacientes/buscar-sala?dni=${encodeURIComponent(documentoIngresado)}`;
    }

    const response = await fetch(url);
    const result = await response.json();

    if (!result.ok || !result.existe) {
      pacienteValidado = false;

      historiaClinica.value = "";
      dniPaciente.value = "";
      nombrePaciente.value = "";
      apellidoPaciente.value = "";
      sexoPaciente.value = "";

      if (tipoBusqueda === "historia") {
        mostrarEstadoPaciente(
          "advertencia",
          "Historia clínica no encontrada. Debe acercarse a ventanilla.",
        );

        mostrarAlertaSala({
          titulo: "Historia clínica no encontrada",
          mensaje:
            "La historia clínica ingresada no existe en la central. El paciente debe acercarse a ventanilla para tramitarla.",
          tipo: "advertencia",
          icono: "🏥",
        });

        historiaClinica.focus();
      } else {
        mostrarEstadoPaciente(
          "advertencia",
          "DNI sin historia clínica registrada. Debe acercarse a ventanilla.",
        );

        mostrarAlertaSala({
          titulo: "DNI sin historia clínica",
          mensaje:
            "El DNI ingresado no tiene una historia clínica registrada. El paciente debe acercarse a ventanilla para tramitarla.",
          tipo: "advertencia",
          icono: "🪪",
        });

        dniPaciente.focus();
      }

      return;
    }

    const paciente = result.data;

    historiaClinica.value = paciente.historiaClinica || "";
    dniPaciente.value = paciente.documento || "";
    nombrePaciente.value = paciente.nombre || "";
    apellidoPaciente.value = paciente.apellido || "";
    sexoPaciente.value = paciente.sexo || "";

    pacienteValidado = true;

    mostrarEstadoPaciente(
      "exito",
      "Paciente validado correctamente. Continúa con el teléfono y la preferencia de atención.",
    );
  } catch (error) {
    pacienteValidado = false;

    mostrarEstadoPaciente("error", "Error buscando paciente en la central.");

    mostrarAlertaSala({
      titulo: "Error buscando paciente",
      mensaje: error.message,
      tipo: "error",
      icono: "📡",
    });
  }
}

function normalizarTextoComparacion(texto) {
  return String(texto || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .trim()
    .replace(/\s+/g, " ")
    .toUpperCase();
}

function buscarItemPorNombre(lista, texto) {
  const normalizado = normalizarTextoComparacion(texto);

  return lista.find((item) => {
    return normalizarTextoComparacion(item.nombre) === normalizado;
  });
}

function pintarDatalist(datalist, data) {
  datalist.innerHTML = "";

  data.forEach((item) => {
    const option = document.createElement("option");
    option.value = item.nombre;
    datalist.appendChild(option);
  });
}

function obtenerIdEspecialidad(item) {
  return item.IdEspecialidad ?? item.idEspecialidad ?? item.id ?? null;
}

function obtenerNombreEspecialidad(item) {
  return item.Nombre ?? item.Especialidad ?? item.nombre ?? "";
}

async function cargarEspecialidadesSala() {
  try {
    let result = await fetchJsonSeguro(`${API_URL}/api/especialidades-disponibles`);

    if (!result.ok || !Array.isArray(result.data) || result.data.length === 0) {
      result = await fetchJsonSeguro(`${API_URL}/api/especialidades`);
    }

    if (!result.ok) {
      console.error(result.error || "Error cargando especialidades");
      return;
    }

    especialidadesSala = (result.data || [])
      .map((item) => ({
        id: obtenerIdEspecialidad(item),
        nombre: obtenerNombreEspecialidad(item),
      }))
      .filter((item) => item.id !== null && item.id !== "" && item.nombre !== "")
      .sort((a, b) => a.nombre.localeCompare(b.nombre));

    pintarDatalist(listaEspecialidadesSala, especialidadesSala);
  } catch (error) {
    console.error("Error cargando especialidades:", error);
  }
}

async function fetchJsonSeguro(url) {
  const response = await fetch(url);
  return await response.json();
}

async function seleccionarEspecialidadSala() {
  const texto = buscarEspecialidadSala.value.trim();

  if (!texto) {
    idEspecialidadSala.value = "";
    buscarMedicoSala.value = "";
    idMedicoSala.value = "";
    medicosSala = [];
    listaMedicosSala.innerHTML = "";
    buscarMedicoSala.disabled = true;
    buscarMedicoSala.placeholder = "Primero seleccione una especialidad";
    evaluarBloqueGinecologia();
    return;
  }

  const especialidad = buscarItemPorNombre(especialidadesSala, texto);

  if (!especialidad) {
    idEspecialidadSala.value = "";
    buscarMedicoSala.value = "";
    idMedicoSala.value = "";
    medicosSala = [];
    listaMedicosSala.innerHTML = "";
    buscarMedicoSala.disabled = true;
    evaluarBloqueGinecologia();
    return;
  }

  if (
    String(idEspecialidadSala.value) === String(especialidad.id) &&
    medicosSala.length > 0
  ) {
    evaluarBloqueGinecologia();
    return;
  }

  idEspecialidadSala.value = especialidad.id;
  buscarEspecialidadSala.value = especialidad.nombre;

  buscarMedicoSala.value = "";
  idMedicoSala.value = "";
  medicosSala = [];
  listaMedicosSala.innerHTML = "";
  buscarMedicoSala.disabled = true;
  buscarMedicoSala.placeholder = "Cargando médicos...";

  evaluarBloqueGinecologia();
  await cargarMedicosSala(especialidad.id);
}

async function cargarMedicosSala(idEspecialidad = null) {
  try {
    const id = idEspecialidad || idEspecialidadSala.value;

    if (!id) return;

    const response = await fetch(
      `${API_URL}/api/medicos?idEspecialidad=${encodeURIComponent(id)}`,
    );

    const result = await response.json();

    if (!result.ok) {
      console.error(result.error || "Error cargando médicos");
      buscarMedicoSala.placeholder = "No se pudieron cargar médicos";
      return;
    }

    medicosSala = (result.data || [])
      .map((item) => ({
        id: item.IdMedico ?? item.idMedico ?? item.id,
        nombre: item.Nombre ?? item.nombre ?? "",
      }))
      .filter((item) => item.id !== null && item.id !== undefined && item.nombre !== "")
      .sort((a, b) => a.nombre.localeCompare(b.nombre));

    pintarDatalist(listaMedicosSala, medicosSala);

    buscarMedicoSala.disabled = false;
    buscarMedicoSala.placeholder = medicosSala.length > 0
      ? "Escriba o seleccione médico"
      : "No hay médicos disponibles para esta especialidad";
  } catch (error) {
    console.error("Error cargando médicos:", error);
    buscarMedicoSala.placeholder = "Error cargando médicos";
  }
}

function seleccionarMedicoSala() {
  const texto = buscarMedicoSala.value.trim();

  idMedicoSala.value = "";

  if (!texto) return;

  const medico = buscarItemPorNombre(medicosSala, texto);

  if (!medico) {
    return;
  }

  idMedicoSala.value = medico.id;
  buscarMedicoSala.value = medico.nombre;
}

function esEspecialidadGinecologia() {
  const texto = normalizarTextoComparacion(buscarEspecialidadSala.value);
  return normalizadoresEspecialidad.some((palabra) => texto.includes(palabra));
}

function evaluarBloqueGinecologia() {
  const mostrar = esEspecialidadGinecologia();

  if (!panelGinecologia) return;

  if (mostrar) {
    panelGinecologia.classList.remove("oculto");
    if (esGestante) esGestante.required = true;
    return;
  }

  panelGinecologia.classList.add("oculto");
  limpiarCamposGinecologia();
}

function actualizarCampoSemanaGestacion() {
  if (!semanaGestacion || !esGestante) return;

  if (esGestante.value === VALOR_SI) {
    semanaGestacion.disabled = false;
    semanaGestacion.required = true;
    semanaGestacion.placeholder = "Indique semana de gestación";
    return;
  }

  semanaGestacion.value = "";
  semanaGestacion.disabled = true;
  semanaGestacion.required = false;
  semanaGestacion.placeholder = "Solo si es gestante";
}

function limpiarCamposGinecologia() {
  const controles = [
    esGestante,
    semanaGestacion,
    edadPaciente,
    esAdolescente,
    cesareaAnterior,
    diabetesPaciente,
    hipertensionPaciente,
    infeccionesUrinariasPaciente,
    hemofiliaPaciente,
    numeroGestacion,
    otrosGinecologia,
  ];

  controles.forEach((control) => {
    if (!control) return;
    control.value = "";
    control.required = false;
  });

  if (semanaGestacion) {
    semanaGestacion.disabled = true;
  }
}

function marcarAdolescentePorEdad() {
  if (!edadPaciente || !esAdolescente) return;

  const edad = Number(edadPaciente.value || 0);

  if (!edadPaciente.value) {
    esAdolescente.value = "";
    return;
  }

  esAdolescente.value = edad >= 10 && edad <= 19 ? VALOR_SI : VALOR_NO;
}

function limitarNumero(input, minimo, maximo) {
  if (!input || input.value === "") return;

  const valor = Number(input.value);

  if (Number.isNaN(valor)) {
    input.value = "";
    return;
  }

  if (valor < minimo) input.value = String(minimo);
  if (valor > maximo) input.value = String(maximo);
}

function validarSelectSiNo(select, nombreCampo) {
  if (!select) return true;

  const valor = select.value.trim();

  if (valor !== VALOR_SI && valor !== VALOR_NO) {
    mostrarAlertaSala({
      titulo: "Campo requerido",
      mensaje: `Seleccione Sí o No en el campo: ${nombreCampo}.`,
      tipo: "advertencia",
      icono: "📋",
    });

    select.focus();
    return false;
  }

  return true;
}

function validarCamposGinecologia() {
  if (!esEspecialidadGinecologia()) {
    return true;
  }

  if (!validarSelectSiNo(esGestante, "¿Es gestante?")) return false;

  if (esGestante.value === VALOR_SI) {
    const semana = Number(semanaGestacion.value || 0);

    if (!semanaGestacion.value || semana < 1 || semana > 42) {
      mostrarAlertaSala({
        titulo: "Semana de gestación requerida",
        mensaje: "Si la paciente es gestante, indique una semana entre 1 y 42.",
        tipo: "advertencia",
        icono: "🤰",
      });

      semanaGestacion.focus();
      return false;
    }
  }

  return true;
}

async function enviarRegistroPaciente() {
  try {
    if (!ticketActual) {
      mostrarAlertaSala({
        titulo: "Ticket no activo",
        mensaje:
          "No tienes un ticket activo. Ingresa nuevamente a la sala de espera.",
        tipo: "advertencia",
        icono: "🎫",
      });
      return;
    }

    if (!pacienteValidado) {
      mostrarAlertaSala({
        titulo: "Validación requerida",
        mensaje: "Primero debe ingresar una historia clínica o DNI válido.",
        tipo: "advertencia",
        icono: "🔎",
      });

      historiaClinica.focus();
      return;
    }

    const documento = dniPaciente.value.trim().toUpperCase();
    const historia = historiaClinica.value.trim();
    const nombre = nombrePaciente.value.trim();
    const apellido = apellidoPaciente.value.trim();
    const sexo = sexoPaciente.value.trim();
    const telefono = telefonoPaciente.value.trim();

    await seleccionarEspecialidadSala();
    seleccionarMedicoSala();

    const idEspecialidad = idEspecialidadSala.value;
    const especialidad = buscarEspecialidadSala.value.trim();
    const idMedico = idMedicoSala.value;
    const medico = buscarMedicoSala.value.trim();
    const tieneSis = tieneSisPaciente.value.trim();
    const motivo = motivoConsulta.value.trim();

    if (!/^9\d{8}$/.test(telefono)) {
      mostrarAlertaSala({
        titulo: "Teléfono inválido",
        mensaje: "Ingrese un número celular válido de 9 dígitos que empiece con 9.",
        tipo: "error",
        icono: "📱",
      });

      telefonoPaciente.focus();
      return;
    }

    if (!idEspecialidad) {
      mostrarAlertaSala({
        titulo: "Especialidad no válida",
        mensaje: "Seleccione una especialidad de la lista.",
        tipo: "advertencia",
        icono: "🏥",
      });

      buscarEspecialidadSala.focus();
      return;
    }

    if (!idMedico) {
      mostrarAlertaSala({
        titulo: "Médico no válido",
        mensaje: "Seleccione un médico de la lista.",
        tipo: "advertencia",
        icono: "👨‍⚕️",
      });

      buscarMedicoSala.focus();
      return;
    }

    if (!documento || !historia || !nombre || !apellido || !sexo) {
      mostrarAlertaSala({
        titulo: "Campos incompletos",
        mensaje: "Completa todos los datos del paciente antes de enviar.",
        tipo: "advertencia",
        icono: "📝",
      });

      return;
    }

    if (!/^\d{8}$/.test(documento)) {
      mostrarAlertaSala({
        titulo: "DNI no válido",
        mensaje:
          "Ingrese solo los 8 dígitos del DNI. No agregue el número 1 al inicio.",
        tipo: "error",
        icono: "🪪",
      });

      dniPaciente.focus();
      return;
    }

    if (!validarSelectSiNo(tieneSisPaciente, "¿Tiene SIS?")) return;

    if (!motivo) {
      mostrarAlertaSala({
        titulo: "Motivo requerido",
        mensaje: "Ingrese el motivo de consulta antes de enviar el registro.",
        tipo: "advertencia",
        icono: "📋",
      });

      motivoConsulta.focus();
      return;
    }

    if (!validarCamposGinecologia()) return;

    const datos = {
      ticket: ticketActual,
      documento: documento,
      dni: documento,
      historiaClinica: historia,
      nombre: nombre,
      apellido: apellido,
      sexo: sexo,
      telefono: telefono,
      idEspecialidad: idEspecialidad,
      especialidad: especialidad,
      idServicio: 0,
      servicio: "NO APLICA",
      idMedico: idMedico,
      medico: medico,
      tieneSis: tieneSis,
      motivoConsulta: motivo,
      esGinecologia: esEspecialidadGinecologia() ? VALOR_SI : VALOR_NO,
      esGestante: esEspecialidadGinecologia() ? esGestante.value.trim() : "",
      semanaGestacion: esGestante.value === VALOR_SI ? semanaGestacion.value.trim() : "",
      edadPaciente: esEspecialidadGinecologia() ? edadPaciente.value.trim() : "",
      esAdolescente: esEspecialidadGinecologia() ? esAdolescente.value.trim() : "",
      cesareaAnterior: esEspecialidadGinecologia() ? cesareaAnterior.value.trim() : "",
      diabetes: esEspecialidadGinecologia() ? diabetesPaciente.value.trim() : "",
      hipertension: esEspecialidadGinecologia() ? hipertensionPaciente.value.trim() : "",
      infeccionesUrinariasRepetitivas: esEspecialidadGinecologia()
        ? infeccionesUrinariasPaciente.value.trim()
        : "",
      hemofilia: esEspecialidadGinecologia() ? hemofiliaPaciente.value.trim() : "",
      numeroGestacion: esEspecialidadGinecologia() ? numeroGestacion.value.trim() : "",
      otros: esEspecialidadGinecologia() ? otrosGinecologia.value.trim() : "",
    };

    console.log("DATOS ENVIADOS:", datos);

    const response = await fetch(`${API_URL}/api/sala-espera/enviar`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": CSRF_TOKEN,
      },
      body: JSON.stringify(datos),
    });

    const result = await response.json();

    console.log("RESPUESTA SERVIDOR:", result);

    if (!result.ok) {
      mostrarAlertaSala({
        titulo: "No se pudo enviar",
        mensaje: result.error || "No se pudo enviar el registro.",
        tipo: "error",
        icono: "🚫",
      });

      return;
    }

    limpiarTicket();
    limpiarFormulario();
    detenerContador();

    mostrarPanel(panelFinal);
  } catch (error) {
    mostrarAlertaSala({
      titulo: "Error enviando registro",
      mensaje: error.message,
      tipo: "error",
      icono: "📡",
    });
  }
}

function iniciarContador(segundosIniciales) {
  detenerContador();

  segundosRestantesActual = Number(
    segundosIniciales || DURACION_TURNO_SEGUNDOS,
  );
  pintarContador();

  intervaloContador = setInterval(() => {
    segundosRestantesActual--;

    if (segundosRestantesActual <= 0) {
      segundosRestantesActual = 0;
      pintarContador();
      detenerContador();
      consultarEstadoTurno();
      return;
    }

    pintarContador();
  }, 1000);
}

function detenerContador() {
  if (intervaloContador) {
    clearInterval(intervaloContador);
    intervaloContador = null;
  }
}

function pintarContador() {
  const minutos = Math.floor(segundosRestantesActual / 60);
  const segundos = segundosRestantesActual % 60;

  contadorTurno.textContent = `${String(minutos).padStart(2, "0")}:${String(segundos).padStart(2, "0")}`;

  if (barraTiempoTurno) {
    const porcentaje = Math.max(
      0,
      Math.min(100, (segundosRestantesActual / DURACION_TURNO_SEGUNDOS) * 100),
    );

    barraTiempoTurno.style.width = `${porcentaje}%`;
  }
}

function iniciarConsultaAutomatica() {
  if (intervaloEstado) {
    clearInterval(intervaloEstado);
  }

  intervaloEstado = setInterval(async () => {
    if (ticketActual) {
      await consultarEstadoTurno();
    }
  }, 3000);
}

function mostrarPanel(panelActivo) {
  panelIngreso.classList.add("oculto");
  panelEspera.classList.add("oculto");
  panelFormulario.classList.add("oculto");
  panelFinal.classList.add("oculto");

  panelActivo.classList.remove("oculto");
  actualizarAvanceSala(panelActivo);
}

function mostrarPanelInicial() {
  mostrarPanel(panelIngreso);
}

function limpiarTicket() {
  ticketActual = null;
  localStorage.removeItem("ticketSalaEspera");
}

function limpiarFormulario() {
  nombrePaciente.value = "";
  apellidoPaciente.value = "";
  sexoPaciente.value = "";
  historiaClinica.value = "";
  dniPaciente.value = "";
  telefonoPaciente.value = "";

  buscarEspecialidadSala.value = "";
  idEspecialidadSala.value = "";

  buscarMedicoSala.value = "";
  idMedicoSala.value = "";
  buscarMedicoSala.disabled = true;
  buscarMedicoSala.placeholder = "Primero seleccione una especialidad";

  if (tieneSisPaciente) tieneSisPaciente.value = "";
  if (motivoConsulta) motivoConsulta.value = "";

  limpiarCamposGinecologia();
  evaluarBloqueGinecologia();

  pacienteValidado = false;

  mostrarEstadoPaciente(
    "neutro",
    "Ingresa DNI o historia clínica y sal del campo para validar.",
  );
}

function actualizarAvanceSala(panelActivo) {
  const pasos = [pasoIngreso, pasoEspera, pasoFormulario, pasoFinal];

  pasos.forEach((paso) => {
    if (!paso) return;
    paso.classList.remove("activo", "completado");
  });

  if (panelActivo === panelIngreso) {
    pasoIngreso.classList.add("activo");
    return;
  }

  if (panelActivo === panelEspera) {
    pasoIngreso.classList.add("completado");
    pasoEspera.classList.add("activo");
    return;
  }

  if (panelActivo === panelFormulario) {
    pasoIngreso.classList.add("completado");
    pasoEspera.classList.add("completado");
    pasoFormulario.classList.add("activo");
    return;
  }

  if (panelActivo === panelFinal) {
    pasoIngreso.classList.add("completado");
    pasoEspera.classList.add("completado");
    pasoFormulario.classList.add("completado");
    pasoFinal.classList.add("activo");
  }
}

function actualizarBadgeEstado(texto, estado) {
  if (!badgeEstadoGlobal) return;

  badgeEstadoGlobal.textContent = texto;
  badgeEstadoGlobal.classList.remove("activo", "espera", "finalizado");
  badgeEstadoGlobal.classList.add(estado);
}

function actualizarProgresoCola(posicion) {
  if (!barraProgresoCola) return;

  const posicionNumerica = Number(posicion || 0);

  const porcentaje =
    posicionNumerica <= 1
      ? 88
      : Math.max(18, Math.min(82, 90 - posicionNumerica * 8));

  barraProgresoCola.style.width = `${porcentaje}%`;

  if (textoProgresoCola) {
    textoProgresoCola.textContent =
      posicionNumerica <= 1
        ? "Estás cerca de ser atendido"
        : `Faltan aproximadamente ${posicionNumerica - 1} ticket(s) antes que tú`;
  }
}

function mostrarEstadoPaciente(tipo, mensaje) {
  if (!estadoValidacionPaciente) return;

  estadoValidacionPaciente.classList.remove(
    "neutro",
    "exito",
    "error",
    "advertencia",
  );

  estadoValidacionPaciente.classList.add(tipo || "neutro");
  estadoValidacionPaciente.textContent = mensaje || "";
}

function formatearTiempoEstimado(segundos) {
  const totalSegundos = Number(segundos || 0);
  const minutos = Math.ceil(totalSegundos / 60);

  if (minutos <= 0) {
    return "Menos de 1 min";
  }

  if (minutos === 1) {
    return "1 min";
  }

  return `${minutos} min`;
}

function mostrarAlertaSala({
  titulo = "Aviso importante",
  mensaje = "",
  tipo = "advertencia",
  icono = "⚠️",
}) {
  if (!alertaSala || !fondoAlertaSala) {
    alert(mensaje);
    return;
  }

  alertaSala.classList.remove("error", "advertencia", "exito");
  alertaSala.classList.add(tipo);

  alertaSalaTitulo.textContent = titulo;
  alertaSalaMensaje.textContent = mensaje;
  alertaSalaIcono.textContent = icono;

  fondoAlertaSala.classList.remove("oculto");
  alertaSala.classList.remove("oculto");
}

function cerrarAlertaSala() {
  if (alertaSala) {
    alertaSala.classList.add("oculto");
  }

  if (fondoAlertaSala) {
    fondoAlertaSala.classList.add("oculto");
  }
}