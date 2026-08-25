let preguntaCounter = 0;
let editPreguntaCounter = 0;
let isEditQuestionsBlocked = false;

// Markdown Toolbar Helper
function insertMarkdown(inputId, prefix, suffix) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = input.value;
    const selectedText = text.substring(start, end);
    const replacement = `${prefix}${selectedText}${suffix}`;
    input.value = text.substring(0, start) + replacement + text.substring(end);
    input.focus();
    input.setSelectionRange(start + prefix.length, start + prefix.length + selectedText.length);
}

function insertMarkdownHelper(inputId) {
    const input = document.getElementById(inputId);
    if (!input || input.disabled) return;
    if (input.parentElement && input.parentElement.classList.contains('md-wrapper')) return;
    
    const wrapper = document.createElement('div');
    wrapper.className = 'md-wrapper';
    wrapper.style.marginBottom = input.style.marginBottom;
    input.style.marginBottom = '0';
    
    const toolbar = document.createElement('div');
    toolbar.className = 'markdown-toolbar';
    toolbar.innerHTML = `
        <button type="button" class="btn-md" onclick="insertMarkdown('${inputId}', '**', '**')" title="Negrita"><i class="fa-solid fa-bold"></i></button>
        <button type="button" class="btn-md" onclick="insertMarkdown('${inputId}', '*', '*')" title="Cursiva"><i class="fa-solid fa-italic"></i></button>
        <div class="md-divider"></div>
        <button type="button" class="btn-md" onclick="insertMarkdown('${inputId}', '# ', '')" title="Título 1">H1</button>
        <button type="button" class="btn-md" onclick="insertMarkdown('${inputId}', '## ', '')" title="Título 2">H2</button>
        <button type="button" class="btn-md" onclick="insertMarkdown('${inputId}', '### ', '')" title="Título 3">H3</button>
    `;
    
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(toolbar);
    wrapper.appendChild(input);
    input.style.borderTopLeftRadius = '0';
    input.style.borderTopRightRadius = '0';
}

// ----------------------------------------------------
// CREATE FORM FUNCTIONS
// ----------------------------------------------------
function addPregunta(texto = '', tipo = 'texto', opciones = [], incluyeOtro = false, incluyeJustif = false, labelOtro = 'Otro', labelJustif = 'Justifique su respuesta') {
    preguntaCounter++;
    const id = preguntaCounter;
    
    const div = document.createElement('div');
    div.className = 'pregunta-item';
    div.id = `preg-${id}`;
    
    div.innerHTML = `
        <div class="preg-actions">
            <button type="button" class="btn-preg-action danger" onclick="removePregunta(${id})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
        </div>
        <input type="text" class="form-control" id="texto-${id}" value="${texto}" placeholder="Escribe la pregunta..." required style="width: 80%; margin-top: 5px;">
        <select class="form-control" id="tipo-${id}" onchange="toggleOpciones(${id})" style="margin-bottom: 0;">
            <option value="texto" ${tipo === 'texto' ? 'selected' : ''}>Texto Libre (Párrafo)</option>
            <option value="respuesta_corta" ${tipo === 'respuesta_corta' ? 'selected' : ''}>Respuesta Corta</option>
            <option value="opcion_multiple" ${tipo === 'opcion_multiple' ? 'selected' : ''}>Opción Múltiple (Radio)</option>
            <option value="seleccion_multiple" ${tipo === 'seleccion_multiple' ? 'selected' : ''}>Casillas de verificación (Checkbox)</option>
            <option value="menu_desplegable" ${tipo === 'menu_desplegable' ? 'selected' : ''}>Menú Desplegable</option>
            <option value="escala_lineal" ${tipo === 'escala_lineal' ? 'selected' : ''}>Escala Lineal</option>
            <option value="cuadricula" ${tipo === 'cuadricula' ? 'selected' : ''}>Cuadrícula (Radio)</option>
            <option value="cuadricula_checkbox" ${tipo === 'cuadricula_checkbox' ? 'selected' : ''}>Cuadrícula (Checkbox)</option>
            <option value="seccion" ${tipo === 'seccion' ? 'selected' : ''}>Título / Encabezado de Sección</option>
            <option value="descripcion_corta" ${tipo === 'descripcion_corta' ? 'selected' : ''}>Descripción Corta</option>
        </select>
        <div id="opciones-container-${id}" class="opciones-container" style="display:none;"></div>
    `;
    
    document.getElementById('preguntas-container').appendChild(div);
    insertMarkdownHelper(`texto-${id}`);
    
    // Call toggleOpciones to render the specific fields
    toggleOpciones(id);
    
    // If there were existing options/values, restore them
    if (tipo === 'opcion_multiple' || tipo === 'menu_desplegable' || tipo === 'seleccion_multiple') {
        if (opciones && opciones.length > 0) {
            const lista = document.getElementById(`lista-opciones-${id}`);
            lista.innerHTML = '';
            opciones.forEach(op => {
                const opDiv = document.createElement('div');
                opDiv.className = 'opcion-input-group';
                opDiv.innerHTML = `<input type="text" class="opcion-val-${id}" value="${op.replace(/"/g, '&quot;')}" required> <button type="button" onclick="removeOpcion(this)" style="color:red; background:none; border:none; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>`;
                lista.appendChild(opDiv);
            });
        }
        const chkOtro = document.getElementById(`incluye-otro-${id}`);
        if (chkOtro) {
            chkOtro.checked = incluyeOtro;
            document.getElementById(`label-otro-${id}`).style.display = incluyeOtro ? 'inline-block' : 'none';
            document.getElementById(`label-otro-${id}`).value = labelOtro;
        }
        const chkJust = document.getElementById(`incluye-justificacion-${id}`);
        if (chkJust) {
            chkJust.checked = incluyeJustif;
            document.getElementById(`label-justificacion-${id}`).style.display = incluyeJustif ? 'inline-block' : 'none';
            document.getElementById(`label-justificacion-${id}`).value = labelJustif;
        }
    } else if (tipo === 'escala_lineal' && opciones) {
        if (opciones.min) document.getElementById(`escala-min-${id}`).value = opciones.min;
        if (opciones.max) document.getElementById(`escala-max-${id}`).value = opciones.max;
        if (opciones.label_min) document.getElementById(`escala-label-min-${id}`).value = opciones.label_min;
        if (opciones.label_max) document.getElementById(`escala-label-max-${id}`).value = opciones.label_max;
    } else if ((tipo === 'cuadricula' || tipo === 'cuadricula_checkbox') && opciones) {
        if (opciones.filas) {
            const fCont = document.getElementById(`cuad-filas-${id}`);
            fCont.innerHTML = '';
            opciones.filas.forEach(f => {
                const el = document.createElement('input');
                el.type = 'text'; el.className = `cuad-fila-val-${id} form-control`; el.value = f; el.required = true; el.style.marginBottom = '5px';
                fCont.appendChild(el);
            });
        }
        if (opciones.columnas) {
            const cCont = document.getElementById(`cuad-cols-${id}`);
            cCont.innerHTML = '';
            opciones.columnas.forEach(c => {
                const el = document.createElement('input');
                el.type = 'text'; el.className = `cuad-col-val-${id} form-control`; el.value = c; el.required = true; el.style.marginBottom = '5px';
                cCont.appendChild(el);
            });
        }
    }
}

