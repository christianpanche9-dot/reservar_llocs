const calendario = document.querySelector('#calendario');
const tituloMes = document.querySelector('#titulo-mes');
const estado = document.querySelector('#estado');
const detalle = document.querySelector('#detalle');
const filtroActividad = document.querySelector('#filtro-actividad');
const hoy = new Date();
let fechaVisible = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
let sesionesActuales = [];
let controlador = null;

function fechaLocal(fecha) {
const anio = fecha.getFullYear();
const mes = String(fecha.getMonth() + 1).padStart(2, '0');
const dia = String(fecha.getDate()).padStart(2, '0');
return `${anio}-${mes}-${dia}`;
}

function obtenerIntervalo() {
const primeroMes = new Date(
fechaVisible.getFullYear(),
fechaVisible.getMonth(),
1
);
const desplazamiento = (primeroMes.getDay() + 6) % 7;
const inicio = new Date(primeroMes);
inicio.setDate(inicio.getDate() - desplazamiento);
const fin = new Date(inicio);
fin.setDate(fin.getDate() + 41);
return { inicio, fin };
}

function crearUrl(inicio, fin) {
const parametros = new URLSearchParams({
inicio: fechaLocal(inicio),
fin: fechaLocal(fin)
});
if (filtroActividad.value) {
parametros.set('id_actividad', filtroActividad.value);
}
return `api_sesiones.php?${parametros.toString()}`;
}

async function cargarSesiones() {
const { inicio, fin } = obtenerIntervalo();
if (controlador) {
controlador.abort();
}
controlador = new AbortController();
estado.textContent = 'Cargando sesiones…';
try {
const respuesta = await fetch(crearUrl(inicio, fin), {
signal: controlador.signal,
headers: { Accept: 'application/json' }
});
const datos = await respuesta.json();
if (!respuesta.ok || !datos.ok) {
throw new Error(datos.error || 'No se pudo cargar el calendario.');
}
sesionesActuales = datos.sesiones;
dibujarCalendario(inicio);
estado.textContent = `${datos.total} sesiones encontradas.`;
} catch (error) {
if (error.name === 'AbortError') return;
sesionesActuales = [];
calendario.replaceChildren();
estado.textContent = error.message;
}
}

function dibujarCalendario(inicio) {
calendario.replaceChildren();
detalle.hidden = true;
tituloMes.textContent = fechaVisible.toLocaleDateString('es-ES', {
month: 'long',
year: 'numeric'
});
for (let posicion = 0; posicion < 42; posicion++) {
const fecha = new Date(inicio);
fecha.setDate(inicio.getDate() + posicion);
calendario.append(crearDia(fecha));
}
}
function crearDia(fecha) {
const celda = document.createElement('article');
celda.className = 'dia';
if (fecha.getMonth() !== fechaVisible.getMonth()) {
celda.classList.add('dia--fuera');
}
if (fechaLocal(fecha) === fechaLocal(hoy)) {
celda.classList.add('dia--hoy');
}
const numero = document.createElement('strong');
numero.textContent = fecha.getDate();
celda.append(numero);
const clave = fechaLocal(fecha);
const sesionesDia = sesionesActuales.filter(
sesion => sesion.fecha === clave
);
for (const sesion of sesionesDia) {
celda.append(crearBotonSesion(sesion));
}
return celda;
}

function crearBotonSesion(sesion) {
const boton = document.createElement('button');
boton.type = 'button';
boton.className = 'sesion';

if (sesion.completa) {
boton.classList.add('sesion--completa');
}
boton.textContent = `${sesion.inicio} · ${sesion.actividad}`;
boton.setAttribute(
'aria-label',
`${sesion.actividad}, ${sesion.inicio}, ${sesion.plazas} plazas`
);
boton.addEventListener('click', () => mostrarDetalle(sesion));
return boton;
}

function mostrarDetalle(sesion) {
detalle.replaceChildren();
const titulo = document.createElement('h2');
titulo.textContent = sesion.actividad;
const informacion = document.createElement('p');
informacion.textContent =
`${sesion.fecha} · ${sesion.inicio}-${sesion.fin} · ` +
`${sesion.espacio} · ${sesion.monitor}`;
const plazas = document.createElement('p');
plazas.textContent = sesion.completa
? 'Sesión completa: puedes solicitar lista de espera.'
: `Quedan ${sesion.plazas} plazas.`;
const enlace = document.createElement('a');
enlace.href = `detalle_sesion.php?id=${encodeURIComponent(sesion.id)}`;
enlace.textContent = sesion.completa
? 'Ver sesión y lista de espera'
: 'Ver sesión y reservar';
detalle.append(titulo, informacion, plazas, enlace);
detalle.hidden = false;
detalle.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
document.querySelector('#anterior').addEventListener('click', () => {
fechaVisible = new Date(
fechaVisible.getFullYear(),
fechaVisible.getMonth() - 1,
1
);
cargarSesiones();
});
document.querySelector('#siguiente').addEventListener('click', () => {
fechaVisible = new Date(
fechaVisible.getFullYear(),
fechaVisible.getMonth() + 1,
1
);
cargarSesiones();
});

filtroActividad.addEventListener('change', cargarSesiones);
cargarSesiones();