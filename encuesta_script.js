/**
 * Script para renderizar y manejar el envío de encuestas públicas.
 */

const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get('token');

let encuestaData = null;

// Ejecutar al cargar la página
document.addEventListener('DOMContentLoaded', init);

/**
 * Función inicial para validar token y cargar datos.
 */
function init() {
    if (!token) {
        mostrarError("Enlace de encuesta inválido. Falta el token.");
        return;
    }
    cargarEncuesta();
}

/**
 * Carga los datos de la encuesta desde la API.
 */
async function cargarEncuesta() {
    try {
        const respuesta = await fetch('api/encuestas/public_get_encuesta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ token: token })
        });
        
        const data = await respuesta.json();
        document.getElementById('cargando').style.display = 'none';

        if (data.success) {
            encuestaData = data;
            mostrarEncuesta(data);
        } else {
            mostrarError(data.error || "Error al cargar la encuesta.");
        }
    } catch (error) {
        mostrarError("Error de conexión al cargar la encuesta.");
        console.error(error);
    }
}

/**
 * Convierte texto con Markdown básico a HTML.
 * @param {string} text - Texto a formatear.
 * @returns {string} HTML formateado.
 */
function formatMarkdown(text) {
    if (!text) return '';
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/\n/g, '<br>');
}

/**
 * Muestra un mensaje de error.
 * @param {string} mensaje - Mensaje a mostrar.
 */