function removePregunta(id) {
    const el = document.getElementById(`preg-${id}`);
    if (el) el.remove();
}

function toggleOpciones(id) {
    const tipo = document.getElementById(`tipo-${id}`).value;
    const container = document.getElementById(`opciones-container-${id}`);
    
    // Save existing labels if they exist
    const oldLabelOtro = document.getElementById(`label-otro-${id}`)?.value || 'Otro';
    const oldLabelJust = document.getElementById(`label-justificacion-${id}`)?.value || 'Justifique su respuesta';
    const oldChkOtro = document.getElementById(`incluye-otro-${id}`)?.checked || false;
    const oldChkJust = document.getElementById(`incluye-justificacion-${id}`)?.checked || false;
    
    if (tipo === 'opcion_multiple' || tipo === 'menu_desplegable' || tipo === 'seleccion_multiple') {
        container.style.display = 'block';
        
        let html = `
            <p style="font-size:0.85rem; margin-bottom:5px;">Opciones de respuesta:</p>
            <div id="lista-opciones-${id}">
                <div class="opcion-input-group"><input type="text" class="opcion-val-${id}" placeholder="Opción 1" required></div>
                <div class="opcion-input-group"><input type="text" class="opcion-val-${id}" placeholder="Opción 2" required></div>
            </div>
            <button type="button" class="btn-add-opcion" onclick="addOpcion(${id})">+ Añadir opción</button>
            <div style="margin-top: 10px; display: ${tipo === 'menu_desplegable' ? 'none' : 'block'};" id="toggle-otro-${id}">
                <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 5px; cursor: pointer; margin-bottom: 5px;">
                    <input type="checkbox" id="incluye-otro-${id}" onchange="document.getElementById('label-otro-${id}').style.display = this.checked ? 'inline-block' : 'none'"> Permitir respuesta abierta
                    <input type="text" id="label-otro-${id}" class="form-control" style="width: 150px; padding: 2px 5px; display: none;" placeholder="Ej: Otro" value="Otro">
                </label>
                <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="checkbox" id="incluye-justificacion-${id}" onchange="document.getElementById('label-justificacion-${id}').style.display = this.checked ? 'inline-block' : 'none'"> Agregar campo extra de texto
                    <input type="text" id="label-justificacion-${id}" class="form-control" style="width: 250px; padding: 2px 5px; display: none;" placeholder="Ej: Justifique su respuesta" value="Justifique su respuesta">
                </label>
            </div>
        `;
        container.innerHTML = html;
        
        // Restore values
        if (tipo !== 'menu_desplegable') {
            document.getElementById(`incluye-otro-${id}`).checked = oldChkOtro;
            document.getElementById(`label-otro-${id}`).value = oldLabelOtro;
            document.getElementById(`label-otro-${id}`).style.display = oldChkOtro ? 'inline-block' : 'none';
            
            document.getElementById(`incluye-justificacion-${id}`).checked = oldChkJust;
            document.getElementById(`label-justificacion-${id}`).value = oldLabelJust;
            document.getElementById(`label-justificacion-${id}`).style.display = oldChkJust ? 'inline-block' : 'none';
        }
        
    } else if (tipo === 'escala_lineal') {
        container.style.display = 'block';
        container.innerHTML = `
            <p style="font-size:0.85rem; margin-bottom:5px;">Escala Lineal:</p>
            <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                <select id="escala-min-${id}" class="form-control" style="width:60px;"><option value="0">0</option><option value="1" selected>1</option></select> a 
                <select id="escala-max-${id}" class="form-control" style="width:60px;"><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5" selected>5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select>
            </div>
            <div style="display:flex; gap:10px;">
                <input type="text" id="escala-label-min-${id}" class="form-control" placeholder="Etiqueta min (opcional)">
                <input type="text" id="escala-label-max-${id}" class="form-control" placeholder="Etiqueta max (opcional)">
            </div>
        `;
    } else if (tipo === 'cuadricula' || tipo === 'cuadricula_checkbox') {
        container.style.display = 'block';
        container.innerHTML = `
            <div style="display:flex; gap:20px; width:100%;">
                <div style="flex:1;">
                    <p style="font-size:0.85rem; margin-bottom:5px;">Filas:</p>
                    <div id="cuad-filas-${id}">
                        <input type="text" class="cuad-fila-val-${id} form-control" placeholder="Fila 1" required style="margin-bottom:5px;">
                    </div>
                    <button type="button" onclick="addCuadFila('${id}')" class="btn-add-opcion">+ Añadir fila</button>
                </div>
                <div style="flex:1;">
                    <p style="font-size:0.85rem; margin-bottom:5px;">Columnas:</p>
                    <div id="cuad-cols-${id}">
                        <input type="text" class="cuad-col-val-${id} form-control" placeholder="Columna 1" required style="margin-bottom:5px;">
                    </div>
                    <button type="button" onclick="addCuadCol('${id}')" class="btn-add-opcion">+ Añadir columna</button>
                </div>
            </div>
        `;
    } else {
        container.style.display = 'none';
        container.innerHTML = '';
    }
}

