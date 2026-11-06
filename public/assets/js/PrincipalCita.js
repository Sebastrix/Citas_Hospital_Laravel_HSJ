let disponibilidadActual = [];
let disponibilidadFiltrada = [];

const APP_BASE_PATH = (() => {
  const pathname = window.location.pathname;
  const match = pathname.match(/^(.*\/public)(?:\/|$)/i);

  if (match) {
    return match[1];
  }

  return "";
})();

const API_URL = `${window.location.origin}${APP_BASE_PATH}`;

const fechaInicioInput = document.getElementById("fechaInicio");
const fechaFinInput = document.getElementById("fechaFin");
const idEspecialidadSelect = document.getElementById("idEspecialidad");
const idServicioSelect = document.getElementById("idServicio");
const idMedicoSelect = document.getElementById("idMedico");
const idTurnoVistaSelect = document.getElementById("idTurnoVista");
const busquedaRapidaInput = document.getElementById("busquedaRapida");
const soloConCitaInput = document.getElementById("soloConCita");

const btnBuscar = document.getElementById("btnBuscar");
const btnLimpiar = document.getElementById("btnLimpiar");

const tablaDisponibilidad = document.getElementById("tablaDisponibilidad");
const estadoCarga = document.getElementById("estadoCarga");
const actualizadoTabla = document.getElementById("actualizadoTabla");

const totalProgramaciones = document.getElementById("totalProgramaciones");
const totalCupos = document.getElementById("totalCupos");
const totalOtorgadas = document.getElementById("totalOtorgadas");
const totalDisponibles = document.getElementById("totalDisponibles");

const modalDetalle = document.getElementById("modalDetalle");
const contenidoDetalle = document.getElementById("contenidoDetalle");
const btnCerrarModal = document.getElementById("btnCerrarModal");

const alertaHorario = document.getElementById("alertaHorario");
const alertaHorarioTitulo = document.getElementById("alertaHorarioTitulo");
const alertaHorarioMensaje = document.getElementById("alertaHorarioMensaje");
const btnCerrarAlertaHorario = document.getElementById("btnCerrarAlertaHorario");
const btnEntendidoHorario = document.getElementById("btnEntendidoHorario");
const fondoAlertaHorario = document.getElementById("fondoAlertaHorario");

let temporizadorAlertaHorario = null;

/* =========================
   INICIO
========================= */

document.addEventListener("DOMContentLoaded", async () => {
  colocarFechaActual();
  sincronizarFechaSeleccionada();
  actualizarFechaHeader();

  await cargarCatalogos();
  await buscarDisponibilidad();
});

if (btnBuscar) {
  btnBuscar.addEventListener("click", async () => {
    sincronizarFechaSeleccionada();
    actualizarFechaHeader();
    await buscarDisponibilidad();
  });
}

if (btnLimpiar) {
  btnLimpiar.addEventListener("click", async () => {
    limpiarFiltros();
    await cargarCatalogos();
    await buscarDisponibilidad();
  });
}

if (fechaInicioInput) {
  fechaInicioInput.addEventListener("change", async () => {
    sincronizarFechaSeleccionada();
    actualizarFechaHeader();

    await cargarEspecialidades();
    await cargarServicios();
    await cargarMedicos();
    await buscarDisponibilidad();
  });
}

if (fechaFinInput) {
  fechaFinInput.addEventListener("change", async () => {
    sincronizarFechaSeleccionada();
    actualizarFechaHeader();

    await cargarEspecialidades();
    await cargarServicios();
    await cargarMedicos();
    await buscarDisponibilidad();
  });

  fechaFinInput.addEventListener("input", () => {
    sincronizarFechaSeleccionada();
    actualizarFechaHeader();
  });
}

if (idEspecialidadSelect) {
  idEspecialidadSelect.addEventListener("change", async () => {
    if (idServicioSelect) {
      idServicioSelect.value = "";
      idServicioSelect.innerHTML = `<option value="">Cargando servicios...</option>`;
      idServicioSelect.disabled = true;
    }

    if (idMedicoSelect) {
      idMedicoSelect.value = "";
      idMedicoSelect.innerHTML = `<option value="">Cargando médicos...</option>`;
      idMedicoSelect.disabled = true;
    }

    await cargarServicios();
    await cargarMedicos();
    await buscarDisponibilidad();
  });
}

if (idServicioSelect) {
  idServicioSelect.addEventListener("change", async () => {
    await cargarMedicos();
    await buscarDisponibilidad();
  });
}

if (idTurnoVistaSelect) {
  idTurnoVistaSelect.addEventListener("change", aplicarFiltrosVista);
}

if (busquedaRapidaInput) {
  busquedaRapidaInput.addEventListener("input", aplicarFiltrosVista);
}

if (soloConCitaInput) {
  soloConCitaInput.addEventListener("change", aplicarFiltrosVista);
}