function mostrarError(mensaje) {
    document.getElementById('cargando').style.display = 'none';
    document.getElementById('encuesta-container').style.display = 'none';
    
    const errorContainer = document.getElementById('error-container');
    errorContainer.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i><br>${mensaje}`;
    errorContainer.style.display = 'block';
}

/**
 * Muestra la información de la encuesta y renderiza las preguntas.
 * @param {object} data - Datos de la encuesta.
 */
function mostrarEncuesta(data) {
    document.getElementById('enc-titulo').innerHTML = formatMarkdown(data.encuesta.titulo);
    document.getElementById('enc-desc').innerHTML = formatMarkdown(data.encuesta.descripcion);
    
    const btnEnviar = document.getElementById('btn-enviar');
    if (data.encuesta.tipo_encuesta === 'anonima' || !data.encuesta.tipo_encuesta) {
        btnEnviar.innerText = 'Enviar Respuestas de Forma Anónima';
    } else {
        btnEnviar.innerText = 'Enviar Respuestas';
    }

    const contenedorPreguntas = document.getElementById('preguntas-list');
    contenedorPreguntas.innerHTML = '';
    
    // Si la encuesta es nominativa, agregamos campos de identificación
    if (data.encuesta.tipo_encuesta === 'nominativa') {
        const identificacionHTML = `
            <div class="pregunta-card">
                <div class="pregunta-texto">Identificación del Participante</div>
                <div style="margin-bottom: 1rem;">
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">Nombre Completo:</label>
                    <input type="text" id="identificacion-nombre" class="input-texto" required>
                </div>
                <div>
                    <label style="display:block; margin-bottom: 0.5rem; font-weight: 500;">RUT:</label>
                    <input type="text" id="identificacion-rut" class="input-texto" required>
                </div>
            </div>
        `;
        contenedorPreguntas.innerHTML += identificacionHTML;
    }

    renderPreguntas(data.preguntas, contenedorPreguntas);

    document.getElementById('encuesta-container').style.display = 'block';

    const formulario = document.getElementById('form-respuestas');
    // Remover eventos anteriores clonando el nodo para evitar múltiples envíos
    const nuevoFormulario = formulario.cloneNode(true);
    formulario.parentNode.replaceChild(nuevoFormulario, formulario);
    
    nuevoFormulario.addEventListener('submit', procesarEnvio);
}

/**
 * Renderiza el listado de preguntas.
 * @param {Array} preguntas - Arreglo de preguntas.
 * @param {HTMLElement} contenedor - Elemento donde agregar las preguntas.
 */
function renderPreguntas(preguntas, contenedor) {
    let numeroPregunta = 1;

    preguntas.forEach(p => {
        if (p.tipo_pregunta === 'seccion') {
            const seccion = document.createElement('div');
            seccion.className = 'encabezado-seccion';
            seccion.innerHTML = formatMarkdown(p.texto_pregunta);
            contenedor.appendChild(seccion);
            return;
        }

        if (p.tipo_pregunta === 'descripcion_corta') {
            const descripcion = document.createElement('div');
            descripcion.className = 'descripcion-corta-seccion';
            descripcion.innerHTML = formatMarkdown(p.texto_pregunta);
            contenedor.appendChild(descripcion);
            return;
        }

        const card = document.createElement('div');
        card.className = 'pregunta-card';

        let html = `<div class="pregunta-texto">${numeroPregunta}. ${formatMarkdown(p.texto_pregunta)}</div>`;
        numeroPregunta++;

        switch (p.tipo_pregunta) {
            case 'texto':
            case 'parrafo':
                html += `<textarea id="resp-${p.id}" class="input-texto" required></textarea>`;
                break;
                
            case 'respuesta_corta':
                html += `<input type="text" id="resp-${p.id}" class="input-texto" required>`;
                break;
                
            case 'menu_desplegable':
                html += `
                    <select id="resp-${p.id}" class="input-texto" required>
                        <option value="">Seleccione una opción</option>
                        ${p.opciones.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                    </select>
                `;
                break;
                
            case 'opcion_multiple':
                html += p.opciones.map(opt => `
                    <label class="radio-opcion">
                        <input type="radio" name="resp-${p.id}" value="${opt}" required>
                        <span>${opt}</span>
                    </label>
                `).join('');
                
                if (p.incluye_otro) {
                    const labelOtro = p.label_otro || 'Otro';
                    html += `
                        <label class="radio-opcion">
                            <input type="radio" name="resp-${p.id}" value="OTRO" required>
                            <span>${labelOtro}:</span>
                            <input type="text" id="otro-texto-${p.id}" class="input-texto" onclick="document.querySelector('input[name=\\'resp-${p.id}\\'][value=\\'OTRO\\']').checked = true;" style="margin-left: 0.5rem; flex-grow: 1;">
                        </label>
                    `;
                }
                break;
                
            case 'seleccion_multiple':
                html += p.opciones.map(opt => `
                    <label class="radio-opcion">
                        <input type="checkbox" name="resp-${p.id}" value="${opt}">
                        <span>${opt}</span>
                    </label>
                `).join('');
                
                if (p.incluye_otro) {
                    const labelOtro = p.label_otro || 'Otro';
                    html += `
                        <label class="radio-opcion">
                            <input type="checkbox" name="resp-${p.id}" value="OTRO">
                            <span>${labelOtro}:</span>
                            <input type="text" id="otro-texto-${p.id}" class="input-texto" onclick="document.querySelector('input[name=\\'resp-${p.id}\\'][value=\\'OTRO\\']').checked = true;" style="margin-left: 0.5rem; flex-grow: 1;">
                        </label>
                    `;
                }
                break;
                
            case 'escala_lineal':
                const min = parseInt(p.opciones.min);
                const max = parseInt(p.opciones.max);
                const labelMin = p.opciones.label_min || '';
                const labelMax = p.opciones.label_max || '';
                
                let escalaOpciones = '';
                for (let i = min; i <= max; i++) {
                    escalaOpciones += `
                        <div style="display: flex; flex-direction: column; align-items: center; margin: 0 0.5rem;">
                            <span>${i}</span>
                            <input type="radio" name="resp-${p.id}" value="${i}" required style="margin-top: 0.5rem;">
                        </div>
                    `;
                }
                
                html += `
                    <div style="display: flex; align-items: center; justify-content: center; overflow-x: auto; padding: 1rem 0;">
                        ${labelMin ? `<span style="margin-right: 1rem;">${labelMin}</span>` : ''}
                        ${escalaOpciones}
                        ${labelMax ? `<span style="margin-left: 1rem;">${labelMax}</span>` : ''}
                    </div>
                `;
                break;
                
            case 'cuadricula':
            case 'cuadricula_checkbox':
                const typeInput = p.tipo_pregunta === 'cuadricula' ? 'radio' : 'checkbox';
                const cols = p.opciones.columnas || [];
                const filas = p.opciones.filas || [];
                
                let thead = `<tr><th></th>${cols.map(c => `<th style="padding: 0.5rem; text-align: center;">${c}</th>`).join('')}</tr>`;
                
                let tbody = filas.map((f, iFila) => `
                    <tr>
                        <td style="padding: 0.5rem;">${f}</td>
                        ${cols.map((c, iCol) => `
                            <td style="padding: 0.5rem; text-align: center;">
                                <input type="${typeInput}" name="resp-${p.id}-fila-${iFila}" value="${c}" ${typeInput === 'radio' ? 'required' : ''}>
                            </td>
                        `).join('')}
                    </tr>
                `).join('');
                
                html += `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>${thead}</thead>
                            <tbody>${tbody}</tbody>
                        </table>
                    </div>
                `;
                break;
        }

        if (p.incluye_justificacion && p.tipo_pregunta !== 'seccion' && p.tipo_pregunta !== 'descripcion_corta') {
            const labelJustificacion = p.label_justificacion || 'Justifique su respuesta';
            html += `
                <div style="margin-top: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">${labelJustificacion}</label>
                    <textarea id="justificacion-${p.id}" class="input-texto" rows="2"></textarea>
                </div>
            `;
        }

        card.innerHTML = html;
        contenedor.appendChild(card);
    });
}

/**
 * Procesa el envío del formulario de respuestas.
 * @param {Event} e - Evento de submit.
 */
async function procesarEnvio(e) {
    e.preventDefault();

    const btnEnviar = document.getElementById('btn-enviar');
    const textoOriginal = btnEnviar.innerText;
    btnEnviar.disabled = true;
    btnEnviar.innerHTML = 'Enviando...';

    let respuestas = [];
    let esValido = true;
    let identificacion = {};

    if (encuestaData.encuesta.tipo_encuesta === 'nominativa') {
        const nombreInput = document.getElementById('identificacion-nombre');
        const rutInput = document.getElementById('identificacion-rut');
        if (!nombreInput.value.trim() || !rutInput.value.trim()) {
            esValido = false;
        } else {
            identificacion = {
                nombre: nombreInput.value.trim(),
                rut: rutInput.value.trim()
            };
        }
    }

    encuestaData.preguntas.forEach(p => {
        if (p.tipo_pregunta === 'seccion' || p.tipo_pregunta === 'descripcion_corta') return;

        let valorRespuesta = null;

        switch (p.tipo_pregunta) {
            case 'texto':
            case 'parrafo':
            case 'respuesta_corta':
            case 'menu_desplegable':
                const input = document.getElementById(`resp-${p.id}`);
                valorRespuesta = input ? input.value.trim() : '';
                if (!valorRespuesta) esValido = false;
                break;

            case 'opcion_multiple':
            case 'escala_lineal':
                const seleccionadoRadio = document.querySelector(`input[name="resp-${p.id}"]:checked`);
                if (seleccionadoRadio) {
                    if (seleccionadoRadio.value === 'OTRO') {
                        const inputOtro = document.getElementById(`otro-texto-${p.id}`);
                        valorRespuesta = inputOtro ? inputOtro.value.trim() : '';
                        if (!valorRespuesta) esValido = false;
                    } else {
                        valorRespuesta = seleccionadoRadio.value;
                    }
                } else {
                    esValido = false;
                }
                break;

            case 'seleccion_multiple':
                const seleccionadosCheck = document.querySelectorAll(`input[name="resp-${p.id}"]:checked`);
                if (seleccionadosCheck.length > 0) {
                    let valores = [];
                    seleccionadosCheck.forEach(chk => {
                        if (chk.value === 'OTRO') {
                            const inputOtro = document.getElementById(`otro-texto-${p.id}`);
                            const valOtro = inputOtro ? inputOtro.value.trim() : '';
                            if (valOtro) valores.push(valOtro);
                        } else {
                            valores.push(chk.value);
                        }
                    });
                    
                    if (valores.length === 0) {
                        esValido = false;
                    } else {
                        valorRespuesta = valores.join('; ');
                    }
                } else {
                    esValido = false;
                }
                break;

            case 'cuadricula':
            case 'cuadricula_checkbox':
                const filas = p.opciones.filas || [];
                let matrizValores = {};
                
                filas.forEach((fila, iFila) => {
                    const selector = `input[name="resp-${p.id}-fila-${iFila}"]:checked`;
                    if (p.tipo_pregunta === 'cuadricula') {
                        const seleccionado = document.querySelector(selector);
                        if (seleccionado) {
                            matrizValores[fila] = seleccionado.value;
                        } else {
                            esValido = false;
                        }
                    } else {
                        const seleccionados = document.querySelectorAll(selector);
                        if (seleccionados.length > 0) {
                            matrizValores[fila] = Array.from(seleccionados).map(c => c.value);
                        } else {
                            esValido = false;
                        }
                    }
                });
                
                if (Object.keys(matrizValores).length > 0) {
                    valorRespuesta = JSON.stringify(matrizValores);
                }
                break;
        }

        if (valorRespuesta !== null && valorRespuesta !== '') {
            if (p.incluye_justificacion) {
                const justificacionInput = document.getElementById(`justificacion-${p.id}`);
                const textoJustificacion = justificacionInput ? justificacionInput.value.trim() : '';
                if (textoJustificacion) {
                    valorRespuesta += `\nJustificación: ${textoJustificacion}`;
                }
            }

            respuestas.push({
                pregunta_id: p.id,
                valor: valorRespuesta
            });
        }
    });

    if (!esValido) {
        alert("Por favor, responda todas las preguntas obligatorias.");
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = textoOriginal;
        return;
    }

    try {
        const peticion = await fetch('api/encuestas/public_submit_respuesta.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                respuestas: respuestas,
                identificacion: encuestaData.encuesta.tipo_encuesta === 'nominativa' ? identificacion : null
            })
        });

        const data = await peticion.json();

        if (data.success) {
            document.getElementById('encuesta-container').style.display = 'none';
            document.getElementById('success-container').style.display = 'block';
            
            const msgExito = document.getElementById('msg-exito');
            if (encuestaData.encuesta.tipo_encuesta === 'anonima' || !encuestaData.encuesta.tipo_encuesta) {
                msgExito.innerText = 'Tus respuestas han sido enviadas de forma 100% anónima.';
            } else {
                msgExito.innerText = 'Tus respuestas han sido registradas exitosamente.';
            }
            
            window.scrollTo(0, 0);
        } else {
            alert(data.error || "Error al enviar las respuestas.");
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = textoOriginal;
        }
    } catch (error) {
        alert("Error de conexión al enviar las respuestas.");
        console.error(error);
        btnEnviar.disabled = false;
        btnEnviar.innerHTML = textoOriginal;
    }
}