function addOpcion(id) {
    const lista = document.getElementById(`lista-opciones-${id}`);
    const div = document.createElement('div');
    div.className = 'opcion-input-group';
    div.innerHTML = `<input type="text" class="opcion-val-${id}" placeholder="Nueva opción" required> <button type="button" onclick="removeOpcion(this)" style="color:red; background:none; border:none; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>`;
    lista.appendChild(div);
}

function removeOpcion(btn) {
    btn.parentElement.remove();
}

function addCuadFila(id) {
    const div = document.createElement('input'); div.type = 'text'; div.className = `cuad-fila-val-${id} form-control`; div.placeholder = 'Nueva fila'; div.required = true; div.style.marginBottom = '5px';
    document.getElementById(`cuad-filas-${id}`).appendChild(div);
}

function addCuadCol(id) {
    const div = document.createElement('input'); div.type = 'text'; div.className = `cuad-col-val-${id} form-control`; div.placeholder = 'Nueva columna'; div.required = true; div.style.marginBottom = '5px';
    document.getElementById(`cuad-cols-${id}`).appendChild(div);
}

// ----------------------------------------------------
// EDIT FORM FUNCTIONS
// ----------------------------------------------------
function addEditPregunta(texto = '', tipo = 'texto', opciones = null, incluyeOtro = false, incluyeJustif = false, labelOtro = 'Otro', labelJustif = 'Justifique su respuesta') {
    editPreguntaCounter++;
    const id = editPreguntaCounter;
    
    const div = document.createElement('div');
    div.className = 'pregunta-item edit-pregunta-item';
    div.id = `edit-preg-${id}`;
    
    div.innerHTML = `
        <div class="preg-actions">
            <button type="button" class="btn-preg-action danger" onclick="removeEditPregunta(${id})" title="Eliminar"><i class="fa-solid fa-times"></i></button>
        </div>
        <input type="text" class="form-control" id="edit-texto-${id}" value="${texto}" placeholder="Escribe la pregunta..." required style="width: 80%; margin-top: 5px;">
        <select class="form-control" id="edit-tipo-${id}" onchange="toggleEditOpciones(${id})" style="margin-bottom: 0;">
            <option value="texto" ${tipo === 'texto' ? 'selected' : ''}>Texto Libre (Párrafo)</option>
            <option value="respuesta_corta" ${tipo === 'respuesta_corta' ? 'selected' : ''}>Respuesta Corta</option>
            <option value="opcion_multiple" ${tipo === 'opcion_multiple' ? 'selected' : ''}>Opción Múltiple (Radio)</option>
            <option value="seleccion_multiple" ${tipo === 'seleccion_multiple' ? 'selected' : ''}>Casillas de verificación (Checkbox)</option>
            <option value="menu_desplegable" ${tipo === 'menu_desplegable' ? 'selected' : ''}>Menú Desplegable</option>
            <option value="escala_lineal" ${tipo === 'escala_lineal' ? 'selected' : ''}>Escala Lineal</option>
            <option value="cuadricula" ${tipo === 'cuadricula' ? 'selected' : ''}>Cuadrícula (Radio)</option>
            <option value="cuadricula_checkbox" ${tipo === 'cuadricula_checkbox' ? 'selected' : ''}>Cuadrícula (Checkbox)</option>
            <option value="seccion" ${tipo === 'seccion' ? 'selected' : ''}>Título / Encabezado de Sección</option>
            <option value="descripcion_corta" ${tipo === 'descripcion_corta' ? 'selected' : ''}>Descripción Corta</option>
        </select>
        <div id="edit-opciones-container-${id}" class="opciones-container" style="display:none;"></div>
    `;
    
    document.getElementById('edit-preguntas-container').appendChild(div);
    insertMarkdownHelper(`edit-texto-${id}`);
    
    toggleEditOpciones(id);
    
    // Restore values
    if (tipo === 'opcion_multiple' || tipo === 'menu_desplegable' || tipo === 'seleccion_multiple') {
        if (opciones && opciones.length > 0) {
            const lista = document.getElementById(`edit-lista-opciones-${id}`);
            lista.innerHTML = '';
            opciones.forEach(op => {
                const opDiv = document.createElement('div');
                opDiv.className = 'opcion-input-group';
                opDiv.innerHTML = `<input type="text" class="edit-opcion-val-${id}" value="${op.replace(/"/g, '&quot;')}" required> <button type="button" onclick="removeEditOpcion(this)" style="color:red; background:none; border:none; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>`;
                lista.appendChild(opDiv);
            });
        }
        const chkOtro = document.getElementById(`edit-incluye-otro-${id}`);
        if (chkOtro) {
            chkOtro.checked = incluyeOtro;
            document.getElementById(`edit-label-otro-${id}`).style.display = incluyeOtro ? 'inline-block' : 'none';
            document.getElementById(`edit-label-otro-${id}`).value = labelOtro;
        }
        const chkJust = document.getElementById(`edit-incluye-justificacion-${id}`);
        if (chkJust) {
            chkJust.checked = incluyeJustif;
            document.getElementById(`edit-label-justificacion-${id}`).style.display = incluyeJustif ? 'inline-block' : 'none';
            document.getElementById(`edit-label-justificacion-${id}`).value = labelJustif;
        }
    } else if (tipo === 'escala_lineal' && opciones) {
        if (opciones.min) document.getElementById(`edit-escala-min-${id}`).value = opciones.min;
        if (opciones.max) document.getElementById(`edit-escala-max-${id}`).value = opciones.max;
        if (opciones.label_min) document.getElementById(`edit-escala-label-min-${id}`).value = opciones.label_min;
        if (opciones.label_max) document.getElementById(`edit-escala-label-max-${id}`).value = opciones.label_max;
    } else if ((tipo === 'cuadricula' || tipo === 'cuadricula_checkbox') && opciones) {
        if (opciones.filas) {
            const fCont = document.getElementById(`edit-cuad-filas-${id}`);
            fCont.innerHTML = '';
            opciones.filas.forEach(f => {
                const el = document.createElement('input');
                el.type = 'text'; el.className = `edit-cuad-fila-val-${id} form-control`; el.value = f; el.required = true; el.style.marginBottom = '5px';
                fCont.appendChild(el);
            });
        }
        if (opciones.columnas) {
            const cCont = document.getElementById(`edit-cuad-cols-${id}`);
            cCont.innerHTML = '';
            opciones.columnas.forEach(c => {
                const el = document.createElement('input');
                el.type = 'text'; el.className = `edit-cuad-col-val-${id} form-control`; el.value = c; el.required = true; el.style.marginBottom = '5px';
                cCont.appendChild(el);
            });
        }
    }
    
    if (isEditQuestionsBlocked) {
        div.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
        const actions = div.querySelector('.preg-actions');
        if (actions) actions.style.display = 'none';
    }
}

