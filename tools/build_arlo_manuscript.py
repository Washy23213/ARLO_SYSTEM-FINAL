from __future__ import annotations

from pathlib import Path

from docx import Document
from docx.enum.section import WD_ORIENT, WD_SECTION
from docx.enum.table import WD_TABLE_ALIGNMENT, WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH, WD_BREAK
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Mm, Pt, RGBColor


ROOT = Path(r"c:\Users\washe\Downloads\arlo_system")
OUT = ROOT / "A Web (3) - Manuscript Guidelines.docx"

TITLE = "A Web-Based Tracer and Employment Analytics System for the Alumni Relations and Linkages Office"
SHORT_TITLE = "ARLO Tracer System"
INSTITUTION = "Central Mindanao University"
INSTITUTE = "Institute of Computer Applications"
AUTHORS = ["Washeley Tan", "Elvin Lar Gonzales"]
PROGRAM = "Bachelor of Science in Information Technology"
DATE_TEXT = "April 2026"

DFD_IMG = ROOT / "docs" / "arlo-dfd-context-hd.png"
DATA_MODEL_IMG = ROOT / "docs" / "arlo-data-model-hd.png"
ERD_IMG = ROOT / "docs" / "arlo-erd-professional-hd.png"
USE_CASE_IMG = ROOT / "build_doc_assets" / "arlo-use-case-clean.png"


def set_run_font(run, size=12, bold=False, italic=False):
    run.font.name = "Arial"
    run._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    run.font.size = Pt(size)
    run.font.bold = bold
    run.font.italic = italic
    run.font.color.rgb = RGBColor(0, 0, 0)


def add_field(paragraph, instr: str, placeholder: str = ""):
    run = paragraph.add_run()
    begin = OxmlElement("w:fldChar")
    begin.set(qn("w:fldCharType"), "begin")
    instr_text = OxmlElement("w:instrText")
    instr_text.set(qn("xml:space"), "preserve")
    instr_text.text = instr
    separate = OxmlElement("w:fldChar")
    separate.set(qn("w:fldCharType"), "separate")
    text = OxmlElement("w:t")
    text.text = placeholder
    end = OxmlElement("w:fldChar")
    end.set(qn("w:fldCharType"), "end")
    run._r.append(begin)
    run._r.append(instr_text)
    run._r.append(separate)
    run._r.append(text)
    run._r.append(end)
    set_run_font(run, 12)
    return run


def clear_paragraph(paragraph):
    paragraph._p.clear_content()


def configure_section(section, landscape=False, chapter_start=False):
    section.orientation = WD_ORIENT.LANDSCAPE if landscape else WD_ORIENT.PORTRAIT
    if landscape:
        section.page_width = Mm(297)
        section.page_height = Mm(210)
    else:
        section.page_width = Mm(210)
        section.page_height = Mm(297)
    section.top_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.right_margin = Inches(1)
    section.footer_distance = Inches(0.45)
    section.different_first_page_header_footer = chapter_start

    section.footer.is_linked_to_previous = False
    if section.footer.paragraphs:
        p = section.footer.paragraphs[0]
    else:
        p = section.footer.add_paragraph()
    clear_paragraph(p)
    p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(0)
    r = p.add_run(f"{SHORT_TITLE} ")
    set_run_font(r, 10)
    add_field(p, "PAGE", "1")
    for r in p.runs:
        set_run_font(r, 10)

    if chapter_start:
        section.first_page_footer.is_linked_to_previous = False
        fp = section.first_page_footer.paragraphs[0] if section.first_page_footer.paragraphs else section.first_page_footer.add_paragraph()
        clear_paragraph(fp)


def setup_styles(doc: Document):
    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Arial"
    normal._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    normal.font.size = Pt(12)
    normal.font.color.rgb = RGBColor(0, 0, 0)
    normal.paragraph_format.line_spacing = 1.5
    normal.paragraph_format.first_line_indent = Inches(0.5)
    normal.paragraph_format.space_after = Pt(12)
    normal.paragraph_format.space_before = Pt(0)

    for style_name in ["Heading 1", "Heading 2", "Heading 3", "Heading 4"]:
        style = styles[style_name]
        style.font.name = "Arial"
        style._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
        style.font.bold = True
        style.font.size = Pt(12)
        style.font.color.rgb = RGBColor(0, 0, 0)
        style.paragraph_format.first_line_indent = Inches(0)
        style.paragraph_format.line_spacing = 1.5
        style.paragraph_format.space_before = Pt(12)
        style.paragraph_format.space_after = Pt(12)

    styles["Heading 1"].font.size = Pt(14)
    styles["Heading 1"].paragraph_format.alignment = WD_ALIGN_PARAGRAPH.CENTER

    caption = styles["Caption"]
    caption.font.name = "Arial"
    caption._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    caption.font.size = Pt(12)
    caption.font.italic = False
    caption.font.color.rgb = RGBColor(0, 0, 0)
    caption.paragraph_format.line_spacing = 1.0
    caption.paragraph_format.first_line_indent = Inches(0)
    caption.paragraph_format.space_before = Pt(0)
    caption.paragraph_format.space_after = Pt(6)

    if "Approval Text" not in styles:
        approval = styles.add_style("Approval Text", 1)
    else:
        approval = styles["Approval Text"]
    approval.font.name = "Arial"
    approval._element.rPr.rFonts.set(qn("w:eastAsia"), "Arial")
    approval.font.size = Pt(11)
    approval.font.color.rgb = RGBColor(0, 0, 0)
    approval.paragraph_format.line_spacing = 1.5
    approval.paragraph_format.first_line_indent = Inches(0.5)
    approval.paragraph_format.space_after = Pt(12)