if (btnCerrarModal && modalDetalle) {
  btnCerrarModal.addEventListener("click", cerrarModalDetalle);

  modalDetalle.addEventListener("click", (event) => {
    if (event.target === modalDetalle) {
      cerrarModalDetalle();
    }
  });
}

if (btnCerrarAlertaHorario) {
  btnCerrarAlertaHorario.addEventListener("click", cerrarAlertaHorario);
}

if (btnEntendidoHorario) {
  btnEntendidoHorario.addEventListener("click", cerrarAlertaHorario);
}

if (fondoAlertaHorario) {
  fondoAlertaHorario.addEventListener("click", cerrarAlertaHorario);
}

/* =========================
   FECHAS
========================= */

function obtenerHoyISO() {
  const hoy = new Date();
  const yyyy = hoy.getFullYear();
  const mm = String(hoy.getMonth() + 1).padStart(2, "0");
  const dd = String(hoy.getDate()).padStart(2, "0");

  return `${yyyy}-${mm}-${dd}`;
}

function colocarFechaActual() {
  const hoyISO = obtenerHoyISO();

  if (fechaFinInput) {
    fechaFinInput.value = hoyISO;
    fechaFinInput.min = hoyISO;
  }

  if (fechaInicioInput) {
    fechaInicioInput.value = hoyISO;
  }
}

function obtenerFechaSeleccionada() {
  if (fechaFinInput && fechaFinInput.value) {
    return fechaFinInput.value;
  }

  if (fechaInicioInput && fechaInicioInput.value) {
    return fechaInicioInput.value;
  }

  return obtenerHoyISO();
}

function sincronizarFechaSeleccionada() {
  if (!fechaInicioInput || !fechaFinInput) return;

  const hoyISO = obtenerHoyISO();

  if (!fechaFinInput.value) {
    fechaFinInput.value = hoyISO;
  }

  fechaFinInput.min = hoyISO;

  if (fechaFinInput.value < hoyISO) {
    fechaFinInput.value = hoyISO;
  }

  fechaInicioInput.value = fechaFinInput.value;

  console.log("Fecha seleccionada sincronizada:", {
    fechaInicio: fechaInicioInput.value,
    fechaFin: fechaFinInput.value,
  });
}

function actualizarFechaHeader() {
  const elemento = document.getElementById("fechaHeader");

  if (!elemento) return;

  const fechaSeleccionada = obtenerFechaSeleccionada();
  elemento.textContent = formatearFechaLarga(fechaSeleccionada);
}

function obtenerTextoRangoSeleccionado() {
  const fechaSeleccionada = fechaFinInput?.value || fechaInicioInput?.value || obtenerHoyISO();
  return formatearFechaLarga(fechaSeleccionada);
}

function formatearFechaLarga(fechaISO) {
  if (!fechaISO) return "Hoy";

  const partes = String(fechaISO).split("-");

  if (partes.length !== 3) return fechaISO;

  const fecha = new Date(
    Number(partes[0]),
    Number(partes[1]) - 1,
    Number(partes[2])
  );

  const opciones = {
    day: "numeric",
    month: "long",
    year: "numeric",
  };

  return fecha.toLocaleDateString("es-PE", opciones);
}