function removeEditPregunta(id) {
    const el = document.getElementById(`edit-preg-${id}`);
    if (el) el.remove();
}

function toggleEditOpciones(id) {
    const tipo = document.getElementById(`edit-tipo-${id}`).value;
    const container = document.getElementById(`edit-opciones-container-${id}`);
    
    const oldLabelOtro = document.getElementById(`edit-label-otro-${id}`)?.value || 'Otro';
    const oldLabelJust = document.getElementById(`edit-label-justificacion-${id}`)?.value || 'Justifique su respuesta';
    const oldChkOtro = document.getElementById(`edit-incluye-otro-${id}`)?.checked || false;
    const oldChkJust = document.getElementById(`edit-incluye-justificacion-${id}`)?.checked || false;
    
    if (tipo === 'opcion_multiple' || tipo === 'menu_desplegable' || tipo === 'seleccion_multiple') {
        container.style.display = 'block';
        
        let html = `
            <p style="font-size:0.85rem; margin-bottom:5px;">Opciones de respuesta:</p>
            <div id="edit-lista-opciones-${id}">
                <div class="opcion-input-group"><input type="text" class="edit-opcion-val-${id}" placeholder="Opción 1" required></div>
                <div class="opcion-input-group"><input type="text" class="edit-opcion-val-${id}" placeholder="Opción 2" required></div>
            </div>
            <button type="button" class="btn-add-opcion" onclick="addEditOpcion(${id})">+ Añadir opción</button>
            <div style="margin-top: 10px; display: ${tipo === 'menu_desplegable' ? 'none' : 'block'};" id="edit-toggle-otro-${id}">
                <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 5px; cursor: pointer; margin-bottom: 5px;">
                    <input type="checkbox" id="edit-incluye-otro-${id}" onchange="document.getElementById('edit-label-otro-${id}').style.display = this.checked ? 'inline-block' : 'none'"> Permitir respuesta abierta
                    <input type="text" id="edit-label-otro-${id}" class="form-control" style="width: 150px; padding: 2px 5px; display: none;" placeholder="Ej: Otro" value="Otro">
                </label>
                <label style="font-size: 0.85rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                    <input type="checkbox" id="edit-incluye-justificacion-${id}" onchange="document.getElementById('edit-label-justificacion-${id}').style.display = this.checked ? 'inline-block' : 'none'"> Agregar campo extra de texto
                    <input type="text" id="edit-label-justificacion-${id}" class="form-control" style="width: 250px; padding: 2px 5px; display: none;" placeholder="Ej: Justifique su respuesta" value="Justifique su respuesta">
                </label>
            </div>
        `;
        container.innerHTML = html;
        
        if (tipo !== 'menu_desplegable') {
            document.getElementById(`edit-incluye-otro-${id}`).checked = oldChkOtro;
            document.getElementById(`edit-label-otro-${id}`).value = oldLabelOtro;
            document.getElementById(`edit-label-otro-${id}`).style.display = oldChkOtro ? 'inline-block' : 'none';
            
            document.getElementById(`edit-incluye-justificacion-${id}`).checked = oldChkJust;
            document.getElementById(`edit-label-justificacion-${id}`).value = oldLabelJust;
            document.getElementById(`edit-label-justificacion-${id}`).style.display = oldChkJust ? 'inline-block' : 'none';
        }
        
    } else if (tipo === 'escala_lineal') {
        container.style.display = 'block';
        container.innerHTML = `
            <p style="font-size:0.85rem; margin-bottom:5px;">Escala Lineal:</p>
            <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px;">
                <select id="edit-escala-min-${id}" class="form-control" style="width:60px;"><option value="0">0</option><option value="1" selected>1</option></select> a 
                <select id="edit-escala-max-${id}" class="form-control" style="width:60px;"><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5" selected>5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option></select>
            </div>
            <div style="display:flex; gap:10px;">
                <input type="text" id="edit-escala-label-min-${id}" class="form-control" placeholder="Etiqueta min (opcional)">
                <input type="text" id="edit-escala-label-max-${id}" class="form-control" placeholder="Etiqueta max (opcional)">
            </div>
        `;
    } else if (tipo === 'cuadricula' || tipo === 'cuadricula_checkbox') {
        container.style.display = 'block';
        container.innerHTML = `
            <div style="display:flex; gap:20px; width:100%;">
                <div style="flex:1;">
                    <p style="font-size:0.85rem; margin-bottom:5px;">Filas:</p>
                    <div id="edit-cuad-filas-${id}">
                        <input type="text" class="edit-cuad-fila-val-${id} form-control" placeholder="Fila 1" required style="margin-bottom:5px;">
                    </div>
                    <button type="button" onclick="addEditCuadFila('${id}')" class="btn-add-opcion">+ Añadir fila</button>
                </div>
                <div style="flex:1;">
                    <p style="font-size:0.85rem; margin-bottom:5px;">Columnas:</p>
                    <div id="edit-cuad-cols-${id}">
                        <input type="text" class="edit-cuad-col-val-${id} form-control" placeholder="Columna 1" required style="margin-bottom:5px;">
                    </div>
                    <button type="button" onclick="addEditCuadCol('${id}')" class="btn-add-opcion">+ Añadir columna</button>
                </div>
            </div>
        `;
    } else {
        container.style.display = 'none';
        container.innerHTML = '';
    }
}