def add_centered(doc, text="", size=12, bold=False, space_after=12):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing = 1.5
    p.paragraph_format.space_after = Pt(space_after)
    r = p.add_run(text)
    set_run_font(r, size=size, bold=bold)
    return p


def add_body(doc, text, style="Normal", justify=True):
    p = doc.add_paragraph(style=style)
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY if justify else WD_ALIGN_PARAGRAPH.LEFT
    p.paragraph_format.first_line_indent = Inches(0.5)
    p.paragraph_format.line_spacing = 1.5
    p.paragraph_format.space_after = Pt(12)
    r = p.add_run(text)
    set_run_font(r, 12)
    return p


def add_noindent(doc, text="", style="Normal", bold=False, center=False, size=12):
    p = doc.add_paragraph(style=style)
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing = 1.5
    p.paragraph_format.space_after = Pt(12)
    if center:
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    r = p.add_run(text)
    set_run_font(r, size=size, bold=bold)
    return p


def add_chapter(doc, number, title):
    sec = doc.add_section(WD_SECTION.NEW_PAGE)
    configure_section(sec, chapter_start=True)
    p = doc.add_paragraph(style="Heading 1")
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.first_line_indent = Inches(0)
    r = p.add_run(f"CHAPTER {number}\n{title.upper()}")
    set_run_font(r, 14, bold=True)
    return sec


def add_section_heading(doc, text, level=2):
    p = doc.add_paragraph(style=f"Heading {level}")
    p.paragraph_format.first_line_indent = Inches(0)
    r = p.add_run(text)
    set_run_font(r, 12, bold=True)
    return p


def add_list_item(doc, text, numbered=False):
    p = doc.add_paragraph(style="List Number" if numbered else "List Bullet")
    p.paragraph_format.line_spacing = 1.5
    p.paragraph_format.space_after = Pt(6)
    r = p.add_run(text)
    set_run_font(r, 12)
    return p


def add_caption(doc, label, text, align=WD_ALIGN_PARAGRAPH.CENTER):
    p = doc.add_paragraph(style="Caption")
    p.alignment = align
    p.paragraph_format.first_line_indent = Inches(0)
    r = p.add_run(f"{label} ")
    set_run_font(r, 12, bold=False)
    add_field(p, f"SEQ {label} \\* ARABIC", "1")
    r = p.add_run(f". {text}")
    set_run_font(r, 12)
    return p


def set_cell_text(cell, text, bold=False):
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
    cell.text = ""
    p = cell.paragraphs[0]
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing = 1.0
    p.paragraph_format.space_after = Pt(0)
    r = p.add_run(text)
    set_run_font(r, 11, bold=bold)


def shade_cell(cell, fill="EDEDED"):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = OxmlElement("w:shd")
    shd.set(qn("w:fill"), fill)
    tc_pr.append(shd)


def set_cell_width(cell, width_in):
    cell.width = Inches(width_in)
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_w = tc_pr.first_child_found_in("w:tcW")
    if tc_w is None:
        tc_w = OxmlElement("w:tcW")
        tc_pr.append(tc_w)
    tc_w.set(qn("w:w"), str(int(width_in * 1440)))
    tc_w.set(qn("w:type"), "dxa")


def add_table(doc, headers, rows, widths):
    table = doc.add_table(rows=1, cols=len(headers))
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    table.style = "Table Grid"
    hdr = table.rows[0].cells
    for i, h in enumerate(headers):
        set_cell_text(hdr[i], h, bold=True)
        shade_cell(hdr[i])
        set_cell_width(hdr[i], widths[i])
    for row_data in rows:
        row = table.add_row().cells
        for i, val in enumerate(row_data):
            set_cell_text(row[i], val)
            set_cell_width(row[i], widths[i])
    doc.add_paragraph()
    return table


def add_picture(doc, path: Path, width_in):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.space_after = Pt(6)
    r = p.add_run()
    r.add_picture(str(path), width=Inches(width_in))
    return p


def add_blank_lines(doc, count=3):
    for _ in range(count):
        p = doc.add_paragraph()
        p.paragraph_format.line_spacing = 1.5
        p.paragraph_format.space_after = Pt(12)


def add_toc_field_page(doc, title, field_instr):
    add_noindent(doc, title.upper(), bold=True, center=True, size=12)
    p = doc.add_paragraph()
    p.paragraph_format.first_line_indent = Inches(0)
    p.paragraph_format.line_spacing = 1.5
    add_field(p, field_instr, "Right-click and update field if page numbers do not appear.")


