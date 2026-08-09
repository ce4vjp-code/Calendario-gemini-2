const loadPdfLibrary = () => {
    return new Promise((resolve, reject) => {
        if (window.html2pdf) {
            resolve(window.html2pdf);
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.onload = () => resolve(window.html2pdf);
        script.onerror = () => reject(new Error('No se pudo cargar html2pdf.js'));
        document.head.appendChild(script);
    });
};

const generarPDF = async (tipo, prestamoId, usuarioNombre, usuarioRut, usuarioEmail, equipoNombre, equipoMarca, equipoModelo, equipoSerie) => {
    await loadPdfLibrary();
    
    // Crear contenedor oculto para el PDF
    const container = document.createElement('div');
    container.style.position = 'absolute';
    container.style.left = '-9999px';
    container.style.top = '-9999px';
    
    const folio = `LAB-${String(prestamoId).padStart(5, '0')}`;
    const titulo = tipo === 'prestamo' ? 'ACTA DE PRÉSTAMO Y ASIGNACIÓN DE EQUIPO PORTÁTIL PARA DOCENTES' : 'ACTA DE RECEPCIÓN Y DEVOLUCIÓN DE EQUIPO PORTÁTIL';
    const subtitulo = tipo === 'prestamo' ? 'Asignación de Uso Temporal / Apoyo a la Función Pedagógica' : 'Constancia de Devolución al Departamento de Informática';
    
    const fechaActual = new Date().toLocaleDateString('es-CL');
    
    // Plantilla HTML basada en el formato original
    container.innerHTML = `
        <div id="pdf-content" style="width: 21cm; padding: 1.5cm; background: white; font-family: Helvetica, Arial, sans-serif; color: #1a202c; box-sizing: border-box;">
            <!-- Cabecera -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1a365d; padding-bottom: 10px; margin-bottom: 20px;">
                <div style="width: 3cm;">
                    <h2 style="margin:0; font-size:16px; color:#1a365d;">Liceo G.G.M.</h2>
                </div>
                <div style="text-align: right; flex-grow: 1;">
                    <h1 style="margin: 0; font-size: 14px; color: #1a365d;">LICEO TÉCNICO PROFESIONAL GONZALO GUGLIELMI MONTIEL</h1>
                    <p style="margin: 5px 0 0; font-size: 11px; color: #4a5568;">Departamento de Informática & Soporte Técnico TI</p>
                </div>
            </div>

            <!-- Banner -->
            <div style="background: #f7fafc; border: 1px solid #e2e8f0; border-left: 4px solid #2b6cb0; padding: 10px; text-align: center; margin-bottom: 20px;">
                <h2 style="margin: 0; font-size: 14px; color: #2b6cb0;">${titulo}</h2>
                <p style="margin: 5px 0 0; font-size: 11px; color: #2d3748;">${subtitulo}</p>
            </div>
            
            <div style="text-align: right; margin-bottom: 15px; font-weight: bold; font-size: 12px;">
                Folio: ${folio}
            </div>

            <!-- Sección 1 -->
            <div style="margin-bottom: 20px;">
                <div style="background: #2b6cb0; color: white; padding: 6px 10px; font-size: 12px; font-weight: bold;">
                    1. Datos del Usuario Responsable
                </div>
                <div style="border: 1px solid #cbd5e0; padding: 10px; font-size: 11px; display: flex;">
                    <div style="width: 50%;">
                        <p style="margin: 0 0 10px;"><b>Nombre Completo:</b> ${usuarioNombre}</p>
                        <p style="margin: 0 0 10px;"><b>RUT:</b> ${usuarioRut || '_______________________'}</p>
                        <p style="margin: 0;"><b>Teléfono Contacto:</b> _________________</p>
                    </div>
                    <div style="width: 50%;">
                        <p style="margin: 0 0 10px;"><b>Asignatura / Depto:</b> ____________________</p>
                        <p style="margin: 0 0 10px;"><b>Correo Institucional:</b> ${usuarioEmail || '____________________'}</p>
                        <p style="margin: 0;"><b>Fecha de Emisión:</b> ${fechaActual}</p>
                    </div>
                </div>
            </div>

            <!-- Sección 2 -->
            <div style="margin-bottom: 20px;">
                <div style="background: #2b6cb0; color: white; padding: 6px 10px; font-size: 12px; font-weight: bold;">
                    2. Identificación del Equipo y Accesorios
                </div>
                <div style="border: 1px solid #cbd5e0; padding: 10px; font-size: 11px;">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; text-align: left;">
                        <tr style="background: #edf2f7; border-bottom: 1px solid #cbd5e0;">
                            <th style="padding: 5px;">Ítem</th>
                            <th style="padding: 5px;">Marca/Modelo</th>
                            <th style="padding: 5px;">Nº Serie</th>
                            <th style="padding: 5px;">Estado</th>
                        </tr>
                        <tr>
                            <td style="padding: 5px; border-bottom: 1px solid #e2e8f0;">${equipoNombre}</td>
                            <td style="padding: 5px; border-bottom: 1px solid #e2e8f0;">${equipoMarca ? equipoMarca + ' ' + (equipoModelo||'') : '________________'}</td>
                            <td style="padding: 5px; border-bottom: 1px solid #e2e8f0;">${equipoSerie || '________________'}</td>
                            <td style="padding: 5px; border-bottom: 1px solid #e2e8f0;">Operativo</td>
                        </tr>
                        <tr>
                            <td style="padding: 5px;">Cargador de Poder</td>
                            <td style="padding: 5px;">________________</td>
                            <td style="padding: 5px;">________________</td>
                            <td style="padding: 5px;">Operativo</td>
                        </tr>
                    </table>
                    <div style="display: flex;">
                        <div style="width: 50%;">
                            <p style="margin: 0 0 5px;">[  ] Bolso / Funda de Transporte</p>
                            <p style="margin: 0;">[  ] Adaptador de Video (HDMI/VGA)</p>
                        </div>
                        <div style="width: 50%;">
                            <p style="margin: 0 0 5px;">[  ] Mouse Óptico</p>
                            <p style="margin: 0;">[  ] Cable de Red</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 3 -->
            <div style="margin-bottom: 40px;">
                <div style="background: #2b6cb0; color: white; padding: 6px 10px; font-size: 12px; font-weight: bold;">
                    ${tipo === 'prestamo' ? '3. Términos, Condiciones y Cláusulas de Responsabilidad' : '3. Constancia de Devolución'}
                </div>
                <div style="border: 1px solid #cbd5e0; padding: 10px; font-size: 10px; line-height: 1.4; text-align: justify;">
                    ${tipo === 'prestamo' ? `
                    <p style="margin: 0 0 8px;"><b>1. Uso Exclusivo Institucional:</b> El equipo es de propiedad del Liceo y se entrega para uso pedagógico y administrativo.</p>
                    <p style="margin: 0 0 8px;"><b>2. Obligación de Cuidado:</b> El usuario asume la custodia directa y el compromiso de velar por la integridad física y lógica del bien.</p>
                    <p style="margin: 0 0 8px;"><b>3. RESPONSABILIDAD POR DAÑO O PÉRDIDA:</b> El usuario responderá directamente por cualquier daño, deterioro por mal uso, o pérdida.</p>
                    <p style="margin: 0 0 8px;"><b>4. PLAZO DE REPOSICIÓN (10 DÍAS HÁBILES):</b> En caso de extravío o pérdida, el docente tendrá un plazo perentorio de diez (10) días hábiles para efectuar la reposición directa de un equipo de similares características, o en su defecto, cancelar el valor comercial.</p>
                    <p style="margin: 0;"><b>5. Devolución:</b> El bien deberá ser restituido al Departamento de Informática al término de la necesidad o cuando sea requerido.</p>
                    ` : `
                    <p style="margin: 0 0 8px;">Se deja constancia de que el usuario mencionado en la Sección 1 hace devolución formal del equipo computacional y los accesorios detallados en la Sección 2.</p>
                    <p style="margin: 0 0 8px;">Tras la revisión visual y técnica preliminar, el encargado del Departamento de Informática certifica que el equipo <b>ha sido devuelto en buenas condiciones operativas y físicas</b>, no presentando daños atribuibles a mal uso.</p>
                    <p style="margin: 0;">Por consiguiente, se exime al usuario de toda responsabilidad sobre el equipo a partir de la firma de este documento, dándose por concluido el préstamo.</p>
                    `}
                </div>
            </div>

            <!-- Firmas -->
            <div style="display: flex; justify-content: space-around; margin-top: 60px;">
                <div style="text-align: center; width: 40%;">
                    <div style="border-top: 1px solid #2d3748; padding-top: 5px;">
                        <b>FIRMA DEL USUARIO</b><br>
                        <span style="font-size: 10px;">Acepta conforme condiciones y equipo</span>
                    </div>
                </div>
                <div style="text-align: center; width: 40%;">
                    <div style="border-top: 1px solid #2d3748; padding-top: 5px;">
                        <b>FIRMA ENCARGADO TI / ADMIN</b><br>
                        <span style="font-size: 10px;">Entrega / Recepción conforme</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(container);

    const filename = `${folio}_${tipo === 'prestamo' ? 'Prestamo' : 'Recepcion'}_${usuarioNombre.replace(/\s+/g, '_')}.pdf`;

    const opt = {
        margin:       0,
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    try {
        await html2pdf().set(opt).from(container.querySelector('#pdf-content')).save();
    } catch (err) {
        console.error('Error generando PDF:', err);
        alert('Hubo un error al generar el PDF.');
    } finally {
        document.body.removeChild(container);
    }
};