function addEditOpcion(id) {
    const lista = document.getElementById(`edit-lista-opciones-${id}`);
    const div = document.createElement('div');
    div.className = 'opcion-input-group';
    div.innerHTML = `<input type="text" class="edit-opcion-val-${id}" placeholder="Nueva opción" required> <button type="button" onclick="removeEditOpcion(this)" style="color:red; background:none; border:none; cursor:pointer;"><i class="fa-solid fa-trash"></i></button>`;
    lista.appendChild(div);
}

function removeEditOpcion(btn) {
    btn.parentElement.remove();
}

function addEditCuadFila(id) {
    const div = document.createElement('input'); div.type = 'text'; div.className = `edit-cuad-fila-val-${id} form-control`; div.placeholder = 'Nueva fila'; div.required = true; div.style.marginBottom = '5px';
    document.getElementById(`edit-cuad-filas-${id}`).appendChild(div);
}

function addEditCuadCol(id) {
    const div = document.createElement('input'); div.type = 'text'; div.className = `edit-cuad-col-val-${id} form-control`; div.placeholder = 'Nueva columna'; div.required = true; div.style.marginBottom = '5px';
    document.getElementById(`edit-cuad-cols-${id}`).appendChild(div);
}

// ----------------------------------------------------
// API FUNCTIONS
// ----------------------------------------------------
async function cargarEncuestas() {
    const urlParams = new URLSearchParams(window.location.search);
    const tipo_enc = urlParams.get('tipo') || 'anonima';
    
    try {
        const res = await fetch('api/encuestas/admin_get_encuestas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo: tipo_enc })
        });
        const data = await res.json();
        const tbody = document.getElementById('tbody-encuestas');
        tbody.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(enc => {
                const baseUrl = window.location.href.split('admin_encuestas.html')[0];
                const link = `${baseUrl}encuesta.html?token=${enc.token_publico}`;
                const badgeClass = enc.estado === 'abierta' ? 'estado-abierta' : 'estado-cerrada';
                
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${enc.titulo}</strong></td>
                    <td><span class="badge-estado ${badgeClass}">${enc.estado.toUpperCase()}</span></td>
                    <td><strong>${enc.total_respuestas}</strong> tickets</td>
                    <td>${enc.creado_en.split(' ')[0]}</td>
                    <td style="text-align: right; display:flex; gap:5px; justify-content:flex-end;">
                        <button onclick="copiarEnlace('${link}')" title="Copiar Link Público" style="padding:5px 10px; cursor:pointer; background:#f1f5f9; border:1px solid #ccc; border-radius:4px;"><i class="fa-solid fa-link"></i></button>
                        <button onclick="verResultados(${enc.id})" title="Ver Resultados" style="padding:5px 10px; cursor:pointer; background:var(--primary-color); color:white; border:none; border-radius:4px;"><i class="fa-solid fa-chart-bar"></i></button>
                        <button onclick="editEncuesta(${enc.id})" title="Editar" style="padding:5px 10px; cursor:pointer; background:var(--color-trabajo); color:white; border:none; border-radius:4px;"><i class="fa-solid fa-edit"></i></button>
                        <button onclick="toggleEstado(${enc.id})" title="Abrir/Cerrar" style="padding:5px 10px; cursor:pointer; background:var(--color-disertacion); color:white; border:none; border-radius:4px;"><i class="fa-solid fa-power-off"></i></button>
                        <button onclick="vaciarEncuesta(${enc.id})" title="Vaciar Respuestas" style="padding:5px 10px; cursor:pointer; background:#f97316; color:white; border:none; border-radius:4px;"><i class="fa-solid fa-broom"></i></button>
                        <button onclick="eliminarEncuesta(${enc.id})" title="Eliminar" style="padding:5px 10px; cursor:pointer; background:var(--danger); color:white; border:none; border-radius:4px;"><i class="fa-solid fa-trash"></i></button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No hay encuestas.</td></tr>';
        }
    } catch (e) {
        console.error(e);
    }
}

