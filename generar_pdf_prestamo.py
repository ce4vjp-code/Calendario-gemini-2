import os
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle, Image, HRFlowable, KeepTogether
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.pdfgen import canvas

class NumberedCanvas(canvas.Canvas):
    def __init__(self, *args, **kwargs):
        canvas.Canvas.__init__(self, *args, **kwargs)
        self._saved_page_states = []

    def showPage(self):
        self._saved_page_states.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        num_pages = len(self._saved_page_states)
        print(f"==========================================")
        print(f"TOTAL DE PÁGINAS EN PDF PRÉSTAMO: {num_pages}")
        print(f"==========================================")
        for state in self._saved_page_states:
            self.__dict__.update(state)
            canvas.Canvas.showPage(self)
        canvas.Canvas.save(self)

def crear_pdf():
    pdf_filename = r"c:\Users\Cristian\Downloads\Calendario gemini\Acta_Prestamo_Notebook_Profesores.pdf"
    
    # Document setup: Letter size with balanced 1.0 cm margins to fill page
    doc = SimpleDocTemplate(
        pdf_filename,
        pagesize=letter,
        leftMargin=1.0*cm,
        rightMargin=1.0*cm,
        topMargin=1.0*cm,
        bottomMargin=1.0*cm
    )
    
    styles = getSampleStyleSheet()
    
    # Well-proportioned fonts and line heights
    title_style = ParagraphStyle(
        'DocTitle',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=14,
        textColor=colors.HexColor('#1A365D'),
        alignment=2
    )
    
    subtitle_style = ParagraphStyle(
        'DocSubtitle',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=11,
        textColor=colors.HexColor('#4A5568'),
        alignment=2
    )
    
    main_heading = ParagraphStyle(
        'MainHeading',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=12,
        leading=14,
        textColor=colors.HexColor('#2B6CB0'),
        alignment=1
    )
    
    sub_main_heading = ParagraphStyle(
        'SubMainHeading',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=9,
        leading=11,
        textColor=colors.HexColor('#2D3748'),
        alignment=1
    )
    
    section_heading = ParagraphStyle(
        'SectionHeading',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=9,
        leading=11,
        textColor=colors.white
    )
    
    cell_bold = ParagraphStyle(
        'CellBold',
        parent=styles['Normal'],
        fontName='Helvetica-Bold',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor('#2D3748')
    )
    
    cell_text = ParagraphStyle(
        'CellText',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8,
        leading=10,
        textColor=colors.HexColor('#1A202C')
    )

    cell_center = ParagraphStyle(
        'CellCenter',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=8,
        leading=10,
        alignment=1,
        textColor=colors.HexColor('#1A202C')
    )

    clause_text = ParagraphStyle(
        'ClauseText',
        parent=styles['Normal'],
        fontName='Helvetica',
        fontSize=7.8,
        leading=9.8,
        textColor=colors.HexColor('#1A202C')
    )

    story = []
    
    # 1. Header with Logo & Title
    logo_path = r"c:\Users\Cristian\Downloads\Calendario gemini\logo.png"
    if os.path.exists(logo_path):
        from PIL import Image as PILImage
        with PILImage.open(logo_path) as pimg:
            pw, ph = pimg.size
            aspect = pw / float(ph)
            target_h = 1.7 * cm
            target_w = target_h * aspect
            img = Image(logo_path, width=target_w, height=target_h)
        img.hAlign = 'LEFT'
    else:
        img = Paragraph("<b>LICEO GONZALO GUGLIELMI MONTIEL</b>", cell_bold)

    header_text_p = [
        Paragraph("LICEO TÉCNICO PROFESIONAL GONZALO GUGLIELMI MONTIEL", title_style),
        Spacer(1, 2),
        Paragraph("Departamento de Informática & Soporte Técnico TI", subtitle_style)
    ]
    
    header_table = Table([[img, header_text_p]], colWidths=[2.6*cm, 16.95*cm])
    header_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('ALIGN', (1,0), (1,0), 'RIGHT'),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
        ('TOPPADDING', (0,0), (-1,-1), 0),
    ]))
    story.append(header_table)
    story.append(HRFlowable(width="100%", thickness=1.2, color=colors.HexColor('#1A365D'), spaceBefore=3, spaceAfter=6))
    
    # 2. Banner Title
    banner_data = [[
        Paragraph("ACTA DE PRÉSTAMO Y ASIGNACIÓN DE EQUIPO PORTÁTIL PARA DOCENTES", main_heading),
    ], [
        Paragraph("Asignación de Uso Temporal / Apoyo a la Función Pedagógica", sub_main_heading)
    ]]
    banner_table = Table(banner_data, colWidths=[19.55*cm])
    banner_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#F7FAFC')),
        ('BOX', (0,0), (-1,-1), 0.8, colors.HexColor('#E2E8F0')),
        ('LINELEFT', (0,0), (0,-1), 3.5, colors.HexColor('#2B6CB0')),
        ('TOPPADDING', (0,0), (-1,-1), 4),
        ('BOTTOMPADDING', (0,0), (-1,-1), 4),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
    ]))
    story.append(banner_table)
    story.append(Spacer(1, 6))
    
    # Helper to build section box
    def make_section(title, content_flowables, spacer_after=6):
        header_p = Paragraph(title, section_heading)
        sec_header = Table([[header_p]], colWidths=[19.55*cm])
        sec_header.setStyle(TableStyle([
            ('BACKGROUND', (0,0), (-1,-1), colors.HexColor('#2B6CB0')),
            ('TOPPADDING', (0,0), (-1,-1), 3),
            ('BOTTOMPADDING', (0,0), (-1,-1), 3),
            ('LEFTPADDING', (0,0), (-1,-1), 6),
        ]))
        
        sec_content = Table([[content_flowables]], colWidths=[19.55*cm])
        sec_content.setStyle(TableStyle([
            ('BOX', (0,0), (-1,-1), 0.8, colors.HexColor('#CBD5E0')),
            ('BACKGROUND', (0,0), (-1,-1), colors.white),
            ('TOPPADDING', (0,0), (-1,-1), 4),
            ('BOTTOMPADDING', (0,0), (-1,-1), 4),
            ('LEFTPADDING', (0,0), (-1,-1), 6),
            ('RIGHTPADDING', (0,0), (-1,-1), 6),
        ]))
        return [sec_header, sec_content, Spacer(1, spacer_after)]

    # Section 1: Datos del Docente
    doc_col1 = [
        Paragraph("<b>Nombre Completo:</b> ___________________________________", cell_text),
        Spacer(1, 3),
        Paragraph("<b>RUT:</b> _______________________", cell_text),
        Spacer(1, 3),
        Paragraph("<b>Teléfono Contacto:</b> _________________", cell_text),
    ]
    doc_col2 = [
        Paragraph("<b>Asignatura / Depto:</b> _____________________________", cell_text),
        Spacer(1, 3),
        Paragraph("<b>Correo Institucional:</b> ___________________________", cell_text),
        Spacer(1, 3),
        Paragraph("<b>Fecha de Entrega:</b> _____ / _____ / 2026", cell_text),
    ]
    doc_table = Table([[doc_col1, doc_col2]], colWidths=[9.75*cm, 9.75*cm])
    doc_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LEFTPADDING', (0,0), (-1,-1), 2),
        ('RIGHTPADDING', (0,0), (-1,-1), 2),
    ]))
    story.extend(make_section("1. Datos del Docente (Usuario Responsable)", doc_table, 6))

    # Section 2: Detalle del Equipo Portátil
    eq_table_data = [
        [Paragraph("<b>Ítem / Componente</b>", cell_bold), Paragraph("<b>Marca / Modelo</b>", cell_bold), Paragraph("<b>N° de Serie (S/N)</b>", cell_bold), Paragraph("<b>N° Inventario TI</b>", cell_bold), Paragraph("<b>Estado Entrega</b>", cell_center)],
        [Paragraph("<b>Notebook / Laptop</b>", cell_text), Paragraph("______________________", cell_text), Paragraph("______________________", cell_text), Paragraph("______________________", cell_text), Paragraph("Operativo / Bueno", cell_center)],
        [Paragraph("<b>Cargador de Poder</b>", cell_text), Paragraph("______________________", cell_text), Paragraph("______________________", cell_text), Paragraph("______________________", cell_text), Paragraph("Operativo / Bueno", cell_center)]
    ]
    eq_t = Table(eq_table_data, colWidths=[3.5*cm, 4.3*cm, 4.3*cm, 4.2*cm, 3.25*cm])
    eq_t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#EDF2F7')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#CBD5E0')),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 3),
        ('RIGHTPADDING', (0,0), (-1,-1), 3),
    ]))
    
    acc_data = [
        [Paragraph("[<b>X</b>] Bolso / Funda de Transporte", cell_text), Paragraph("[<b>X</b>] Mouse Óptico (USB/Inalámbrico)", cell_text)],
        [Paragraph("[<b>X</b>] Adaptador de Video (HDMI/VGA)", cell_text), Paragraph("[<b>X</b>] Cable de Red / Conectividad WiFi", cell_text)]
    ]
    acc_t = Table(acc_data, colWidths=[9.75*cm, 9.75*cm])
    acc_t.setStyle(TableStyle([('TOPPADDING', (0,0), (-1,-1), 2), ('BOTTOMPADDING', (0,0), (-1,-1), 2)]))

    eq_content = [eq_t, Spacer(1, 3), acc_t]
    story.extend(make_section("2. Identificación del Equipo y Accesorios Entregados", eq_content, 6))

    # Section 3: Cláusulas y Condiciones
    clauses = [
        Paragraph("<b>1. Uso Exclusivo Institucional:</b> El equipo portátil es de propiedad del Liceo y se entrega para uso pedagógico, lectivo y administrativo del docente.", clause_text),
        Spacer(1, 2),
        Paragraph("<b>2. Obligación de Cuidado:</b> El docente asume la custodia directa y el compromiso de velar por la integridad física y lógica del bien.", clause_text),
        Spacer(1, 2),
        Paragraph("<b>3. RESPONSABILIDAD POR DAÑO O PÉRDIDA:</b> El usuario responderá directamente ante el establecimiento por cualquier daño, deterioro derivado del mal uso, o pérdida total/parcial del equipo y sus accesorios.", clause_text),
        Spacer(1, 2),
        Paragraph("<b>4. PLAZO DE REPOSICIÓN O PAGO (10 DÍAS HÁBILES):</b> En caso de extravío, hurto o pérdida del equipo portátil o periféricos, el docente tendrá un plazo perentorio de <b>diez (10) días hábiles</b> a contar de la fecha del evento para efectuar la <b>reposición directa de un equipo de similares o superiores características</b>, o en su defecto, <b>cancelar el valor comercial de reposición actualizado</b> de los bienes afectados.", clause_text),
        Spacer(1, 2),
        Paragraph("<b>5. Devolución:</b> El bien deberá ser restituido al Departamento de Informática al término del año escolar o cuando sea requerido por la Dirección.", clause_text)
    ]
    story.extend(make_section("3. Términos, Condiciones y Cláusulas de Responsabilidad", clauses, 6))

    # Section 4: Signatures - Increased top spacer to nicely balance the bottom of page
    sig1 = [
        Spacer(1, 40),
        HRFlowable(width="80%", thickness=1, color=colors.HexColor('#2D3748'), spaceAfter=3),
        Paragraph("<b>FIRMA DEL DOCENTE</b>", cell_center),
        Paragraph("Receptor Aceptante", ParagraphStyle('SubSig', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#4A5568'))),
        Paragraph("RUT: __________________", ParagraphStyle('SubSigR', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#718096')))
    ]
    sig2 = [
        Spacer(1, 40),
        HRFlowable(width="80%", thickness=1, color=colors.HexColor('#2D3748'), spaceAfter=3),
        Paragraph("<b>ENCARGADO SOPORTE TI</b>", cell_center),
        Paragraph("Entregado / Verificado", ParagraphStyle('SubSig', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#4A5568'))),
        Paragraph("RUT: __________________", ParagraphStyle('SubSigR', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#718096')))
    ]
    sig3 = [
        Spacer(1, 40),
        HRFlowable(width="80%", thickness=1, color=colors.HexColor('#2D3748'), spaceAfter=3),
        Paragraph("<b>DIRECCIÓN / JEFATURA</b>", cell_center),
        Paragraph("VoBo Institucional", ParagraphStyle('SubSig', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#4A5568'))),
        Paragraph("RUT: __________________", ParagraphStyle('SubSigR', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#718096')))
    ]
    
    sig_table = Table([[sig1, sig2, sig3]], colWidths=[6.51*cm, 6.51*cm, 6.51*cm])
    sig_table.setStyle(TableStyle([
        ('VALIGN', (0,0), (-1,-1), 'BOTTOM'),
        ('ALIGN', (0,0), (-1,-1), 'CENTER'),
        ('TOPPADDING', (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
    ]))
    
    story.append(KeepTogether(sig_table))

    doc.build(story, canvasmaker=NumberedCanvas)
    print("PDF de préstamo de página completa generado exitosamente.")

if __name__ == "__main__":
    crear_pdf()
