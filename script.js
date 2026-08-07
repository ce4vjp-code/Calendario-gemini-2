document.addEventListener('DOMContentLoaded', () => {
    const calendarGrid = document.getElementById('calendar-grid');
    const calendarList = document.getElementById('calendar-list');
    const calendarWeekdays = document.getElementById('calendar-weekdays');
    const monthYearDisplay = document.getElementById('month-year');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const btnExportPdf = document.getElementById('btn-export-pdf');
    const form = document.getElementById('evaluation-form');
    const formMessage = document.getElementById('form-message');
    const sidebar = document.getElementById('sidebar');
    const userControls = document.getElementById('user-controls');
    const modalFooter = document.getElementById('modal-footer');
    
    // Filtros
    const filterCurso = document.getElementById('filter-curso');
    const filterAsignatura = document.getElementById('filter-asignatura');
    const filterProfesor = document.getElementById('filter-profesor');
    const profesorFilterGroup = document.getElementById('profesor-filter-group');
    const formCurso = document.getElementById('curso');
    const formAsignatura = document.getElementById('asignatura');
    
    // Modal elements
    const modal = document.getElementById('eval-modal');
    const closeModal = document.querySelector('.close-modal');
    const modalAsignatura = document.getElementById('modal-asignatura');
    const modalCurso = document.getElementById('modal-curso');
    const modalProfesor = document.getElementById('modal-profesor');
    const modalFecha = document.getElementById('modal-fecha');
    const modalHora = document.getElementById('modal-hora');
    const modalTipo = document.getElementById('modal-tipo');
    const modalObservaciones = document.getElementById('modal-observaciones');
    const modalObsContainer = document.getElementById('modal-obs-container');
    const btnDelete = document.getElementById('btn-delete');
    const btnEdit = document.getElementById('btn-edit');

    let currentDate = new Date();
    let evaluations = [];
    let currentEventIdToDelete = null;
    let currentEventIdToEdit = null;
    let currentUser = null;

    // Configuración de base URL para API (Ajustar si está en subcarpeta)
    // Para probar local en la misma carpeta o en cPanel, normalmente es un path relativo.
    const API_BASE = 'api/';

    // Colores por tipo de evaluación (mapeado desde CSS)
    const typeColors = {
        'Prueba': 'var(--color-prueba)',
        'Trabajo': 'var(--color-trabajo)',
        'Disertacion': 'var(--color-disertacion)',
        'Control': 'var(--color-control)',
        'Otro': 'var(--color-otro)'
    };

    // Formateador de fecha
    const getMonthName = (date) => {
        return date.toLocaleString('es-ES', { month: 'long', year: 'numeric' });
    };

    if(btnExportPdf) {
        btnExportPdf.addEventListener('click', () => {
            window.print();
        });
    }

    // Mapa de asignaturas dinámicas
    const subjectMap = {
        'basica_media': [
            "Lenguaje y Comunicación", "Inglés", "Matemática", "Historia Geografía y Ciencias Sociales",
            "Ciencias Naturales", "Tecnología", "Educación Física y Salud", "Artes Visuales", "Música",
            "Orientación", "Religión", "Taller de Recuperación de Habilidades de Lenguaje",
            "Taller de Recuperación de Habilidades de Matemática", "DIA", "Velocidad Lectora", "SIMCE"
        ],
        'telecomunicaciones': [
            "Lengua y Literatura", "Inglés", "Matemática", "Educación Ciudadana", "Filosofía",
            "Ciencias para la Ciudadanía", "Religión", "Taller de Educación Física y Salud",
            "Taller de Orientación", "Taller de Inglés", "Operación y Fundamentos de las Telecomunicaciones",
            "Instalación y Mantenimiento Básico de un Terminal Informático", "Instalación y Configuración de Redes",
            "Mantenimiento de Circuitos Electrónicos Básicos", "Instalación de Servicios Básicos de Telecomunicaciones",
            "DIA", "Velocidad Lectora", "SIMCE"
        ],
        'muebles': [
            "Lengua y Literatura", "Inglés", "Matemática", "Educación Ciudadana", "Filosofía",
            "Ciencias para la Ciudadanía", "Religión", "Taller de Educación Física y Salud",
            "Taller de Orientación", "Taller de Inglés", "Abastecimiento y Despacho",
            "Fabricación de Componentes de Carpintería y Muebles", "Cubicaciones",
            "Aseguramiento de la Calidad, Seguridad y Cuidado del Medio Ambiente",
            "Representación Gráfica de Muebles y Elementos de Carpintería",
            "DIA", "Velocidad Lectora", "SIMCE"
        ],
        'enfermeria': [
            "Lengua y Literatura", "Inglés", "Matemática", "Educación Ciudadana", "Filosofía",
            "Ciencias para la Ciudadanía", "Religión", "Taller de Educación Física y Salud",
            "Taller de Orientación", "Taller de Inglés", "Aplicación de Cuidados Básicos",
            "Medición y Control de Parámetros Básicos en Salud",
            "Promoción de la Salud y Prevención de la Enfermedad",
            "Higiene y Bioseguridad del Ambiente", "Sistema de Registro e Información en Salud",
            "DIA", "Velocidad Lectora", "SIMCE"
        ]
    };
    const TODAS_LAS_ASIGNATURAS = [
        "Lenguaje y Comunicación", "Inglés", "Matemática", "Historia Geografía y Ciencias Sociales",
        "Ciencias Naturales", "Tecnología", "Educación Física y Salud", "Artes Visuales", "Música",
        "Orientación", "Religión", "Taller de Recuperación de Habilidades de Lenguaje",
        "Taller de Recuperación de Habilidades de Matemática", "DIA", "Velocidad Lectora", "SIMCE",
        "Lengua y Literatura", "Educación Ciudadana", "Filosofía", "Ciencias para la Ciudadanía", 
        "Taller de Educación Física y Salud", "Taller de Orientación", "Taller de Inglés", 
        "Operación y Fundamentos de las Telecomunicaciones", "Instalación y Mantenimiento Básico de un Terminal Informático", 
        "Instalación y Configuración de Redes", "Mantenimiento de Circuitos Electrónicos Básicos", 
        "Instalación de Servicios Básicos de Telecomunicaciones", "Abastecimiento y Despacho",
        "Fabricación de Componentes de Carpintería y Muebles", "Cubicaciones",
        "Aseguramiento de la Calidad, Seguridad y Cuidado del Medio Ambiente",
        "Representación Gráfica de Muebles y Elementos de Carpintería",
        "Aplicación de Cuidados Básicos", "Medición y Control de Parámetros Básicos en Salud",
        "Promoción de la Salud y Prevención de la Enfermedad",
        "Higiene y Bioseguridad del Ambiente", "Sistema de Registro e Información en Salud"
    ].sort();

    function populateAsignaturas(cursoSelect, asignaturaSelect, includeAllOption = false) {
        asignaturaSelect.innerHTML = '';
        asignaturaSelect.disabled = false;
        
        if (includeAllOption) {
            asignaturaSelect.innerHTML = `<option value="all" selected>Todas las asignaturas</option>`;
        } else {
            asignaturaSelect.innerHTML = `<option value="" disabled selected>Selecciona una asignatura...</option>`;
        }

        let asignaturasToShow = [];
        
        if (currentUser && currentUser.rol === 'profesor') {
            asignaturasToShow = currentUser.asignaturas_asignadas || [];
        } else {
            asignaturasToShow = TODAS_LAS_ASIGNATURAS;
        }

        asignaturasToShow.forEach(sub => {
            const opt = document.createElement('option');
            opt.value = sub;
            opt.textContent = sub;
            asignaturaSelect.appendChild(opt);
        });
        
        if (!includeAllOption && asignaturasToShow.length === 0) {
            asignaturaSelect.innerHTML = `<option value="" disabled selected>No tienes asignaturas autorizadas.</option>`;
            asignaturaSelect.disabled = true;
        }
    }

    // Inicializar filtros
    if (formCurso && filterCurso) {
        Array.from(formCurso.options).forEach(opt => {
            if (opt.value !== '') {
                const newOpt = document.createElement('option');
                newOpt.value = opt.value;
                newOpt.textContent = opt.textContent;
                filterCurso.appendChild(newOpt);
            }
        });

        // Initialize immediately
        populateAsignaturas(formCurso, formAsignatura, false);
        populateAsignaturas(filterCurso, filterAsignatura, true);

        formCurso.addEventListener('change', () => {
            populateAsignaturas(formCurso, formAsignatura, false);
        });

        filterCurso.addEventListener('change', () => {
            populateAsignaturas(filterCurso, filterAsignatura, true);
            const viewType = document.getElementById('view-type-selector').value;
            if (viewType === 'evaluaciones') {
                renderCalendar();
            } else if (viewType === 'horarios') {
                const selectedOpt = filterCurso.options[filterCurso.selectedIndex];
                const dbId = selectedOpt.dataset.dbId;
                if (dbId) {
                    loadHorario(dbId);
                } else {
                    renderEmptyTabla(); // Limpiar si no hay curso válido o ID
                }
            }
        });

        filterAsignatura.addEventListener('change', renderCalendar);
    }
    
    // Manejador del Selector de Tipo de Vista (Mover fuera del if formCurso)
    const viewTypeSelector = document.getElementById('view-type-selector');
    if(viewTypeSelector) {
        viewTypeSelector.addEventListener('change', (e) => {
            const viewType = e.target.value;
            const mesControles = document.querySelector('.calendar-header div:first-child');
            const calGrid = document.querySelector('.calendar-container');
            const asigFiltro = document.getElementById('filter-asignatura') ? document.getElementById('filter-asignatura').parentElement : null;
            const profFiltro = document.getElementById('profesor-filter-group');
            const btnPdf = document.getElementById('btn-export-pdf');
            
            const horCont = document.getElementById('horario-container');
            
            if (viewType === 'evaluaciones') {
                if(mesControles) mesControles.style.display = 'flex';
                if(calGrid) calGrid.style.display = 'block';
                if(asigFiltro) asigFiltro.style.display = 'block';
                if(currentUser && currentUser.rol === 'admin' && profFiltro) profFiltro.style.display = 'flex';
                if(horCont) horCont.style.display = 'none';
                if(btnPdf) btnPdf.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Exportar a PDF';
                
                const filterCurso = document.getElementById('filter-curso');
                if (filterCurso) {
                    const allOpt = filterCurso.querySelector('option[value="all"]');
                    if (allOpt) allOpt.style.display = '';
                }

                if(typeof renderCalendar === 'function') renderCalendar();
            } else {
                if(mesControles) mesControles.style.display = 'none';
                if(calGrid) calGrid.style.display = 'none';
                if(asigFiltro) asigFiltro.style.display = 'none';
                if(profFiltro) profFiltro.style.display = 'none';
                if(horCont) horCont.style.display = 'block';
                if(btnPdf) btnPdf.innerHTML = '<i class="fa-solid fa-print"></i> Imprimir Horario';
                
                const filterCurso = document.getElementById('filter-curso');
                if(filterCurso) {
                    const allOpt = filterCurso.querySelector('option[value="all"]');
                    if (allOpt) allOpt.style.display = 'none';
                    
                    if (filterCurso.value === 'all' && filterCurso.options.length > 1) {
                        filterCurso.selectedIndex = 1;
                        populateAsignaturas(filterCurso, document.getElementById('filter-asignatura'), true);
                    }
                }
                
                if(filterCurso && filterCurso.selectedIndex >= 0) {
                    const selectedOpt = filterCurso.options[filterCurso.selectedIndex];
                    const dbId = selectedOpt ? selectedOpt.dataset.dbId : null;
                    if (dbId && typeof loadHorario === 'function') loadHorario(dbId);
                    else if(typeof renderEmptyTabla === 'function') renderEmptyTabla();
                } else if(typeof renderEmptyTabla === 'function') {
                    renderEmptyTabla();
                }
            }
        });
    }

    // --- Lógica de Horarios Semanales ---
    const bloquesHorario = [
        { id: 1, hora: "08:30 - 09:15" },
        { id: 2, hora: "09:15 - 10:00" },
        { id: 0, hora: "10:00 - 10:20", label: "RECREO" },
        { id: 3, hora: "10:20 - 11:05" },
        { id: 4, hora: "11:05 - 11:50" },
        { id: 0, hora: "11:50 - 12:00", label: "RECREO" },
        { id: 5, hora: "12:00 - 12:45" },
        { id: 6, hora: "12:45 - 13:30" },
        { id: 0, hora: "13:30 - 14:25", label: "ALMUERZO" },
        { id: 7, hora: "14:25 - 15:10" },
        { id: 8, hora: "15:10 - 15:55" }
    ];

    function renderEmptyTabla() {
        const tbody = document.getElementById('tbody-horario');
        if(!tbody) return;
        tbody.innerHTML = '';
        
        bloquesHorario.forEach(b => {
            const tr = document.createElement('tr');
            if(b.id === 0) {
                tr.style.background = '#f1f5f9';
                tr.style.fontStyle = 'italic';
                tr.style.color = 'var(--text-muted)';
                tr.style.height = '40px';
                tr.innerHTML = `<td style="border: 1px solid var(--border-color); padding: 0.5rem; height: 40px;"><strong>${b.hora}</strong></td><td colspan="5" style="border: 1px solid var(--border-color); padding: 0.5rem; letter-spacing:4px;">${b.label}</td>`;
            } else {
                tr.style.height = '95px';
                let html = `<td style="border: 1px solid var(--border-color); padding: 0.5rem; height: 95px;"><strong>${b.hora}</strong><br><small style="color:var(--text-muted)">Bloque ${b.id}</small></td>`;
                for(let dia = 1; dia <= 5; dia++) {
                    html += `<td style="border: 1px solid var(--border-color); padding: 0.5rem; height: 95px;"><div class="horario-cell empty" id="cell-${dia}-${b.id}" style="height: 100%; min-height: 80px; display: flex; align-items: center; justify-content: center; background: #fafafa; color: #94a3b8; font-style: italic; border-radius: 4px; padding: 4px; font-weight: 500; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis;">--</div></td>`;
                }
                tr.innerHTML = html;
            }
            tbody.appendChild(tr);
        });
    }

    async function loadHorario(curso_id) {
        renderEmptyTabla();
        try {
            const res = await fetch(`api/horarios/public_get_horario.php?curso_id=${curso_id}`);
            const data = await res.json();
            if(data.success) {
                data.data.forEach(clase => {
                    const cell = document.getElementById(`cell-${clase.dia_semana}-${clase.bloque}`);
                    if(cell) {
                        cell.innerHTML = `<span>${clase.asignatura_nombre}</span>`;
                        cell.style.backgroundColor = clase.color;
                        cell.style.color = '#ffffff';
                        cell.style.boxShadow = "0 2px 4px rgba(0,0,0,0.1)";
                        cell.style.fontStyle = 'normal';
                    }
                });
            }
        } catch (e) {
            console.error("Error cargando el horario:", e);
        }
    }

    async function loadDbCursos() {
        try {
            const res = await fetch('api/horarios/public_get_cursos.php');
            const data = await res.json();
            if(data.success && filterCurso) {
                const options = Array.from(filterCurso.options);
                data.data.forEach(c => {
                    // Match by name
                    const opt = options.find(o => o.value === c.nombre);
                    if(opt) opt.dataset.dbId = c.id;
                });
            }
        } catch(e) {
            console.error("Error cargando IDs de cursos de DB");
        }
    }

    // Función para obtener evaluaciones del backend
    async function fetchEvaluations() {
        try {
            const response = await fetch(`${API_BASE}get_evaluaciones.php`, { method: 'POST' });
            if (response.ok) {
                evaluations = await response.json();
                console.log("Evaluaciones cargadas desde la BD:", evaluations.length);
                renderCalendar();
            } else {
                console.error("Error al cargar evaluaciones", response.status);
            }
        } catch (error) {
            console.error("Fetch Error:", error);
            renderCalendar();
        }
    }

    // Verificar si el usuario ha iniciado sesión
    async function checkAuth() {
        try {
            const response = await fetch(`${API_BASE}check_session.php?t=` + new Date().getTime());
            const data = await response.json();
            if (data.authenticated) {
                currentUser = data.user;
                sidebar.style.display = 'flex';
                
                let adminLink = currentUser.rol === 'admin' ? '<a href="admin.html" style="margin-right: 15px; color: var(--primary-color); font-weight: bold; text-decoration: none;">Panel Admin</a>' : '';
                
                userControls.innerHTML = `
                    ${adminLink}
                    <span style="margin-right: 15px;">Hola, <strong>${currentUser.nombre}</strong></span>
                    <button id="btn-logout-main" class="btn-danger" style="padding: 0.5rem 1rem;">Cerrar Sesión</button>
                `;
                

                document.getElementById('btn-logout-main').addEventListener('click', async () => {
                    await fetch(`${API_BASE}logout.php`);
                    window.location.reload();
                });
                
                // Rellenar el nombre del profesor automáticamente en el formulario
                const profesorInput = document.getElementById('profesor');
                if(profesorInput) {
                    profesorInput.value = currentUser.nombre;
                }
                
                // Mostrar filtro de profesores solo para administrador
                if (currentUser.rol === 'admin' && profesorFilterGroup && filterProfesor) {
                    profesorFilterGroup.style.display = 'flex';
                    loadProfesores();
                    filterProfesor.addEventListener('change', renderCalendar);
                }
                
                // Actualizar asignaturas segn permisos del usuario
                if (document.getElementById('curso') && document.getElementById('filter-curso')) {
                    populateAsignaturas(document.getElementById('curso'), document.getElementById('asignatura'), false);
                    populateAsignaturas(document.getElementById('filter-curso'), document.getElementById('filter-asignatura'), true);
                }
            } else {
                if (data.reason === 'timeout') {
                    alert('⏳ Tu sesión ha expirado por inactividad (30 minutos). Por favor, inicia sesión nuevamente.');
                    window.location.reload();
                } else if (data.reason === 'concurrent') {
                    alert('⚠️ Se ha iniciado sesión en otro dispositivo. Por seguridad, se cerrará esta sesión activa.');
                    window.location.reload();
                }
            }
        } catch (e) {
            console.warn('Error verificando sesión', e);
        }
    }

    // Renderizar Calendario
    function renderCalendar() {
        calendarGrid.innerHTML = '';
        monthYearDisplay.textContent = getMonthName(currentDate);

        // --- Aplicar Filtros Globales ---
        const selCurso = filterCurso ? filterCurso.value : 'all';
        const selAsig = filterAsignatura && !filterAsignatura.disabled ? filterAsignatura.value : 'all';
        const selProf = (filterProfesor && profesorFilterGroup && profesorFilterGroup.style.display !== 'none') ? filterProfesor.value : 'all';

        const filteredEvaluations = evaluations.filter(ev => {
            if (selCurso !== 'all' && ev.curso !== selCurso) return false;
            if (selAsig !== 'all' && ev.asignatura !== selAsig) return false;
            if (selProf !== 'all' && ev.profesor !== selProf) return false;
            return true;
        });

        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        
        // getDay() devuelve 0 para Domingo, 1 para Lunes. 
        // Como nuestro calendario empieza en Lunes, ajustamos:
        const startingDay = (firstDay.getDay() + 6) % 7; 
        const totalDays = lastDay.getDate();

        // Celdas vacías iniciales
        for (let i = 0; i < startingDay; i++) {
            const emptyDiv = document.createElement('div');
            emptyDiv.classList.add('calendar-day', 'empty');
            calendarGrid.appendChild(emptyDiv);
        }

        const today = new Date();

        // Días del mes
        for (let i = 1; i <= totalDays; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('calendar-day');

            // Comprobar si es hoy
            if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
                dayDiv.classList.add('today');
            }

            const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
            
            dayDiv.innerHTML = `<span class="day-number">${i}</span>`;
            
            const eventsContainer = document.createElement('div');
            eventsContainer.classList.add('events-container');

            // Filtrar eventos para este día usando los datos ya filtrados globalmente
            const dayEvents = filteredEvaluations.filter(ev => ev.fecha === dateString);
            
            const MAX_VISIBLE = 2;
            dayEvents.forEach((ev, idx) => {
                if (idx >= MAX_VISIBLE) return; // No renderizar los extras, solo el badge +N

                const badge = document.createElement('div');
                badge.classList.add('event-badge');
                
                const color = typeColors[ev.tipo] || typeColors['Otro'];
                badge.style.backgroundColor = color;
                badge.style.borderColor = color;

                  let obsHtml = '';
                  if (ev.observaciones && ev.observaciones.trim() !== '') {
                      obsHtml = `<br><strong>Obs:</strong> ${ev.observaciones}`;
                  }

                  badge.innerHTML = `
                      <span class="screen-title">${ev.asignatura} - ${ev.curso}</span>
                      <div class="print-details">
                          <strong>Prof.:</strong> ${ev.profesor}<br>
                          <strong>Hora:</strong> ${ev.hora ? ev.hora.substring(0,5) : 'N/A'}<br>
                          <strong>Tipo:</strong> ${ev.tipo}${obsHtml}
                      </div>
                  `;
                
                badge.addEventListener('click', () => openModal(ev));
                eventsContainer.appendChild(badge);
            });

            // Si hay más de MAX_VISIBLE evaluaciones, mostrar badge "+N más"
            if (dayEvents.length > MAX_VISIBLE) {
                const extra = dayEvents.length - MAX_VISIBLE;
                const moreBadge = document.createElement('div');
                moreBadge.classList.add('events-more-badge');
                moreBadge.textContent = `+${extra} más`;
                moreBadge.title = dayEvents.slice(MAX_VISIBLE).map(e => e.asignatura).join(', ');
                // Al hacer clic muestra el modal de la primera evaluación oculta
                moreBadge.addEventListener('click', () => openModal(dayEvents[MAX_VISIBLE]));
                eventsContainer.appendChild(moreBadge);
            }

            dayDiv.appendChild(eventsContainer);
            calendarGrid.appendChild(dayDiv);
        }

        // --- Render List View ---
        if (calendarList) {
            calendarList.innerHTML = '';
            let hasEvents = false;
            
            for (let i = 1; i <= totalDays; i++) {
                const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                const dayEvents = filteredEvaluations.filter(ev => ev.fecha === dateString);
                
                if (dayEvents.length > 0) {
                    hasEvents = true;
                    const card = document.createElement('div');
                    card.className = 'list-day-card';
                    
                    const dateObj = new Date(year, month, i);
                    const dayName = dateObj.toLocaleDateString('es-ES', { weekday: 'long' });
                    
                    card.innerHTML = `
                        <div class="list-day-header">
                            <span class="badge-date">${i}</span>
                            <span style="text-transform: capitalize;">${dayName}</span>
                        </div>
                        <div class="list-events" id="list-events-${i}"></div>
                    `;
                    calendarList.appendChild(card);
                    
                    const eventsList = card.querySelector(`#list-events-${i}`);
                    const LIST_MAX = 3;
                    dayEvents.forEach((ev, idx) => {
                        if (idx >= LIST_MAX) return;
                        const item = document.createElement('div');
                        item.className = 'list-event-item';
                        item.style.borderLeftColor = typeColors[ev.tipo] || typeColors['Otro'];
                        item.innerHTML = `
                            <div class="list-event-title">${ev.asignatura} - ${ev.curso}</div>
                            <div class="list-event-meta">
                                <span><i class="fa-solid fa-clock"></i> ${ev.hora ? ev.hora.substring(0,5) : 'N/A'}</span>
                                <span><i class="fa-solid fa-file-pen"></i> ${ev.tipo}</span>
                                <span><i class="fa-solid fa-user"></i> ${ev.profesor}</span>
                            </div>
                        `;
                        item.addEventListener('click', () => openModal(ev));
                        eventsList.appendChild(item);
                    });
                    // Badge +N si hay más de LIST_MAX — al hacer clic expande todas las ocultas
                    if (dayEvents.length > LIST_MAX) {
                        const extra = dayEvents.length - LIST_MAX;

                        // 1. Primero crear y añadir los items ocultos al DOM
                        const hiddenItems = [];
                        dayEvents.slice(LIST_MAX).forEach(ev => {
                            const item = document.createElement('div');
                            item.className = 'list-event-item';
                            item.style.borderLeftColor = typeColors[ev.tipo] || typeColors['Otro'];
                            item.style.display = 'none'; // oculto por defecto
                            item.innerHTML = `
                                <div class="list-event-title">${ev.asignatura} - ${ev.curso}</div>
                                <div class="list-event-meta">
                                    <span><i class="fa-solid fa-clock"></i> ${ev.hora ? ev.hora.substring(0,5) : 'N/A'}</span>
                                    <span><i class="fa-solid fa-file-pen"></i> ${ev.tipo}</span>
                                    <span><i class="fa-solid fa-user"></i> ${ev.profesor}</span>
                                </div>
                            `;
                            item.addEventListener('click', () => openModal(ev));
                            eventsList.appendChild(item); // append directo, sin insertBefore
                            hiddenItems.push(item);
                        });

                        // 2. Después crear y añadir el badge al final
                        const moreBadge = document.createElement('div');
                        moreBadge.className = 'events-more-badge';
                        moreBadge.style.width = '100%';
                        moreBadge.style.textAlign = 'center';
                        moreBadge.style.justifyContent = 'center';
                        moreBadge.textContent = `+${extra} más evaluaciones`;
                        moreBadge.dataset.expanded = 'false';

                        // Toggle al hacer clic en el badge
                        moreBadge.addEventListener('click', () => {
                            const expanded = moreBadge.dataset.expanded === 'true';
                            if (!expanded) {
                                hiddenItems.forEach(it => it.style.display = '');
                                moreBadge.textContent = 'Ver menos ▲';
                                moreBadge.dataset.expanded = 'true';
                            } else {
                                hiddenItems.forEach(it => it.style.display = 'none');
                                moreBadge.textContent = `+${extra} más evaluaciones`;
                                moreBadge.dataset.expanded = 'false';
                            }
                        });

                        eventsList.appendChild(moreBadge);
                    }
                }
            }
            
            if (!hasEvents) {
                calendarList.innerHTML = '<div class="no-events-msg">No hay evaluaciones programadas para este mes.</div>';
            }
        }
    }

    // Cambiar Mes
    prevMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    });

    nextMonthBtn.addEventListener('click', () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    });

    // Agregar Evaluación
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btnSubmit = form.querySelector('.btn-submit');
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';
        btnSubmit.disabled = true;

        const newEval = {
            asignatura: document.getElementById('asignatura').value,
            curso: document.getElementById('curso').value,
            profesor: document.getElementById('profesor').value,
            fecha: document.getElementById('fecha').value,
            hora: document.getElementById('hora').value,
            tipo: document.getElementById('tipo').value,
            observaciones: document.getElementById('observaciones') ? document.getElementById('observaciones').value : ''
        };

        const endpoint = currentEventIdToEdit ? 'edit_evaluacion.php' : 'add_evaluacion.php';
        if (currentEventIdToEdit) newEval.id = currentEventIdToEdit;

        try {
            const response = await fetch(`${API_BASE}${endpoint}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newEval)
            });

            if (response.ok) {
                const data = await response.json();
                if(data.success) {
                    showMessage(currentEventIdToEdit ? 'Evaluación actualizada correctamente.' : 'Evaluación agregada correctamente.', 'success');
                    form.reset();
                    if(currentEventIdToEdit) {
                        currentEventIdToEdit = null;
                        btnSubmit.innerHTML = 'Agregar Evaluación';
                    }
                    if(currentUser && document.getElementById('profesor')) {
                        document.getElementById('profesor').value = currentUser.nombre;
                    }
                    fetchEvaluations(); // Recargar datos
                } else {
                    showMessage(data.error || 'Error al agregar', 'error');
                }
            } else {
                showMessage('Error del servidor (HTTP ' + response.status + ')', 'error');
            }
        } catch (error) {
            console.error("Error de conexión:", error);
            showMessage('Error de red. Verifica tu conexión e intenta de nuevo.', 'error');
        } finally {
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
        }
    });

    // Mostrar mensaje en el form
    function showMessage(msg, type) {
        formMessage.textContent = msg;
        formMessage.className = `form-message ${type}`;
        setTimeout(() => {
            formMessage.style.display = 'none';
        }, 3000);
    }

    // Modal logic
    function openModal(ev) {
        currentEventIdToDelete = ev.id;
        modalAsignatura.textContent = ev.asignatura;
        modalCurso.textContent = ev.curso;
        modalProfesor.textContent = ev.profesor;
        
        // Formatear fecha para lectura
        const partes = ev.fecha.split('-');
        modalFecha.textContent = `${partes[2]}/${partes[1]}/${partes[0]}`;
        
        modalHora.textContent = ev.hora;
        
        modalTipo.textContent = ev.tipo;
        modalTipo.style.backgroundColor = typeColors[ev.tipo] || typeColors['Otro'];
        
        if (ev.observaciones && ev.observaciones.trim() !== '') {
            modalObservaciones.textContent = ev.observaciones;
            modalObsContainer.style.display = 'block';
        } else {
            modalObsContainer.style.display = 'none';
        }
        
        if (currentUser && (currentUser.nombre === ev.profesor || currentUser.rol === 'admin')) {
            modalFooter.style.display = 'flex';
        } else {
            modalFooter.style.display = 'none';
        }
        
        modal.classList.add('show');
    }

    closeModal.addEventListener('click', () => {
        modal.classList.remove('show');
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });

    // Editar Evaluación
    if(btnEdit) {
        btnEdit.addEventListener('click', () => {
            const ev = evaluations.find(e => e.id === currentEventIdToDelete);
            if(!ev) return;
            
            document.getElementById('asignatura').value = ev.asignatura;
            document.getElementById('curso').value = ev.curso;
            populateAsignaturas(document.getElementById('curso'), document.getElementById('asignatura'), false);
            // Restaurar el valor exacto tras rellenar
            setTimeout(() => document.getElementById('asignatura').value = ev.asignatura, 10);
            
            document.getElementById('fecha').value = ev.fecha;
            document.getElementById('hora').value = ev.hora;
            document.getElementById('tipo').value = ev.tipo;
            if(document.getElementById('observaciones')) {
                document.getElementById('observaciones').value = ev.observaciones || '';
            }
            
            currentEventIdToEdit = ev.id;
            
            const btnSubmit = form.querySelector('.btn-submit');
            btnSubmit.innerHTML = '<i class="fa-solid fa-save"></i> Actualizar Evaluación';
            
            modal.classList.remove('show');
            document.querySelector('.sidebar').scrollIntoView({ behavior: 'smooth' });
        });
    }

    // Eliminar Evaluación
    btnDelete.addEventListener('click', async () => {
        if (!currentEventIdToDelete) return;
        if (!confirm('¿Estás seguro de eliminar esta evaluación?')) return;

        const originalText = btnDelete.innerHTML;
        btnDelete.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>...';
        btnDelete.disabled = true;

        try {
            const response = await fetch(`${API_BASE}delete_evaluacion.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: currentEventIdToDelete })
            });

            if (response.ok) {
                modal.classList.remove('show');
                fetchEvaluations();
            } else {
                alert('Error del servidor al intentar eliminar la evaluación.');
            }
        } catch (e) {
            console.error("Error al eliminar:", e);
            alert('Error de conexión. No se pudo eliminar.');
        } finally {
            btnDelete.innerHTML = originalText;
            btnDelete.disabled = false;
        }
    });

    // Cargar profesores registrados (solo admin)
    async function loadProfesores() {
        try {
            const response = await fetch(`${API_BASE}get_profesores.php?t=` + new Date().getTime());
            if (response.ok) {
                const data = await response.json();
                if (data.success && data.profesores && filterProfesor) {
                    // Limpiar opciones previas si las hay
                    filterProfesor.innerHTML = '<option value="all" selected>Todos los profesores</option>';
                    data.profesores.forEach(prof => {
                        const opt = document.createElement('option');
                        opt.value = prof;
                        opt.textContent = prof;
                        filterProfesor.appendChild(opt);
                    });
                }
            }
        } catch (e) {
            console.error('Error cargando profesores', e);
        }
    }


    // Initialize Base Data
    loadDbCursos();
    fetchEvaluations();
    checkAuth();
});