async function editEncuesta(id) {
    document.getElementById('edit-msg').style.display = 'none';
    document.getElementById('edit-preguntas-container').innerHTML = '';
    
    try {
        const res = await fetch('api/encuestas/admin_get_encuesta_completa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('edit-id').value = data.encuesta.id;
            document.getElementById('edit-titulo').value = data.encuesta.titulo;
            document.getElementById('edit-desc').value = data.encuesta.descripcion || '';
            
            isEditQuestionsBlocked = parseInt(data.encuesta.total_respuestas) > 0;
            
            const warnMsg = document.getElementById('edit-warning-msg');
            const btnEditAdd = document.getElementById('btn-edit-add-preg');
            
            if (isEditQuestionsBlocked) {
                if(warnMsg) warnMsg.style.display = 'block';
                if(btnEditAdd) btnEditAdd.style.display = 'none';
            } else {
                if(warnMsg) warnMsg.style.display = 'none';
                if(btnEditAdd) btnEditAdd.style.display = 'block';
            }
            
            insertMarkdownHelper('edit-titulo');
            insertMarkdownHelper('edit-desc');

            editPreguntaCounter = 0;
            data.preguntas.forEach(p => {
                let opciones = p.opciones;
                let incluyeOtro = p.incluye_otro || false;
                let incluyeJustif = p.incluye_justificacion || false;
                let labelOtro = p.label_otro || 'Otro';
                let labelJustif = p.label_justificacion || 'Justifique su respuesta';
                
                if (typeof opciones === 'string') {
                    try {
                        const optParsed = JSON.parse(opciones);
                        opciones = optParsed.items || optParsed;
                        incluyeOtro = optParsed.incluye_otro || incluyeOtro;
                        incluyeJustif = optParsed.incluye_justificacion || incluyeJustif;
                        labelOtro = optParsed.label_otro || labelOtro;
                        labelJustif = optParsed.label_justificacion || labelJustif;
                    } catch(e) {}
                }
                
                addEditPregunta(p.texto_pregunta, p.tipo_pregunta, opciones, incluyeOtro, incluyeJustif, labelOtro, labelJustif);
            });
            
            const modal = document.getElementById('modal-editar');
            if(modal) modal.classList.add('show');
        }
    } catch (e) {
        console.error(e);
    }
}

async function verResultados(id) {
    const urlParams = new URLSearchParams(window.location.search);
    const tipo = urlParams.get('tipo') || 'anonima';
    
    if (tipo === 'nominativa') {
        window.location.href = `admin_resultados_nominativos.html?id=${id}`;
        return;
    }

    try {
        const res = await fetch('api/encuestas/admin_get_resultados.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        
        if (data.success) {
            document.getElementById('res-titulo').textContent = `Resultados: ${data.encuesta.titulo}`;
            const cont = document.getElementById('res-container');
            cont.innerHTML = '';
            
            let resPreguntaNum = 1;
            data.resultados.forEach(r => {
                const d = document.createElement('div');
                d.className = 'resultado-item';
                
                if (r.tipo === 'seccion') {
                    d.style.borderBottom = 'none';
                    d.innerHTML = `<div class="resultado-pregunta" style="color:var(--primary-color); font-size:1.2rem; border-bottom:2px solid var(--border-color); padding-bottom:5px; margin-top:1.5rem;">${r.texto}</div>`;
                    cont.appendChild(d);
                    return;
                } else if (r.tipo === 'descripcion_corta') {
                    d.style.borderBottom = 'none';
                    d.innerHTML = `<div class="resultado-pregunta" style="color:var(--text-color); font-size:1rem; font-style:italic; padding-bottom:5px; margin-top:0.5rem; margin-bottom: 0.5rem;">${r.texto}</div>`;
                    cont.appendChild(d);
                    return;
                }
                
                let html = `<div class="resultado-pregunta">${resPreguntaNum}. ${r.texto}</div>`;
                resPreguntaNum++;
                
                if (r.tipo === 'texto') {
                    if (r.respuestas.length === 0) {
                        html += `<p style="color:#888; font-style:italic;">No hay respuestas aún.</p>`;
                    } else {
                        html += `<ul style="padding-left:20px; font-size:0.9rem; color:#444;">`;
                        r.respuestas.forEach(txt => {
                            html += `<li style="margin-bottom:5px;">"${txt}"</li>`;
                        });
                        html += `</ul>`;
                    }
                } else if (r.tipo === 'opcion_multiple' || r.tipo === 'menu_desplegable' || r.tipo === 'seleccion_multiple') {
                    let total = 0;
                    for (const k in r.respuestas) total += r.respuestas[k];
                    
                    if (total === 0) {
                        html += `<p style="color:#888; font-style:italic;">No hay votos aún.</p>`;
                    } else {
                        for (const k in r.respuestas) {
                            const votos = r.respuestas[k];
                            const porcentaje = Math.round((votos / total) * 100);
                            html += `
                                <div style="font-size:0.85rem; margin-top:10px;">${k} (${votos} votos, ${porcentaje}%)</div>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: ${porcentaje > 0 ? porcentaje : 0}%;">${porcentaje}%</div>
                                </div>
                            `;
                        }
                    }
                }
                
                d.innerHTML = html;
                cont.appendChild(d);
            });
            
            document.getElementById('modal-resultados').classList.add('show');
        }
    } catch (e) {
        console.error(e);
    }
}

async function toggleEstado(id) {
    try {
        const res = await fetch('api/encuestas/admin_toggle_encuesta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            cargarEncuestas();
        }
    } catch (e) {
        console.error(e);
    }
}

async function eliminarEncuesta(id) {
    if (!confirm("¿Borrar definitivamente esta encuesta y todas sus respuestas?")) return;
    try {
        const res = await fetch('api/encuestas/admin_delete_encuesta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            cargarEncuestas();
        }
    } catch (e) {
        console.error(e);
    }
}

async function vaciarEncuesta(id) {
    if (!confirm("¿Estás seguro de VACIAR esta encuesta?\n\nEsto borrará permanentemente TODAS las respuestas.")) return;
    try {
        const res = await fetch('api/encuestas/admin_empty_encuesta.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const data = await res.json();
        if (data.success) {
            alert("Respuestas eliminadas correctamente.");
            cargarEncuestas();
        }
    } catch (e) {
        console.error(e);
    }
}

function copiarEnlace(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert("Enlace copiado al portapapeles:\n" + text);
    });
}

