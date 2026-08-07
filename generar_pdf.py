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
        print(f"TOTAL DE PÁGINAS GENERADAS EN EL PDF: {num_pages}")
        print(f"==========================================")
        for state in self._saved_page_states:
            self.__dict__.update(state)
            canvas.Canvas.showPage(self)
        canvas.Canvas.save(self)

def crear_pdf():
    pdf_filename = r"c:\Users\Cristian\Downloads\Calendario gemini\Acta_Entrega_Retiro_Equipos_Biblioteca.pdf"
    
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
    
    # Custom well-proportioned styles
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
    story.append(HRFlowable(width="100%", thickness=1.2, color=colors.HexColor('#1A365D'), spaceBefore=3, spaceAfter=5))
    
    # 2. Banner Title
    banner_data = [[
        Paragraph("ACTA DE ENTREGA Y RETIRO DE EQUIPOS DE INFORMÁTICA", main_heading),
    ], [
        Paragraph("Reemplazo y Actualización de Equipos — Sala de Biblioteca (CRA)", sub_main_heading)
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
    story.append(Spacer(1, 5))
    
    # Helper to build section box
    def make_section(title, content_flowables, spacer_after=5):
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

    # Section 1: General Info
    info_col1 = [
        Paragraph("<b>Establecimiento:</b> Liceo T. P. Gonzalo Guglielmi Montiel", cell_text),
        Spacer(1, 2),
        Paragraph("<b>Ubicación:</b> Sala de Biblioteca (CRA)", cell_text),
        Spacer(1, 2),
        Paragraph("<b>Motivo:</b> Reemplazo y actualización (2 PC)", cell_text),
    ]
    info_col2 = [
        Paragraph("<b>Fecha de Ejecución:</b> _____ / _____ / 2026", cell_text),
        Spacer(1, 2),
        Paragraph("<b>Horario Intervención:</b> ____:____ a ____:____ hrs.", cell_text),
        Spacer(1, 2),
        Paragraph("<b>N° Ticket / Solicitud:</b> ________________________", cell_text),
    ]
    info_table = Table([[info_col1, info_col2]], colWidths=[9.75*cm, 9.75*cm])
    info_table.setStyle(TableStyle([('VALIGN', (0,0), (-1,-1), 'TOP')]))
    story.extend(make_section("1. Información General y Ubicación", info_table, 5))

    # Section 2: Personal
    pers_col1 = [
        Paragraph("<b>Técnico Responsable (Soporte TI):</b>", cell_bold),
        Spacer(1, 1),
        Paragraph("Nombre: __________________________________________", cell_text),
        Spacer(1, 1),
        Paragraph("RUT: ____________________________________________", cell_text),
    ]
    pers_col2 = [
        Paragraph("<b>Responsable Recepción (Biblioteca):</b>", cell_bold),
        Spacer(1, 1),
        Paragraph("Nombre: __________________________________________", cell_text),
        Spacer(1, 1),
        Paragraph("RUT / Cargo: ______________________________________", cell_text),
    ]
    pers_table = Table([[pers_col1, pers_col2]], colWidths=[9.75*cm, 9.75*cm])
    pers_table.setStyle(TableStyle([('VALIGN', (0,0), (-1,-1), 'TOP')]))
    story.extend(make_section("2. Personal Interviniente", pers_table, 5))

    # Section 3: Equipos Retirados
    ret_table_data = [
        [Paragraph("<b>N°</b>", cell_center), Paragraph("<b>Tipo / Marca / Modelo</b>", cell_bold), Paragraph("<b>N° Serie / Inventario</b>", cell_bold), Paragraph("<b>Estado</b>", cell_center), Paragraph("<b>Motivo / Obs.</b>", cell_bold)],
        [Paragraph("<b>01</b>", cell_center), Paragraph("PC Escritorio / ____________", cell_text), Paragraph("S/N: _______________________", cell_text), Paragraph("[  ] Operativo<br/>[  ] Falla", cell_center), Paragraph("Retiro por actualización", cell_text)],
        [Paragraph("<b>02</b>", cell_center), Paragraph("PC Escritorio / ____________", cell_text), Paragraph("S/N: _______________________", cell_text), Paragraph("[  ] Operativo<br/>[  ] Falla", cell_center), Paragraph("Retiro por actualización", cell_text)]
    ]
    ret_t = Table(ret_table_data, colWidths=[0.9*cm, 5.3*cm, 5.3*cm, 3.2*cm, 4.85*cm])
    ret_t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#EDF2F7')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#CBD5E0')),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 3),
        ('RIGHTPADDING', (0,0), (-1,-1), 3),
    ]))
    
    acc_ret_data = [
        [Paragraph("[<b>X</b>] Retiro 2 Monitores (Pantallas)", cell_text), Paragraph("[<b>X</b>] Retiro 2 Teclados y 2 Mouses", cell_text)],
        [Paragraph("[<b>X</b>] Retiro 2 Juegos Cable Poder/Video", cell_text), Paragraph("[<b>X</b>] Retiro Fuentes / Transformadores", cell_text)]
    ]
    acc_ret_t = Table(acc_ret_data, colWidths=[9.75*cm, 9.75*cm])
    acc_ret_t.setStyle(TableStyle([('TOPPADDING', (0,0), (-1,-1), 2), ('BOTTOMPADDING', (0,0), (-1,-1), 2)]))

    ret_content = [ret_t, Spacer(1, 3), acc_ret_t]
    story.extend(make_section("3. Detalle de Equipos Retirados (Salientes / Biblioteca)", ret_content, 5))

    # Section 4: Equipos Entregados
    ent_table_data = [
        [Paragraph("<b>N°</b>", cell_center), Paragraph("<b>Tipo / Marca / Modelo</b>", cell_bold), Paragraph("<b>N° Serie / Inventario</b>", cell_bold), Paragraph("<b>Especificaciones</b>", cell_bold), Paragraph("<b>Estado</b>", cell_bold)],
        [Paragraph("<b>01</b>", cell_center), Paragraph("PC Escritorio / ____________", cell_text), Paragraph("S/N: _______________________", cell_text), Paragraph("CPU / RAM / SSD: _________", cell_text), Paragraph("Nuevo / Instalado", cell_text)],
        [Paragraph("<b>02</b>", cell_center), Paragraph("PC Escritorio / ____________", cell_text), Paragraph("S/N: _______________________", cell_text), Paragraph("CPU / RAM / SSD: _________", cell_text), Paragraph("Nuevo / Instalado", cell_text)]
    ]
    ent_t = Table(ent_table_data, colWidths=[0.9*cm, 5.3*cm, 5.3*cm, 4.4*cm, 3.65*cm])
    ent_t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#EDF2F7')),
        ('GRID', (0,0), (-1,-1), 0.5, colors.HexColor('#CBD5E0')),
        ('VALIGN', (0,0), (-1,-1), 'MIDDLE'),
        ('TOPPADDING', (0,0), (-1,-1), 3),
        ('BOTTOMPADDING', (0,0), (-1,-1), 3),
        ('LEFTPADDING', (0,0), (-1,-1), 3),
        ('RIGHTPADDING', (0,0), (-1,-1), 3),
    ]))
    
    acc_ent_data = [
        [Paragraph("[<b>X</b>] Entrega 2 Monitores Nuevos", cell_text), Paragraph("[<b>X</b>] Entrega 2 Teclados y 2 Mouses Nuevos", cell_text)],
        [Paragraph("[<b>X</b>] Instalación Cables Poder y HDMI/DP", cell_text), Paragraph("[<b>X</b>] Conexión Red RJ45 / WiFi Configurado", cell_text)]
    ]
    acc_ent_t = Table(acc_ent_data, colWidths=[9.75*cm, 9.75*cm])
    acc_ent_t.setStyle(TableStyle([('TOPPADDING', (0,0), (-1,-1), 2), ('BOTTOMPADDING', (0,0), (-1,-1), 2)]))

    ent_content = [ent_t, Spacer(1, 3), acc_ent_t]
    story.extend(make_section("4. Detalle de Equipos Entregados e Instalados (Nuevos / Biblioteca)", ent_content, 5))

    # Section 5: Verification Checklist
    chk_data = [
        [Paragraph("[<b>X</b>] Encendido e inicio S.O. operativo", cell_text), Paragraph("[<b>X</b>] Conexión a Red e Internet comprobada", cell_text)],
        [Paragraph("[<b>X</b>] Software y navegadores instalados", cell_text), Paragraph("[<b>X</b>] Perfiles de acceso a Biblioteca listos", cell_text)]
    ]
    chk_t = Table(chk_data, colWidths=[9.75*cm, 9.75*cm])
    chk_t.setStyle(TableStyle([('TOPPADDING', (0,0), (-1,-1), 2), ('BOTTOMPADDING', (0,0), (-1,-1), 2)]))
    story.extend(make_section("5. Verificación y Pruebas de Funcionamiento", chk_t, 5))

    # Section 6: Observaciones
    obs_p = Paragraph("Cambio realizado conforme de 2 computadores de la Sala de Biblioteca. Los equipos salientes quedan bajo custodia del taller de soporte TI para su revisión y formateo.", cell_text)
    story.extend(make_section("6. Observaciones Adicionales", obs_p, 5))

    # Section 7: Signatures - Balanced spacer for full page fit
    sig1 = [
        Spacer(1, 35),
        HRFlowable(width="80%", thickness=1, color=colors.HexColor('#2D3748'), spaceAfter=3),
        Paragraph("<b>TÉCNICO SOPORTE TI</b>", cell_center),
        Paragraph("Entregado / Instalado Por", ParagraphStyle('SubSig', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#4A5568'))),
        Paragraph("RUT: __________________", ParagraphStyle('SubSigR', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#718096')))
    ]
    sig2 = [
        Spacer(1, 35),
        HRFlowable(width="80%", thickness=1, color=colors.HexColor('#2D3748'), spaceAfter=3),
        Paragraph("<b>ENCARGADO(A) BIBLIOTECA</b>", cell_center),
        Paragraph("Recibido a Conformidad", ParagraphStyle('SubSig', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#4A5568'))),
        Paragraph("RUT: __________________", ParagraphStyle('SubSigR', parent=cell_center, fontSize=7.5, leading=9, textColor=colors.HexColor('#718096')))
    ]
    sig3 = [
        Spacer(1, 35),
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
    print("PDF de biblioteca de pagina completa generado exitosamente.")

if __name__ == "__main__":
    crear_pdf()