function formatearFechaLargaConDia(fechaISO) {
  if (!fechaISO) return "Hoy";

  const partes = String(fechaISO).split("-");

  if (partes.length !== 3) return fechaISO;

  const fecha = new Date(
    Number(partes[0]),
    Number(partes[1]) - 1,
    Number(partes[2])
  );

  const opciones = {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  };

  const texto = fecha.toLocaleDateString("es-PE", opciones);

  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

/* =========================
   FILTROS
========================= */

function limpiarFiltros() {
  colocarFechaActual();
  sincronizarFechaSeleccionada();
  actualizarFechaHeader();

  if (busquedaRapidaInput) busquedaRapidaInput.value = "";
  if (idTurnoVistaSelect) idTurnoVistaSelect.value = "";
  if (soloConCitaInput) soloConCitaInput.checked = true;

  if (idEspecialidadSelect) idEspecialidadSelect.value = "";
  if (idServicioSelect) idServicioSelect.value = "";
  if (idMedicoSelect) idMedicoSelect.value = "";
}

function aplicarFiltrosVista() {
  const busqueda = normalizarTexto(busquedaRapidaInput?.value || "");
  const turnoVista = idTurnoVistaSelect?.value || "";
  const soloConCita = Boolean(soloConCitaInput?.checked);

  const fechaSeleccionada = fechaFinInput?.value || fechaInicioInput?.value || obtenerHoyISO();

  disponibilidadFiltrada = disponibilidadActual.filter((item) => {
    const disponibles = Number(item.CuposDisponibles || 0);
    const fechaItem = formatearFecha(item.Fecha);

    // IMPORTANTE:
    // Solo se muestran registros de la fecha seleccionada por el usuario.
    if (fechaItem !== fechaSeleccionada) {
      return false;
    }

    if (soloConCita && disponibles <= 0) {
      return false;
    }

    if (turnoVista && obtenerTurnoVista(item) !== turnoVista) {
      return false;
    }

    if (busqueda && !coincideBusqueda(item, busqueda)) {
      return false;
    }

    return true;
  });

  pintarTabla(disponibilidadFiltrada);
  pintarResumen(disponibilidadFiltrada);
  actualizarPieTabla(disponibilidadFiltrada.length);
}

function coincideBusqueda(item, busquedaNormalizada) {
  const texto = [
    item.Servicio,
    item.Medico,
    item.Especialidad,
    item.Departamento,
    item.Turno,
  ].join(" ");

  return normalizarTexto(texto).includes(busquedaNormalizada);
}

function obtenerTurnoVista(item) {
  const turno = normalizarTexto(item.Turno || "");

  if (turno.includes("TARDE")) return "tarde";
  if (turno.includes("MAÑANA") || turno.includes("MANANA")) return "manana";

  const horaInicio = normalizarHora(item.HoraInicio);
  const hora = Number(horaInicio.split(":")[0] || 0);

  return hora >= 12 ? "tarde" : "manana";
}

function actualizarPieTabla(total) {
  const fechaSeleccionadaTexto = obtenerTextoRangoSeleccionado();

  if (estadoCarga) {
    estadoCarga.innerHTML = `
      <i class="fa-solid fa-list"></i>
      Mostrando ${total} consultorio${total === 1 ? "" : "s"} · ${fechaSeleccionadaTexto}
    `;
  }

  if (actualizadoTabla) {
    actualizadoTabla.innerHTML = `
      <i class="fa-solid fa-rotate"></i>
      Actualizado hace un momento
    `;
  }
}

/* =========================
   CATÁLOGOS
========================= */

async function cargarCatalogos() {
  await cargarEspecialidades();
  await cargarServicios();
  await cargarMedicos();
}

async function cargarEspecialidades() {
  if (!idEspecialidadSelect) return;

  try {
    sincronizarFechaSeleccionada();

    const fechaSeleccionada = obtenerFechaSeleccionada();
    const params = new URLSearchParams();

    params.append("fechaInicio", fechaSeleccionada);
    params.append("fechaFin", fechaSeleccionada);

    const response = await fetch(
      `${API_URL}/api/especialidades-disponibles?${params.toString()}`
    );

    const result = await response.json();

    if (!response.ok || !result.ok) {
      console.error("Error al cargar especialidades:", result);
      return;
    }

    const valorActual = idEspecialidadSelect.value;

    idEspecialidadSelect.innerHTML = `<option value="">Todas las especialidades</option>`;

    const especialidadesOrdenadas = [...(result.data || [])].sort((a, b) => {
      const nombreA = String(a.Nombre || a.Especialidad || "").trim();
      const nombreB = String(b.Nombre || b.Especialidad || "").trim();

      return nombreA.localeCompare(nombreB, "es");
    });

    especialidadesOrdenadas.forEach((item) => {
      const nombre = String(item.Nombre || item.Especialidad || "").trim();

      if (!nombre) return;

      const option = document.createElement("option");

      option.value =
        item.ValorFiltro ||
        (item.IdEspecialidad ? `esp:${item.IdEspecialidad}` : "");

      option.textContent = nombre;

      idEspecialidadSelect.appendChild(option);
    });

    if (valorActual) {
      idEspecialidadSelect.value = valorActual;
    }

    console.log("Especialidades programadas cargadas:", especialidadesOrdenadas.length);
  } catch (error) {
    console.error("Error cargando especialidades:", error);
  }
}

function obtenerFiltroCatalogoSeleccionado() {
  const valor = idEspecialidadSelect?.value || "";

  const filtro = {
    idDepartamento: "",
    idEspecialidad: "",
  };

  if (!valor) {
    return filtro;
  }

  if (valor.startsWith("dep:")) {
    filtro.idDepartamento = valor.replace("dep:", "");
    return filtro;
  }

  if (valor.startsWith("esp:")) {
    filtro.idEspecialidad = valor.replace("esp:", "");
    return filtro;
  }

  filtro.idEspecialidad = valor;
  return filtro;
}

async function cargarServicios() {
  if (!idServicioSelect) return;

  try {
    idServicioSelect.innerHTML = `<option value="">Cargando servicios...</option>`;
    idServicioSelect.disabled = true;

    const filtroCatalogo = obtenerFiltroCatalogoSeleccionado();
    const params = new URLSearchParams();

    if (filtroCatalogo.idEspecialidad) {
      params.append("idEspecialidad", filtroCatalogo.idEspecialidad);
    }

    if (filtroCatalogo.idDepartamento) {
      params.append("idDepartamento", filtroCatalogo.idDepartamento);
    }

    let url = `${API_URL}/api/servicios`;

    if (params.toString()) {
      url += `?${params.toString()}`;
    }

    const response = await fetch(url);
    const result = await response.json();

    if (!response.ok || !result.ok) {
      console.error("Error cargando servicios:", result);

      idServicioSelect.innerHTML = `<option value="">Todos los servicios</option>`;
      idServicioSelect.disabled = false;

      return;
    }

    idServicioSelect.innerHTML = `<option value="">Todos los servicios</option>`;

    (result.data || []).forEach((servicio) => {
      const option = document.createElement("option");

      option.value = servicio.IdServicio;
      option.textContent = servicio.Nombre;

      idServicioSelect.appendChild(option);
    });

    idServicioSelect.disabled = false;
  } catch (error) {
    console.error("Error cargando servicios:", error);

    idServicioSelect.innerHTML = `<option value="">Error al cargar</option>`;
    idServicioSelect.disabled = false;
  }
}

async function cargarMedicos() {
  if (!idMedicoSelect) return;

  try {
    idMedicoSelect.innerHTML = `<option value="">Cargando médicos...</option>`;
    idMedicoSelect.disabled = true;

    const filtroCatalogo = obtenerFiltroCatalogoSeleccionado();
    const params = new URLSearchParams();

    if (filtroCatalogo.idEspecialidad) {
      params.append("idEspecialidad", filtroCatalogo.idEspecialidad);
    }

    if (filtroCatalogo.idDepartamento) {
      params.append("idDepartamento", filtroCatalogo.idDepartamento);
    }

    if (idServicioSelect?.value) {
      params.append("idServicio", idServicioSelect.value);
    }

    let url = `${API_URL}/api/medicos`;

    if (params.toString()) {
      url += `?${params.toString()}`;
    }

    const response = await fetch(url);
    const result = await response.json();

    if (!response.ok || !result.ok) {
      console.error("Error cargando médicos:", result);

      idMedicoSelect.innerHTML = `<option value="">Todos los médicos</option>`;
      idMedicoSelect.disabled = false;

      return;
    }

    idMedicoSelect.innerHTML = `<option value="">Todos los médicos</option>`;

    (result.data || []).forEach((medico) => {
      const option = document.createElement("option");

      option.value = medico.IdMedico;
      option.textContent = medico.Nombre;

      idMedicoSelect.appendChild(option);
    });

    idMedicoSelect.disabled = false;
  } catch (error) {
    console.error("Error cargando médicos:", error);

    idMedicoSelect.innerHTML = `<option value="">Error al cargar</option>`;
    idMedicoSelect.disabled = false;
  }
}

/* =========================
   DISPONIBILIDAD
========================= */

async function buscarDisponibilidad() {
  try {
    sincronizarFechaSeleccionada();
    actualizarFechaHeader();
    mostrarAlertaHorarioEspecialidad();

    if (estadoCarga) {
      estadoCarga.innerHTML = `
        <i class="fa-solid fa-spinner fa-spin"></i>
        Cargando disponibilidad...
      `;
    }

    const fechaSeleccionada = obtenerFechaSeleccionada();
    const params = new URLSearchParams();

    /*
      IMPORTANTE:
      Se manda la misma fecha en fechaInicio y fechaFin.
      Así el backend solo busca disponibilidad de ese día.
    */
    params.append("fechaInicio", fechaSeleccionada);
    params.append("fechaFin", fechaSeleccionada);

    const filtroCatalogo = obtenerFiltroCatalogoSeleccionado();

    if (filtroCatalogo.idEspecialidad) {
      params.append("idEspecialidad", filtroCatalogo.idEspecialidad);
    }

    if (filtroCatalogo.idDepartamento) {
      params.append("idDepartamento", filtroCatalogo.idDepartamento);
    }

    if (idServicioSelect?.value) {
      params.append("idServicio", idServicioSelect.value);
    }

    if (idMedicoSelect?.value) {
      params.append("idMedico", idMedicoSelect.value);
    }

    console.log("Consultando disponibilidad:", params.toString());

    const response = await fetch(
      `${API_URL}/api/disponibilidad?${params.toString()}`
    );

    const result = await response.json();

    if (!response.ok || !result.ok) {
      if (estadoCarga) {
        estadoCarga.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Error al cargar`;
      }

      mostrarError(result.detalle || result.error || "No se pudo cargar la información.");
      limpiarResumen();

      return;
    }

    disponibilidadActual = ordenarDisponibilidad(result.data || []);
    aplicarFiltrosVista();
  } catch (error) {
    if (estadoCarga) {
      estadoCarga.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Error de conexión`;
    }

    mostrarError(error.message);
    limpiarResumen();
  }
}

function ordenarDisponibilidad(data) {
  return [...data].sort((a, b) => {
    const fechaA = obtenerValorFecha(a.Fecha);
    const fechaB = obtenerValorFecha(b.Fecha);

    if (fechaA !== fechaB) {
      return fechaA - fechaB;
    }

    const horaA = normalizarHora(a.HoraInicio);
    const horaB = normalizarHora(b.HoraInicio);

    if (horaA !== horaB) {
      return horaA.localeCompare(horaB);
    }

    const especialidadA = String(a.Especialidad || "");
    const especialidadB = String(b.Especialidad || "");

    if (especialidadA !== especialidadB) {
      return especialidadA.localeCompare(especialidadB, "es");
    }

    const servicioA = String(a.Servicio || "");
    const servicioB = String(b.Servicio || "");

    if (servicioA !== servicioB) {
      return servicioA.localeCompare(servicioB, "es");
    }

    return Number(a.IdMedico || 0) - Number(b.IdMedico || 0);
  });
}

function pintarTabla(data) {
  if (!tablaDisponibilidad) return;

  tablaDisponibilidad.innerHTML = "";

  if (!data || data.length === 0) {
    tablaDisponibilidad.innerHTML = `
      <tr>
        <td colspan="6" class="sin-datos">
          No se encontraron consultorios para la fecha seleccionada.
        </td>
      </tr>
    `;

    return;
  }

  data.forEach((item) => {
    const disponibles = Number(item.CuposDisponibles || 0);
    const horaInicio = normalizarHora(item.HoraInicio);
    const horaFin = normalizarHora(item.HoraFin);

    let claseBadge = "verde";
    let textoBadge = disponibles;
    let textoExtra = "";

    if (disponibles <= 0) {
      claseBadge = "rojo";
      textoBadge = "Sin Cita";
    } else if (disponibles <= 2) {
      claseBadge = "amarillo";
      textoExtra = `<span class="badge-ultimos">Últimos</span>`;
    } else if (disponibles <= 4) {
      claseBadge = "verde";
    }

    const accionPrincipal =
      disponibles > 0
        ? `<a class="btn-agendar" href="sala-espera"><i class="fa-solid fa-pen-to-square"></i> Agendar</a>`
        : `<a class="btn-lista" href="sala-espera"><i class="fa-solid fa-hourglass-half"></i> Lista</a>`;

    const tr = document.createElement("tr");

    tr.innerHTML = `
      <td>
        <span class="fecha-tabla">
          <i class="fa-regular fa-calendar"></i>
          ${formatearFechaTabla(item.Fecha)}
        </span>
      </td>

      <td>
        <strong class="servicio-nombre">${formatearTextoTitulo(item.Servicio || "Sin servicio")}</strong>
        <span class="servicio-meta">
          <i class="fa-solid fa-building"></i>
          ${formatearTextoTitulo(item.Departamento || "")}${item.Departamento && item.Especialidad ? " · " : ""}${formatearTextoTitulo(item.Especialidad || "")}
        </span>
      </td>

      <td>
        <span class="medico-nombre">${formatearNombreMedico(item.Medico)}</span>
      </td>

      <td>
        <span class="horario-chip">
          <i class="fa-regular fa-clock"></i>
          ${horaInicio} - ${horaFin}
        </span>
      </td>

      <td>
        <span class="badge ${claseBadge}">${textoBadge}</span>
        ${textoExtra}
      </td>

      <td>
        <div class="acciones-tabla">
          <button
            type="button"
            class="btn-mostrar"
            onclick="verDetalleProgramacion(${Number(item.IdProgramacion || 0)})"
          >
            <i class="fa-regular fa-eye"></i>
            Mostrar
          </button>

          ${accionPrincipal}
        </div>
      </td>
    `;

    tablaDisponibilidad.appendChild(tr);
  });
}

function pintarResumen(data) {
  const totalProg = data.length;

  const cupos = data.reduce((acc, item) => {
    return acc + Number(item.CuposProgramados || 0);
  }, 0);

  const otorgadas = data.reduce((acc, item) => {
    return acc + Number(item.CitasOtorgadas || 0);
  }, 0);

  const disponibles = data.reduce((acc, item) => {
    return acc + Number(item.CuposDisponibles || 0);
  }, 0);

  if (totalProgramaciones) totalProgramaciones.textContent = totalProg;
  if (totalCupos) totalCupos.textContent = cupos;
  if (totalOtorgadas) totalOtorgadas.textContent = otorgadas;
  if (totalDisponibles) totalDisponibles.textContent = disponibles;
}

function limpiarResumen() {
  if (totalProgramaciones) totalProgramaciones.textContent = "0";
  if (totalCupos) totalCupos.textContent = "0";
  if (totalOtorgadas) totalOtorgadas.textContent = "0";
  if (totalDisponibles) totalDisponibles.textContent = "0";
}

function mostrarError(mensaje) {
  if (!tablaDisponibilidad) return;

  tablaDisponibilidad.innerHTML = `
    <tr>
      <td colspan="6" class="sin-datos">
        Error: ${escaparHtml(mensaje || "No se pudo cargar la información.")}
      </td>
    </tr>
  `;
}

/* =========================
   UTILIDADES
========================= */

function obtenerValorFecha(fecha) {
  if (!fecha) return 0;

  const fechaTexto = String(fecha).split("T")[0];
  const partes = fechaTexto.split("-");

  if (partes.length !== 3) return 0;

  const yyyy = Number(partes[0]);
  const mm = Number(partes[1]) - 1;
  const dd = Number(partes[2]);

  return new Date(yyyy, mm, dd).getTime();
}

function formatearFecha(fecha) {
  if (!fecha) return "";

  return String(fecha).split("T")[0];
}

function formatearFechaTabla(fecha) {
  const fechaISO = formatearFecha(fecha);

  if (!fechaISO) return "";

  const partes = fechaISO.split("-");

  if (partes.length !== 3) return fechaISO;

  return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function formatearTextoTitulo(valor) {
  let texto = String(valor || "").trim();

  if (!texto) return "";

  texto = texto
    .replace(/_/g, " ")
    .replace(/\s+/g, " ")
    .replace(/\s*\/\s*/g, " / ")
    .replace(/\s*-\s*/g, " - ")
    .replace(/\s*\.\s*/g, ". ")
    .trim();

  texto = texto.toLocaleLowerCase("es-PE");

  texto = texto.replace(/(^|[\s./()-])([a-záéíóúüñ])/giu, function (
    coincidencia,
    separador,
    letra
  ) {
    return separador + letra.toLocaleUpperCase("es-PE");
  });

  texto = texto
    .replace(/\bCred\b/g, "CRED")
    .replace(/\bEsn\b/g, "ESN")
    .replace(/\bMfr\b/g, "MFR")
    .replace(/\bHta\b/g, "HTA")
    .replace(/\bDm\b/g, "DM")
    .replace(/\bVih\b/g, "VIH")
    .replace(/\bSida\b/g, "SIDA")
    .replace(/\bTbc\b/g, "TBC")
    .replace(/\bIi\b/g, "II")
    .replace(/\bIii\b/g, "III")
    .replace(/\bIv\b/g, "IV")
    .replace(/\bVi\b/g, "VI")
    .replace(/\bVii\b/g, "VII")
    .replace(/\bViii\b/g, "VIII")
    .replace(/\bIx\b/g, "IX")
    .replace(/\bX\b/g, "X")
    .replace(/\bY\s*\/\s*O\b/gi, "y/o");

  texto = texto
    .replace(/\bCirugia\b/gi, "Cirugía")
    .replace(/\bCardiologia\b/gi, "Cardiología")
    .replace(/\bGinecologia\b/gi, "Ginecología")
    .replace(/\bNeurologia\b/gi, "Neurología")
    .replace(/\bNeumologia\b/gi, "Neumología")
    .replace(/\bUrologia\b/gi, "Urología")
    .replace(/\bNefrologia\b/gi, "Nefrología")
    .replace(/\bPsiquiatria\b/gi, "Psiquiatría")
    .replace(/\bPediatria\b/gi, "Pediatría")
    .replace(/\bOftalmologia\b/gi, "Oftalmología")
    .replace(/\bOncologia\b/gi, "Oncología")
    .replace(/\bTraumatologia\b/gi, "Traumatología")
    .replace(/\bGastroenterologia\b/gi, "Gastroenterología")
    .replace(/\bEndocrinologia\b/gi, "Endocrinología")
    .replace(/\bRehabilitacion\b/gi, "Rehabilitación")
    .replace(/\bDiagnostico\b/gi, "Diagnóstico")
    .replace(/\bCancer\b/gi, "Cáncer")
    .replace(/\bFisica\b/gi, "Física")
    .replace(/\bPlastica\b/gi, "Plástica")
    .replace(/\bNiño\b/gi, "Niño")
    .replace(/\bNiña\b/gi, "Niña")
    .replace(/([a-záéíóúüñ])(\d)/giu, "$1 $2");

  return escaparHtml(texto);
}

function normalizarHora(hora) {
  if (!hora) return "";

  const texto = String(hora);

  if (texto.includes("T")) {
    return texto.split("T")[1].substring(0, 5);
  }

  return texto.substring(0, 5);
}

function normalizarTexto(texto) {
  return String(texto || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toUpperCase()
    .trim();
}

function escaparHtml(valor) {
  return String(valor ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

function formatearNombreMedico(nombre) {
  const texto = String(nombre || "").trim();

  if (!texto) return "Sin médico asignado";

  return escaparHtml(
    texto
      .toLocaleLowerCase("es-PE")
      .replace(/\b\p{L}/gu, (letra) => letra.toLocaleUpperCase("es-PE"))
  );
}

/* =========================
   DETALLE
========================= */

function verDetalleProgramacion(idProgramacion) {
  const item = disponibilidadActual.find((x) => {
    return Number(x.IdProgramacion) === Number(idProgramacion);
  });

  if (!item) {
    alert("No se encontró el detalle de esta programación.");
    return;
  }

  const disponibles = Number(item.CuposDisponibles || 0);
  const cupos = Number(item.CuposProgramados || 0);
  const otorgadas = Number(item.CitasOtorgadas || 0);
  const bloqueadas = Number(item.CitasBloqueadas || 0);

  const fecha = formatearFecha(item.Fecha);
  const horaInicio = normalizarHora(item.HoraInicio);
  const horaFin = normalizarHora(item.HoraFin);

  let estadoTexto = "Hay cupos disponibles";
  let estadoClase = "detalle-ok";

  if (disponibles <= 0) {
    estadoTexto = "No hay cupos disponibles";
    estadoClase = "detalle-no";
  } else if (disponibles <= 2) {
    estadoTexto = "Quedan pocos cupos";
    estadoClase = "detalle-alerta";
  }

  if (!modalDetalle || !contenidoDetalle) {
    alert(
      estadoTexto +
        "\n\nFecha: " +
        fecha +
        "\nDepartamento: " +
        (item.Departamento || "") +
        "\nEspecialidad: " +
        (item.Especialidad || "") +
        "\nServicio: " +
        (item.Servicio || "") +
        "\nMédico: " +
        (item.Medico || "") +
        "\nTurno: " +
        (item.Turno || "") +
        "\nHorario: " +
        horaInicio +
        " - " +
        horaFin +
        "\nCupos programados: " +
        cupos +
        "\nCitas otorgadas: " +
        otorgadas +
        "\nCupos disponibles: " +
        disponibles
    );

    return;
  }

  contenidoDetalle.innerHTML = `
    <div class="detalle-estado ${estadoClase}">
      ${estadoTexto}
    </div>

    <div class="detalle-item">
      <span>Fecha</span>
      <strong>${escaparHtml(fecha)}</strong>
    </div>

    <div class="detalle-item">
      <span>Consultorio / Servicio</span>
      <strong>${escaparHtml(item.Servicio || "")}</strong>
    </div>

    <div class="detalle-item">
      <span>Especialidad</span>
      <strong>${escaparHtml(item.Especialidad || "")}</strong>
    </div>

    <div class="detalle-item">
      <span>Médico(a)</span>
      <strong>${formatearNombreMedico(item.Medico)}</strong>
    </div>

    <div class="detalle-item">
      <span>Turno</span>
      <strong>${escaparHtml(item.Turno || "")}</strong>
    </div>

    <div class="detalle-item">
      <span>Horario</span>
      <strong>${horaInicio} - ${horaFin}</strong>
    </div>

    <div class="detalle-item">
      <span>Cupos programados</span>
      <strong>${cupos}</strong>
    </div>

    <div class="detalle-item">
      <span>Citas otorgadas</span>
      <strong>${otorgadas}</strong>
    </div>

    <div class="detalle-item">
      <span>Citas bloqueadas</span>
      <strong>${bloqueadas}</strong>
    </div>

    <div class="detalle-item importante">
      <span>Citas disponibles</span>
      <strong>${disponibles}</strong>
    </div>
  `;

  modalDetalle.classList.remove("oculto");
}

function cerrarModalDetalle() {
  if (modalDetalle) {
    modalDetalle.classList.add("oculto");
  }
}

/* =========================
   ALERTA DE HORARIOS POR ESPECIALIDAD
========================= */

function obtenerNombreEspecialidadSeleccionada() {
  if (!idEspecialidadSelect) return "";

  const opcionSeleccionada =
    idEspecialidadSelect.options[idEspecialidadSelect.selectedIndex];

  if (!opcionSeleccionada) return "";

  return String(opcionSeleccionada.textContent || "")
    .split(" - Dep.")[0]
    .trim();
}

function obtenerHorarioPorEspecialidad(nombreEspecialidad) {
  const especialidad = normalizarTexto(nombreEspecialidad);

  const horariosPorEspecialidad = {
    MEDICINA: "Medicina atiende según la programación disponible del día.",

    "MEDICINA GENERAL":
      "Medicina general atiende principalmente los sábados.",

    "MEDICINA INTERNA":
      "Medicina interna atiende según la programación disponible del día.",

    "MEDICINA FISICA Y REHABILITACION":
      "Medicina física y rehabilitación atiende principalmente los sábados.",

    REHABILITACION:
      "Rehabilitación atiende principalmente los sábados.",

    "TRAUMATOLOGIA Y ORTOPEDIA":
      "Traumatología y Ortopedia atiende principalmente los martes y jueves.",

    GINECOLOGIA:
      "Ginecología atiende principalmente los martes y jueves.",

    CARDIOLOGIA:
      "Cardiología atiende principalmente los martes y jueves.",

    "TERAPIA FISICA":
      "Terapia física atiende según la programación disponible de la semana.",

    "TERAPIA OCUPACIONAL":
      "Terapia ocupacional atiende según la programación disponible de la semana.",

    OFTALMOLOGIA:
      "Oftalmología atiende solo los jueves.",

    PSIQUIATRIA:
      "Psiquiatría atiende según la programación disponible de la semana.",

    CRED:
      "CRED atiende según la programación disponible de la semana.",
  };

  return horariosPorEspecialidad[especialidad] || "";
}

function mostrarAlertaHorarioEspecialidad() {
  const nombreEspecialidad = obtenerNombreEspecialidadSeleccionada();

  if (!idEspecialidadSelect?.value || !nombreEspecialidad) {
    cerrarAlertaHorario();
    return;
  }

  const mensaje = obtenerHorarioPorEspecialidad(nombreEspecialidad);

  if (!mensaje || !alertaHorario || !alertaHorarioTitulo || !alertaHorarioMensaje) {
    cerrarAlertaHorario();
    return;
  }

  alertaHorarioTitulo.textContent = `Horario de ${nombreEspecialidad}`;
  alertaHorarioMensaje.textContent = mensaje;

  if (fondoAlertaHorario) {
    fondoAlertaHorario.classList.remove("oculto");
  }

  alertaHorario.classList.remove("oculto");

  if (temporizadorAlertaHorario) {
    clearTimeout(temporizadorAlertaHorario);
  }

  temporizadorAlertaHorario = setTimeout(() => {
    cerrarAlertaHorario();
  }, 6500);
}

function cerrarAlertaHorario() {
  if (alertaHorario) {
    alertaHorario.classList.add("oculto");
  }

  if (fondoAlertaHorario) {
    fondoAlertaHorario.classList.add("oculto");
  }

  if (temporizadorAlertaHorario) {
    clearTimeout(temporizadorAlertaHorario);
    temporizadorAlertaHorario = null;
  }
}

/* =========================
   CHATBOT DE CITAS
========================= */

const chatbotHospital = document.getElementById("chatbotHospital");
const btnAbrirChatbot = document.getElementById("btnAbrirChatbot");
const btnCerrarChatbot = document.getElementById("btnCerrarChatbot");
const chatbotMensajes = document.getElementById("chatbotMensajes");
const chatbotInput = document.getElementById("chatbotInput");
const btnEnviarChatbot = document.getElementById("btnEnviarChatbot");

if (btnAbrirChatbot && chatbotHospital && chatbotInput) {
  btnAbrirChatbot.addEventListener("click", () => {
    chatbotHospital.classList.remove("oculto");
    chatbotInput.focus();
  });
}

if (btnCerrarChatbot && chatbotHospital) {
  btnCerrarChatbot.addEventListener("click", () => {
    chatbotHospital.classList.add("oculto");
  });
}

if (btnEnviarChatbot) {
  btnEnviarChatbot.addEventListener("click", enviarMensajeChatbot);
}

if (chatbotInput) {
  chatbotInput.addEventListener("keydown", (event) => {
    if (event.key === "Enter") {
      enviarMensajeChatbot();
    }
  });
}

document.querySelectorAll(".chatbot-sugerencia").forEach((boton) => {
  boton.addEventListener("click", () => {
    if (!chatbotInput) return;

    chatbotInput.value = boton.textContent.trim();
    enviarMensajeChatbot();
  });
});

function agregarMensajeChatbot(texto, tipo = "bot") {
  if (!chatbotMensajes) return null;

  const mensaje = document.createElement("div");

  mensaje.className = `chatbot-msg ${tipo}`;
  mensaje.textContent = texto;

  chatbotMensajes.appendChild(mensaje);
  chatbotMensajes.scrollTop = chatbotMensajes.scrollHeight;

  return mensaje;
}

async function enviarMensajeChatbot() {
  if (!chatbotInput) return;

  const texto = chatbotInput.value.trim();

  if (!texto) {
    return;
  }

  agregarMensajeChatbot(texto, "user");

  chatbotInput.value = "";

  const mensajeCargando = agregarMensajeChatbot(
    "Consultando disponibilidad...",
    "bot cargando"
  );

  try {
    const response = await fetch(
      `${API_URL}/api/chatbot-citas?mensaje=${encodeURIComponent(texto)}`
    );

    const result = await response.json();

    if (mensajeCargando) mensajeCargando.remove();

    if (!response.ok || !result.ok) {
      agregarMensajeChatbot(
        result.error || "No pude procesar tu consulta.",
        "bot"
      );

      return;
    }

    agregarMensajeChatbot(result.respuesta, "bot");
  } catch (error) {
    if (mensajeCargando) mensajeCargando.remove();

    agregarMensajeChatbot(
      "No pude conectarme con el servidor. Verifica que el backend esté ejecutándose.",
      "bot"
    );
  }
}