// ----------------------------------------------------
// INITIALIZATION & LISTENERS
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tipo = urlParams.get('tipo') || 'anonima';
    
    if (tipo === 'nominativa') {
        const navNom = document.getElementById('nav-nominativa');
        if(navNom) navNom.classList.add('active');
        const title = document.getElementById('crear-title');
        if(title) title.textContent = 'Crear Nueva Encuesta Nominativa';
    } else {
        const navAnon = document.getElementById('nav-anonima');
        if(navAnon) navAnon.classList.add('active');
    }
    
    insertMarkdownHelper('enc-titulo');
    insertMarkdownHelper('enc-desc');
    
    const btnAdd = document.getElementById('btn-add-preg');
    if (btnAdd) {
        btnAdd.addEventListener('click', () => {
            addPregunta();
        });
    }
    
    const btnEditAdd = document.getElementById('btn-edit-add-preg');
    if (btnEditAdd) {
        btnEditAdd.addEventListener('click', () => {
            if (!isEditQuestionsBlocked) addEditPregunta();
        });
    }
    
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('api/logout.php');
            window.location.href = 'login.html';
        });
    }
    
    const formCrear = document.getElementById('form-crear-encuesta');
    if (formCrear) {
        formCrear.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-guardar-encuesta');
            const msg = document.getElementById('form-msg');
            const titulo = document.getElementById('enc-titulo').value;
            const desc = document.getElementById('enc-desc').value;
            
            const items = document.querySelectorAll('.pregunta-item:not(.edit-pregunta-item)');
            if (items.length === 0) { alert("Debes agregar al menos una pregunta."); return; }
            
            let preguntas = [];
            let valid = true;
            
            items.forEach(item => {
                const idStr = item.id.replace('preg-', '');
                const texto = document.getElementById(`texto-${idStr}`).value;
                const tipo = document.getElementById(`tipo-${idStr}`).value;
                
                let opciones = null;
                let incluyeOtro = false;
                let incluyeJustificacion = false;
                let labelOtro = 'Otro';
                let labelJustificacion = 'Justifique su respuesta';
                
                if (tipo === 'opcion_multiple' || tipo === 'menu_desplegable' || tipo === 'seleccion_multiple') {
                    let ops = [];
                    item.querySelectorAll(`.opcion-val-${idStr}`).forEach(inp => { if(inp.value.trim() !== '') ops.push(inp.value.trim()); });
                    if(ops.length < 2 && tipo !== 'menu_desplegable') {
                        alert("Las preguntas de opciones o menú desplegable deben tener al menos 2 opciones.");
                        valid = false;
                    }
                    if (tipo === 'opcion_multiple' || tipo === 'seleccion_multiple') {
                        incluyeOtro = document.getElementById(`incluye-otro-${idStr}`)?.checked || false;
                        incluyeJustificacion = document.getElementById(`incluye-justificacion-${idStr}`)?.checked || false;
                        if (incluyeOtro) labelOtro = document.getElementById(`label-otro-${idStr}`)?.value || 'Otro';
                        if (incluyeJustificacion) labelJustificacion = document.getElementById(`label-justificacion-${idStr}`)?.value || 'Justifique su respuesta';
                    }
                    opciones = ops;
                } else if (tipo === 'escala_lineal') {
                    opciones = {
                        min: document.getElementById(`escala-min-${idStr}`).value,
                        max: document.getElementById(`escala-max-${idStr}`).value,
                        label_min: document.getElementById(`escala-label-min-${idStr}`).value,
                        label_max: document.getElementById(`escala-label-max-${idStr}`).value
                    };
                } else if (tipo === 'cuadricula' || tipo === 'cuadricula_checkbox') {
                    let filas = [];
                    item.querySelectorAll(`.cuad-fila-val-${idStr}`).forEach(inp => filas.push(inp.value));
                    let columnas = [];
                    item.querySelectorAll(`.cuad-col-val-${idStr}`).forEach(inp => columnas.push(inp.value));
                    opciones = { filas, columnas };
                }
                
                preguntas.push({
                    texto_pregunta: texto,
                    tipo_pregunta: tipo,
                    opciones: opciones,
                    incluye_otro: incluyeOtro,
                    incluye_justificacion: incluyeJustificacion,
                    label_otro: labelOtro,
                    label_justificacion: labelJustificacion
                });
            });
            
            if (!valid) return;
            
            btn.disabled = true;
            btn.innerHTML = 'Guardando...';
            
            try {
                const res = await fetch('api/encuestas/admin_create_encuesta.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ titulo, descripcion: desc, preguntas, tipo_encuesta: tipo })
                });
                const data = await res.json();
                if (data.success) {
                    msg.className = 'form-message success';
                    msg.textContent = 'Encuesta guardada correctamente. Token: ' + data.token;
                    msg.style.display = 'block';
                    formCrear.reset();
                    document.getElementById('preguntas-container').innerHTML = '';
                    preguntaCounter = 0;
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    msg.className = 'form-message error';
                    msg.textContent = data.error || 'Error al guardar la encuesta.';
                    msg.style.display = 'block';
                }
            } catch (error) {
                msg.className = 'form-message error';
                msg.textContent = 'Error de conexión.';
                msg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Publicar Encuesta';
            }
        });
    }
    
    const formEditar = document.getElementById('form-editar-encuesta');
    if (formEditar) {
        formEditar.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btn-guardar-edicion');
            const msg = document.getElementById('edit-msg');
            const id = document.getElementById('edit-id').value;
            const titulo = document.getElementById('edit-titulo').value;
            const desc = document.getElementById('edit-desc').value;
            
            let preguntas = null;
            
            if (!isEditQuestionsBlocked) {
                const items = document.querySelectorAll('.edit-pregunta-item');
                if (items.length === 0) { alert("Debes tener al menos una pregunta."); return; }
                preguntas = [];
                let valid = true;
                items.forEach(item => {
                    const idStr = item.id.replace('edit-preg-', '');
                    const texto = document.getElementById(`edit-texto-${idStr}`).value;
                    const tipo = document.getElementById(`edit-tipo-${idStr}`).value;
                    
                    let opciones = null;
                    let incluyeOtro = false;
                    let incluyeJustificacion = false;
                    let labelOtro = 'Otro';
                    let labelJustificacion = 'Justifique su respuesta';
                    
                    if (tipo === 'opcion_multiple' || tipo === 'menu_desplegable' || tipo === 'seleccion_multiple') {
                        let ops = [];
                        item.querySelectorAll(`.edit-opcion-val-${idStr}`).forEach(inp => { if(inp.value.trim() !== '') ops.push(inp.value.trim()); });
                        if(ops.length < 2 && tipo !== 'menu_desplegable') {
                            alert("Las preguntas de opciones deben tener al menos 2 opciones.");
                            valid = false;
                        }
                        if (tipo === 'opcion_multiple' || tipo === 'seleccion_multiple') {
                            incluyeOtro = document.getElementById(`edit-incluye-otro-${idStr}`)?.checked || false;
                            incluyeJustificacion = document.getElementById(`edit-incluye-justificacion-${idStr}`)?.checked || false;
                            if (incluyeOtro) labelOtro = document.getElementById(`edit-label-otro-${idStr}`)?.value || 'Otro';
                            if (incluyeJustificacion) labelJustificacion = document.getElementById(`edit-label-justificacion-${idStr}`)?.value || 'Justifique su respuesta';
                        }
                        opciones = ops;
                    } else if (tipo === 'escala_lineal') {
                        opciones = {
                            min: document.getElementById(`edit-escala-min-${idStr}`).value,
                            max: document.getElementById(`edit-escala-max-${idStr}`).value,
                            label_min: document.getElementById(`edit-escala-label-min-${idStr}`).value,
                            label_max: document.getElementById(`edit-escala-label-max-${idStr}`).value
                        };
                    } else if (tipo === 'cuadricula' || tipo === 'cuadricula_checkbox') {
                        let filas = [];
                        item.querySelectorAll(`.edit-cuad-fila-val-${idStr}`).forEach(inp => filas.push(inp.value));
                        let columnas = [];
                        item.querySelectorAll(`.edit-cuad-col-val-${idStr}`).forEach(inp => columnas.push(inp.value));
                        opciones = { filas, columnas };
                    }
                    
                    preguntas.push({
                        texto_pregunta: texto,
                        tipo_pregunta: tipo,
                        opciones: opciones,
                        incluye_otro: incluyeOtro,
                        incluye_justificacion: incluyeJustificacion,
                        label_otro: labelOtro,
                        label_justificacion: labelJustificacion
                    });
                });
                if (!valid) return;
            }
            
            btn.disabled = true;
            btn.innerHTML = 'Guardando...';
            try {
                const response = await fetch('api/encuestas/admin_edit_encuesta.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id, titulo, descripcion: desc, preguntas })
                });
                const data = await response.json();
                if(data.success) {
                    msg.className = 'form-message success';
                    msg.textContent = 'Encuesta editada correctamente.';
                    msg.style.display = 'block';
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    msg.className = 'form-message error';
                    msg.textContent = data.error || 'Error al guardar.';
                    msg.style.display = 'block';
                }
            } catch(e) {
                msg.className = 'form-message error';
                msg.textContent = 'Error de conexión.';
                msg.style.display = 'block';
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-save"></i> Guardar Cambios';
            }
        });
    }
    
    cargarEncuestas();
});