def build():
    doc = Document()
    setup_styles(doc)
    configure_section(doc.sections[0], chapter_start=False)

    # Cover page
    add_centered(doc, TITLE.upper(), size=14, bold=True, space_after=36)
    add_centered(doc, "A Capstone Project Manuscript", size=12, bold=False, space_after=24)
    add_centered(doc, f"Presented to the {INSTITUTE}", size=12)
    add_centered(doc, INSTITUTION, size=12, space_after=36)
    add_centered(doc, "By", size=12)
    for author in AUTHORS:
        add_centered(doc, author.upper(), size=12, bold=True, space_after=6)
    add_centered(doc, PROGRAM, size=12, space_after=36)
    add_centered(doc, DATE_TEXT.upper(), size=12, bold=True)

    doc.add_page_break()

    # Approval sheet
    add_noindent(doc, "SIGNED APPROVAL SHEET", style="Approval Text", bold=True, center=True, size=11)
    approval_text = (
        f"This capstone project manuscript entitled \"{TITLE}\" has been prepared and submitted by "
        f"{AUTHORS[0]} and {AUTHORS[1]} in partial fulfillment of the requirements for the degree "
        f"{PROGRAM}. This page is reserved for the signatures of the adviser, panel members, and approving officials."
    )
    p = doc.add_paragraph(style="Approval Text")
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    r = p.add_run(approval_text)
    set_run_font(r, 11)
    add_blank_lines(doc, 3)
    for label in ["Adviser", "Panel Member", "Panel Member", "Panel Member", "Institute Director"]:
        p = doc.add_paragraph(style="Approval Text")
        p.paragraph_format.first_line_indent = Inches(0)
        p.add_run("_______________________________").font.name = "Arial"
        p.add_run(f"\n{label}").font.name = "Arial"

    doc.add_page_break()

    # Biographical sketch
    add_noindent(doc, "AUTHOR'S BIOGRAPHICAL SKETCH", bold=True, center=True)
    add_body(
        doc,
        f"{AUTHORS[0]} and {AUTHORS[1]} are Bachelor of Science in Information Technology students of "
        f"{INSTITUTION}. Their capstone work focuses on the development and documentation of a web-based tracer "
        "and employment analytics system intended to support alumni record management, graduate tracking, job linkage, "
        "communication, and administrative reporting for the Alumni Relations and Linkages Office. Through this project, "
        "the authors applied systems analysis, database design, web application development, software testing, and technical "
        "documentation practices required in information technology project work.",
    )

    doc.add_page_break()

    # Acknowledgment
    add_noindent(doc, "ACKNOWLEDGMENT", bold=True, center=True)
    add_body(
        doc,
        "The researchers express their sincere gratitude to Central Mindanao University, the Institute of Computer "
        "Applications, the project adviser, panel members, instructors, classmates, family members, and the Alumni "
        "Relations and Linkages Office for the guidance, support, and encouragement given throughout the development "
        "of this capstone project. Their assistance helped the researchers understand the importance of accurate alumni "
        "records, responsible data handling, and useful employment analytics for institutional planning and graduate engagement.",
    )
    add_blank_lines(doc, 2)
    for author in AUTHORS:
        p = doc.add_paragraph()
        p.paragraph_format.left_indent = Inches(0.5)
        p.paragraph_format.first_line_indent = Inches(0)
        p.paragraph_format.line_spacing = 1.5
        r = p.add_run("_______________________________\n" + author.upper())
        set_run_font(r, 12)

    doc.add_page_break()

    # Executive summary
    add_noindent(doc, "EXECUTIVE SUMMARY", bold=True, center=True)
    add_body(
        doc,
        f"The study entitled \"{TITLE}\" presents the design and documentation of a centralized web application for "
        "alumni profile management, tracer record submission, job posting, job application handling, announcements, feedback, "
        "notifications, audit logging, and employment analytics. The system responds to the need for organized alumni records "
        "and timely reports that can assist the Alumni Relations and Linkages Office in monitoring graduate outcomes and maintaining communication with alumni.",
    )
    add_body(
        doc,
        "The project uses Laravel 12, PHP 8.2, PostgreSQL, Blade templates, Tailwind CSS, Vite, Chart.js, and supporting notification services. "
        "Its design follows a relational database model centered on users, tracer records, jobs, applications, job submissions, announcements, "
        "feedback, notifications, sessions, and audit logs. The project documentation includes a Data Flow Diagram Level 0, Entity Relationship Diagram, "
        "Use Case Diagram, testing summary, and recommendations for further improvement.",
    )
    add_body(
        doc,
        "The results of the documentation review show that the proposed system is capable of supporting the major requirements of an alumni tracer and employment analytics platform. "
        "The recommendations focus on completing deployment planning, strengthening data privacy controls, improving reporting features, adding more user evaluation, and maintaining the system through regular testing and documentation updates.",
    )

    doc.add_page_break()

    # Copyright page
    add_noindent(doc, "COPYRIGHT PAGE", bold=True, center=True)
    add_body(
        doc,
        f"Copyright {DATE_TEXT[-4:]} by {AUTHORS[0]} and {AUTHORS[1]}. All rights reserved. No part of this manuscript may be reproduced, stored, or transmitted in any form without proper acknowledgment of the authors and the institution, except for academic review, evaluation, and archival purposes allowed by the Institute of Computer Applications.",
    )

    doc.add_page_break()

    # TOC / lists
    add_toc_field_page(doc, "Table of Contents", 'TOC \\o "1-3" \\h \\z \\u')
    doc.add_page_break()
    add_toc_field_page(doc, "List of Tables", 'TOC \\h \\z \\c "Table"')
    doc.add_page_break()
    add_toc_field_page(doc, "List of Figures", 'TOC \\h \\z \\c "Figure"')
    doc.add_page_break()
    add_noindent(doc, "LIST OF APPENDICES", bold=True, center=True)
    add_noindent(doc, "Appendix A. Project Repository and Supporting Materials")

    # CHAPTER I
    add_chapter(doc, "I", "Introduction")
    add_section_heading(doc, "1.1 Background of the Study")
    add_body(
        doc,
        "Alumni information is an important institutional resource because it provides evidence of graduate employability, supports curriculum improvement, and strengthens long-term engagement between a university and its graduates. When alumni information is collected manually or stored in separate files, the office responsible for alumni relations may experience delays in updating records, preparing reports, and communicating opportunities to graduates.",
    )
    add_body(
        doc,
        f"The proposed {TITLE} addresses these concerns by providing a centralized platform where alumni can register, maintain their profiles, submit tracer information, view announcements, browse employment opportunities, apply for jobs, and send feedback. Administrators can manage records, job postings, announcements, job submissions, feedback, audit logs, and analytics from a controlled dashboard.",
    )
    add_body(
        doc,
        "Graduate tracer data is especially useful because it helps institutions understand employment status, industry placement, job satisfaction, salary range, and the relevance of academic preparation to actual work. The system therefore combines record management with analytics so that collected data can be transformed into reports that support planning and decision-making.",
    )

    add_section_heading(doc, "1.2 Statement of the Problem")
    add_body(
        doc,
        "This study is guided by the general problem of how to design and document a web-based tracer and employment analytics system that improves the efficiency, accuracy, and usefulness of alumni records management for the Alumni Relations and Linkages Office.",
    )
    add_noindent(doc, "Specifically, the study seeks to answer the following questions:")
    questions = [
        "How can alumni registration, profile maintenance, and tracer data submission be centralized in a single web-based platform?",
        "How can the system support job posting, job application, feedback submission, announcements, and alumni engagement activities?",
        "How can administrators manage records, submissions, announcements, feedback, audit logs, and reports through role-based access?",
        "How can the system generate useful employment and tracer analytics for planning and decision-making?",
        "How can the database and process design maintain data consistency, privacy, traceability, and accountability?",
    ]
    for q in questions:
        add_list_item(doc, q, numbered=True)

    add_section_heading(doc, "1.3 Objectives of the Study")
    add_body(doc, "The general objective of the study is to develop and document a web-based tracer and employment analytics system for the Alumni Relations and Linkages Office.")
    add_noindent(doc, "Specifically, the study aims to:")
    objectives = [
        "Provide a secure registration, authentication, and account recovery mechanism for alumni and administrators.",
        "Allow alumni to maintain personal, academic, employment, scholarship, and tracer-related information.",
        "Provide modules for jobs, job submissions, job applications, announcements, notifications, and feedback.",
        "Enable administrators to manage alumni records, submissions, announcements, jobs, feedback, audit logs, and reports.",
        "Produce analytical outputs that summarize tracer and employment information for institutional use.",
        "Design a database structure and process flow that support consistent and auditable information management.",
    ]
    for o in objectives:
        add_list_item(doc, o)

    add_section_heading(doc, "1.4 Scope and Limitations of the Study")
    add_body(
        doc,
        "The scope of the study covers the design and documentation of the CMU ARLO platform for two major user roles: alumni and administrator. Its functional coverage includes authentication, profile management, tracer submissions, job postings, job applications, alumni-submitted job opportunities, announcements, notifications, feedback management, audit logs, and analytics dashboards.",
    )
    add_body(
        doc,
        "The study is limited to a web-based implementation and the database structures reflected in the current project repository. It does not include full enterprise integration with payroll systems, third-party human resource platforms, or automated government reporting systems. Final production deployment details and maintenance procedures are intentionally left blank in this manuscript version, as requested.",
    )

    add_section_heading(doc, "1.5 Significance of the Study")
    add_body(
        doc,
        "The study is significant to the Alumni Relations and Linkages Office because it provides a more organized way to collect, update, monitor, and analyze alumni information. For university administrators, the system can support evidence-based decisions about graduate employability and alumni engagement. For alumni, it provides a convenient channel for maintaining records, receiving announcements, submitting tracer information, and accessing job opportunities. For future researchers, the project may serve as a reference for similar systems that combine alumni tracing, employment analytics, and web-based information management.",
    )

    # CHAPTER II
    add_chapter(doc, "II", "Review of Related Literature/Systems")
    add_section_heading(doc, "2.1 Graduate Tracer Studies and Alumni Information")
    add_body(
        doc,
        "Graduate tracer studies are used to determine the employment status, career movement, competencies, and educational experiences of graduates after leaving an institution. The Commission on Higher Education has used graduate tracer study instruments to collect information about employability and the relevance of higher education programs. These practices support the need for structured data collection and careful reporting in alumni-related systems.",
    )
    add_body(
        doc,
        "Nabablit and Dajao (2023) presented a web-based graduate tracer information system with data analytics, showing that tracer data can be managed through an online platform and used to generate reports for decision-making. This related work supports the direction of the proposed system because the CMU ARLO project also combines alumni information management with employment analytics.",
    )

    add_section_heading(doc, "2.2 Web-Based Alumni Systems")
    add_body(
        doc,
        "Web-based alumni systems commonly include registration, profile management, announcements, job opportunities, and communication features. These modules reduce dependence on manual files and make it easier for alumni to update their own records. In the proposed system, these ideas are reflected in the alumni dashboard, tracer workflow, job board, job submission workflow, announcements, notifications, and feedback module.",
    )

    add_section_heading(doc, "2.3 Security, Privacy, and Data Protection")
    add_body(
        doc,
        "Because alumni systems process personal, academic, and employment information, privacy and security are important design concerns. The Philippine Data Privacy Act of 2012 emphasizes lawful, fair, and purpose-based handling of personal information. The OWASP Application Security Verification Standard also provides guidance for verifying web application security controls. These references support the use of authentication, role-based access, audit logs, validation, account controls, and careful handling of sensitive records in the proposed system.",
    )

    add_section_heading(doc, "2.4 Technology Support for Analytics")
    add_body(
        doc,
        "The project uses Laravel for server-side web application structure, PostgreSQL for relational data storage, and Chart.js for visual analytics. This combination supports controlled data processing, structured queries, and dashboard-based visualization of tracer and employment information. The literature and technical references show that a web-based platform can improve accessibility while analytics can turn collected records into more useful institutional outputs.",
    )

    add_caption(doc, "Table", "Summary of Reviewed Literature, Systems, and Technical References", align=WD_ALIGN_PARAGRAPH.LEFT)
    table_rows = [
        ("CHED Graduate Tracer Study", "Graduate profile and employability data collection", "Supports the tracer information structure of the proposed system"),
        ("Nabablit and Dajao (2023)", "Web-based graduate tracer information system with analytics", "Supports the combination of alumni records and reporting features"),
        ("Data Privacy Act of 2012", "Lawful and purpose-based handling of personal information", "Guides privacy, consent, and controlled access considerations"),
        ("OWASP ASVS", "Security verification requirements for web applications", "Supports authentication, validation, and security testing concerns"),
        ("Laravel, PostgreSQL, and Chart.js documentation", "Framework, database, and charting references", "Guides the technical background and implementation tools"),
    ]
    add_table(doc, ["Reference/System", "Key Feature", "Contribution to the Proposed System"], table_rows, [1.9, 2.1, 2.3])

    add_section_heading(doc, "2.5 Summary of Review")
    add_body(
        doc,
        "The reviewed literature and technical references establish the importance of centralized alumni records, tracer data collection, privacy-aware processing, secure access, and dashboard-based reporting. These findings support the proposed system's design as a web-based tool for alumni management and employment analytics.",
    )

    # CHAPTER III
    add_chapter(doc, "III", "Technical Background")
    add_body(
        doc,
        "This chapter describes the main technologies and technical concepts used in the proposed system. The project is implemented as a Laravel-based web application that uses a relational database, server-side validation, Blade views, front-end styling tools, and charting libraries for analytics presentation.",
    )
    add_section_heading(doc, "3.1 Laravel and PHP")
    add_body(
        doc,
        "Laravel 12 serves as the main application framework, while PHP 8.2 provides the server-side programming language. Laravel organizes routing, controllers, form requests, middleware, authentication, notifications, services, models, database migrations, and tests. These framework features support maintainable development and consistent handling of alumni and administrator workflows.",
    )
    add_section_heading(doc, "3.2 PostgreSQL Database")
    add_body(
        doc,
        "PostgreSQL is used as the relational database management system. It stores users, tracer records, jobs, applications, job submissions, announcements, announcement reads, feedback, notifications, sessions, and system logs. The database design uses primary keys, foreign keys, status fields, timestamps, and indexes to support data integrity and reporting performance.",
    )
    add_section_heading(doc, "3.3 Blade Templates, Tailwind CSS, and Vite")
    add_body(
        doc,
        "Blade templates provide the server-rendered views of the application. Tailwind CSS supports user interface styling, while Vite handles asset bundling and development builds. These tools help create separate but consistent interfaces for alumni and administrator users.",
    )
    add_section_heading(doc, "3.4 Chart.js Analytics")
    add_body(
        doc,
        "Chart.js is used to display employment trends, graduation distributions, tracer summaries, and dashboard analytics. By converting database records into visual summaries, the system helps administrators interpret collected alumni data more quickly.",
    )
    add_section_heading(doc, "3.5 Notification and Integration Support")
    add_body(
        doc,
        "The system includes notification support for announcements, job submission review outcomes, and user-facing updates. The repository also contains services for Facebook page publishing and synchronization, which may be used to support communication through the official Alumni Relations and Linkages Office page when properly configured.",
    )

    # CHAPTER IV
    add_chapter(doc, "IV", "Methodology")
    add_section_heading(doc, "4.1 Software Development Methodology")
    add_body(
        doc,
        "This study follows the Waterfall model as the software development methodology. The model was selected because the capstone project requires a clear sequence of requirements analysis, documentation, system design, development, testing, and final reporting. Its structured nature supports the preparation of formal diagrams, database design, and manuscript documentation.",
    )
    add_section_heading(doc, "4.2 Requirements Analysis")
    add_body(
        doc,
        "The requirements analysis phase identified the main needs of the Alumni Relations and Linkages Office. These include alumni account registration, alumni profile management, tracer data submission, job opportunity posting, job applications, announcements, notifications, feedback, audit logs, and analytics. The requirements were grouped according to the two main user roles: alumni and administrator.",
    )
    add_section_heading(doc, "4.3 Requirements Documentation")
    add_body(
        doc,
        "The requirements were documented through narrative descriptions, use case definitions, data flow representation, entity relationship modeling, and functional module descriptions. The use case definitions identify what each actor can do, while the data and process diagrams show how information moves through the system.",
    )
    add_section_heading(doc, "4.4 Design of Software, System, Products, and Processes")
    add_body(
        doc,
        "The system design phase focused on process flow, user roles, database structure, interface modules, validation rules, and administrative control. The latest design uses three main actors: Alumni, Administrator, and Super Admin. These actors are separated at the account level: alumni accounts are stored in the users table, regular administrator accounts are stored in the admins table, and Super Admin accounts are stored in the super_admins table. Alumni use the system to maintain profiles, submit tracer information, apply for jobs, view announcements, receive notifications, and submit feedback. Administrators manage operational records such as alumni records, tracer records, job postings, announcements, feedback, reports, and audit logs. The Super Admin is responsible for account governance, including creating, viewing, updating, and deleting administrator accounts, assigning administrator access roles, and editing alumni or administrator account details when correction or account control is required.",
    )
    add_body(
        doc,
        "The design documents are connected to each other. The Data Flow Diagram shows how information moves between the actors and the system. The conceptual data model identifies the main data objects involved in those processes. The Entity Relationship Diagram then translates those objects into database tables, keys, and relationships. This sequence was used so that the process design first explains the system activities, the data model then identifies the important data objects, and the ERD finally shows how those objects are stored in the Laravel and PostgreSQL database structure.",
    )

    add_section_heading(doc, "4.4.1 Data Flow Diagram Level 0", level=3)
    add_body(
        doc,
        "The Data Flow Diagram Level 0, also called the context diagram, presents the system as one major process interacting with Alumni, Administrator, and Super Admin. This level does not show the internal database tables; instead, it focuses on the major input and output flows between the actors and the proposed system.",
    )
    add_picture(doc, DFD_IMG, 6.2)
    add_caption(doc, "Figure", "Data Flow Diagram Level 0 of the Proposed Web-Based Tracer and Employment Analytics System")

    add_body(
        doc,
        "In the Level 0 diagram, alumni provide login credentials, profile updates, tracer data, job applications, job submissions, and feedback. The system returns dashboard content, announcements, notifications, job information, application updates, and profile data. Administrators provide record management requests, job posting actions, announcement publishing actions, feedback updates, and analytics requests. The system returns managed records, dashboards, reports, and audit logs. The Super Admin provides account management requests, such as creating administrator accounts, updating administrator account information, deleting administrator accounts, assigning administrator access, and editing alumni account details when needed. In return, the system provides admin account lists, alumni account details, role status, account update confirmations, and activity logs.",
    )

    add_section_heading(doc, "4.4.2 Conceptual Data Model", level=3)
    add_body(
        doc,
        "The conceptual data model presents the high-level data objects of the system before they are translated into detailed database tables. The main objects are Alumni, Super Admin, Admin, Tracer Record, Job Submission, Job Application, Job Posting, Feedback, and Announcement. The Alumni object represents graduate users who maintain personal, academic, and employment information. The Admin object represents regular office personnel who manage records and system content. The Super Admin object represents the separate account-control authority responsible for creating administrator accounts, updating administrator account details, deleting administrator accounts, assigning access roles, and editing alumni account information when account correction is required.",
    )
    add_picture(doc, DATA_MODEL_IMG, 6.2)
    add_caption(doc, "Figure", "Conceptual Data Model of the Proposed Web-Based Tracer and Employment Analytics System")
    add_body(
        doc,
        "The model shows that Alumni are connected to Tracer Records because alumni submit employment and graduate outcome information. Alumni are also connected to Job Submissions, Job Applications, and Feedback because they can propose job opportunities, apply for posted jobs, and send concerns or suggestions to the office. Admins are connected to Job Submissions, Job Applications, Job Postings, Announcements, and Feedback because they review, publish, manage, and respond to these records. The Super Admin is connected to Admin through administrator account management because the Super Admin controls the creation, updating, deletion, and role assignment of administrator accounts. The Super Admin is also connected to Alumni through account editing because it can correct or update alumni account details when necessary.",
    )

    add_section_heading(doc, "4.4.3 Entity Relationship Diagram", level=3)
    add_body(
        doc,
        "The Entity Relationship Diagram illustrates the detailed database structure used by the system. The latest design separates the three account groups into different tables. Alumni are stored in the users table, regular administrators are stored in the admins table, and Super Admin accounts are stored in the super_admins table. This separation makes the responsibility of each account type clearer: alumni submit and maintain tracer-related information, administrators manage operational records, and Super Admin accounts control administrator account access.",
    )

    # Landscape page for ERD
    erd_section = doc.add_section(WD_SECTION.NEW_PAGE)
    configure_section(erd_section, landscape=True, chapter_start=False)
    add_picture(doc, ERD_IMG, 9.4)
    add_caption(doc, "Figure", "Entity Relationship Diagram of the Proposed Web-Based Tracer and Employment Analytics System")

    portrait_after_erd = doc.add_section(WD_SECTION.NEW_PAGE)
    configure_section(portrait_after_erd, chapter_start=False)
    add_body(
        doc,
        "The ERD shows that one user can have many tracer records, job applications, job submissions, feedback records, notifications, sessions, and user-related audit logs. The admins table connects to job submissions, job applications, feedback, jobs, announcements, and system logs because administrators review submitted records, manage content, and perform monitored administrative actions. The super_admins table is separate from admins and connects to administrator account management because Super Admin accounts can create, update, and delete regular administrator accounts. The super_admins table also connects to alumni account editing and system logs so that account-level changes can be traced for accountability.",
    )

    add_section_heading(doc, "4.4.4 Use Case Diagram", level=3)
    add_body(
        doc,
        "The Use Case Diagram summarizes the functional requirements of the system by showing the interactions between Alumni, Administrator, Super Admin, and the major system functions. Alumni use account, profile, tracer, job, announcement, notification, and feedback features. Administrators use dashboard, record management, job management, announcement, feedback, analytics, and audit log features. The Super Admin has separate account-control use cases for creating admin accounts, assigning roles, editing roles, deleting admin accounts, and editing alumni or administrator account details.",
    )
    add_picture(doc, USE_CASE_IMG, 6.2)
    add_caption(doc, "Figure", "Use Case Diagram of the Proposed Web-Based Tracer and Employment Analytics System")
    add_body(
        doc,
        "The Use Case Diagram was checked and found aligned with the implemented routes and controllers. The only correction note is that each actor-to-use-case association should appear once in the final source diagram; the Alumni association to Submit Feedback should not be duplicated in the diagram source.",
    )

    add_section_heading(doc, "4.5 Development and Testing")
    add_body(
        doc,
        "The development phase translated the documented requirements and design into Laravel controllers, models, form requests, services, database migrations, Blade views, and supporting JavaScript. Testing focused on authentication, alumni workflows, administrator CRUD operations, audit logs, feedback, job applications, announcement visibility, and account deletion cascade behavior.",
    )
    add_caption(doc, "Table", "Testing Summary of the Proposed System", align=WD_ALIGN_PARAGRAPH.LEFT)
    testing_rows = [
        ("Feature Testing", "Authentication, admin CRUD, feedback, jobs, announcements, account deletion", "Verify that critical workflows behave correctly"),
        ("Exploratory Testing", "Manual checking of edge cases and unusual user actions", "Identify workflow inconsistencies and validation gaps"),
        ("User Acceptance Testing", "End-to-end alumni and administrator workflows", "Confirm that the system supports office requirements"),
        ("Performance and Data Testing", "Simulated records and concurrent user behavior", "Check responsiveness of reports, dashboards, and database queries"),
    ]
    add_table(doc, ["Testing Phase", "Scope/Parameters", "Key Objective"], testing_rows, [1.7, 2.5, 2.1])

    add_section_heading(doc, "4.6 Description of the Prototype")
    add_body(
        doc,
        "The prototype includes separate interfaces for alumni and administrators. Alumni can register, log in, update profiles, submit tracer records, browse jobs, apply for jobs, submit job opportunities, read announcements, view notifications, and submit feedback. Administrators can manage alumni records, create announcements, manage jobs, review job submissions, monitor feedback, view analytics, and review audit logs.",
    )

    add_section_heading(doc, "4.7 Implementation Plan (Infrastructure and Deployment)")
    add_blank_lines(doc, 5)

    add_section_heading(doc, "4.8 Implementing Rules")
    add_body(
        doc,
        "The system should be used according to role-based access. Alumni accounts should be used only by verified alumni, while administrator accounts should be limited to authorized office staff. Personal and employment data should be collected only for legitimate tracer, reporting, and alumni engagement purposes. Administrators should review submitted job opportunities before publishing them, monitor feedback responsibly, and maintain audit logs for accountability.",
    )

    add_section_heading(doc, "4.9 Maintenance")
    add_blank_lines(doc, 5)

    # CHAPTER V
    add_chapter(doc, "V", "Results, Discussion, and Conclusion")
    add_section_heading(doc, "5.1 Results")
    add_body(
        doc,
        "The documented system provides a centralized structure for alumni records, tracer submissions, jobs, announcements, feedback, notifications, and analytics. The database design connects the major entities needed to track users, employment records, job activity, administrative actions, and communication records. The diagrams support the explanation of how users interact with the system and how data flows through the application.",
    )
    add_body(
        doc,
        "The review of the repository indicates that the project includes implemented modules for alumni and administrator workflows, as well as feature tests for important system behavior. These results show that the project is not limited to design documentation but is supported by working application components and verification activities.",
    )
    add_section_heading(doc, "5.2 Discussion")
    add_body(
        doc,
        "The system addresses the identified problem by reducing scattered record handling and providing a single platform for alumni-related data. The administrator dashboard and analytics features help transform collected records into summaries that can support decision-making. The inclusion of audit logs, validation, account controls, and privacy-related fields also strengthens accountability and responsible handling of alumni data.",
    )
    add_body(
        doc,
        "The diagram review showed that the DFD Level 0 and Use Case Diagram are aligned with the main project scope. The ERD is conceptually correct for the central data model, but the final physical ERD should be updated whenever new support tables or fields are added to the database migrations.",
    )
    add_section_heading(doc, "5.3 Conclusion")
    add_body(
        doc,
        "Based on the documentation and repository review, the proposed system is suitable as a web-based platform for alumni tracing and employment analytics. It supports the major workflows needed by alumni and administrators, provides a structured relational database, and includes reporting features that can help the Alumni Relations and Linkages Office monitor graduate outcomes. Continued refinement is recommended before final production deployment, especially in the areas of privacy compliance, maintenance planning, user evaluation, and deployment documentation.",
    )

    # CHAPTER VI
    add_chapter(doc, "VI", "Recommendation")
    add_body(
        doc,
        "The researchers recommend that the system be completed with a formal deployment and maintenance plan before final production use. This plan should identify the hosting environment, domain configuration, database backup schedule, environment variables, queue worker setup, mail configuration, storage settings, monitoring process, and rollback procedure. Since the deployment and maintenance sections are intentionally left blank in this manuscript version, they should be completed after the team finalizes the target infrastructure.",
    )
    add_body(
        doc,
        "It is also recommended that the team update the final ERD to include all current physical database details, especially announcement read tracking and extended user and tracer fields. Keeping the ERD synchronized with migrations will make the documentation more accurate and easier to defend during evaluation.",
    )
    add_body(
        doc,
        "For security and privacy, the system should undergo a focused review based on the Data Privacy Act of 2012 and practical web application security standards. The review should cover data collection notices, consent fields, account verification, password recovery, role-based access, file upload handling, audit logs, session behavior, and database backup protection.",
    )
    add_body(
        doc,
        "For usability, the researchers recommend conducting additional user acceptance testing with actual Alumni Relations and Linkages Office staff and selected alumni representatives. Their feedback should be used to refine form labels, dashboard summaries, tracer questions, filters, reports, and notification messages.",
    )
    add_body(
        doc,
        "For future enhancement, the system may include exportable reports, mobile-responsive improvements, alumni verification workflows, stronger notification preferences, advanced filtering for analytics, automatic report generation, and long-term archival tools for inactive records. These enhancements can help the platform remain useful as the alumni database grows.",
    )

    # CHAPTER VII
    add_chapter(doc, "VII", "References")
    refs = [
        "Chart.js. (2025). Chart.js documentation. https://www.chartjs.org/docs/latest/",
        "Commission on Higher Education. (2017). CHED Graduate Tracer Study. https://ched.gov.ph/wp-content/uploads/2017/11/CHED-00000016.pdf",
        "Laravel. (2026). Laravel 12.x documentation. https://laravel.com/docs/12.x",
        "Meta. (2026). Graph API documentation. https://developers.facebook.com/docs/graph-api/",
        "Nabablit, K. J. E., & Dajao, E. S. (2023). Development of a Web-Based Graduate Tracer Information System with Data Analytics. In Proceedings of the Eighth International Congress on Information and Communication Technology (pp. 601-611). Springer. https://doi.org/10.1007/978-981-99-3091-3_50",
        "National Privacy Commission. (2012). Republic Act No. 10173: Data Privacy Act of 2012. https://privacy.gov.ph/data-privacy-act/",
        "OWASP Foundation. (2025). Application Security Verification Standard. https://owasp.org/www-project-application-security-verification-standard/",
        "PostgreSQL Global Development Group. (2026). PostgreSQL documentation. https://www.postgresql.org/docs/",
        "Royce, W. W. (1970). Managing the Development of Large Software Systems. Proceedings of IEEE WESCON, 1-9. https://www.praxisframework.org/files/royce1970.pdf",
    ]
    for ref in refs:
        p = doc.add_paragraph()
        p.paragraph_format.first_line_indent = Inches(-0.5)
        p.paragraph_format.left_indent = Inches(0.5)
        p.paragraph_format.line_spacing = 1.5
        p.paragraph_format.space_after = Pt(12)
        r = p.add_run(ref)
        set_run_font(r, 12)

    # Appendices
    sec = doc.add_section(WD_SECTION.NEW_PAGE)
    configure_section(sec, chapter_start=False)
    add_noindent(doc, "APPENDICES", bold=True, center=True)
    add_section_heading(doc, "Appendix A. Project Repository and Supporting Materials")
    add_body(
        doc,
        "Supplementary materials may include system screenshots, test cases, database migration files, selected source code listings, user acceptance testing forms, and final deployment notes once the deployment and maintenance plan has been completed.",
    )

    doc.save(OUT)


if __name__ == "__main__":
    build()
    print(OUT)
