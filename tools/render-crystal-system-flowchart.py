from __future__ import annotations

import math
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "figure-4-system-flowchart-crystal-clear.png"

W, H = 4800, 3400
BG = "#ffffff"
GREEN = "#006000"
DARK_GREEN = "#003300"
SOFT_GREEN = "#eef7ec"
PALE_GREEN = "#f8fff4"
GOLD = "#ffc000"
SOFT_GOLD = "#fff4c2"
TEXT = "#102414"
MUTED = "#35543b"
LINE = "#35543b"
LIGHT_LINE = "#b8cbb8"


def font(name: str, size: int) -> ImageFont.FreeTypeFont:
    candidates = [
        Path("C:/Windows/Fonts") / name,
        Path("/usr/share/fonts/truetype/dejavu") / name,
    ]
    for path in candidates:
        if path.exists():
            return ImageFont.truetype(str(path), size)
    return ImageFont.load_default()


FONT_TITLE = font("arialbd.ttf", 54)
FONT_SUB = font("arial.ttf", 28)
FONT_BODY = font("arial.ttf", 31)
FONT_BODY_BOLD = font("arialbd.ttf", 32)
FONT_SMALL = font("arial.ttf", 25)
FONT_SMALL_BOLD = font("arialbd.ttf", 25)
FONT_PANEL_TITLE = font("arialbd.ttf", 34)
FONT_PANEL_BODY = font("arial.ttf", 28)


img = Image.new("RGB", (W, H), BG)
draw = ImageDraw.Draw(img)


def bbox_for_center(cx: int, cy: int, w: int, h: int) -> tuple[int, int, int, int]:
    return (cx - w // 2, cy - h // 2, cx + w // 2, cy + h // 2)


def wrapped_lines(text: str, fnt: ImageFont.ImageFont, max_width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current: list[str] = []
    for word in words:
        candidate = " ".join([*current, word]).strip()
        if draw.textbbox((0, 0), candidate, font=fnt)[2] <= max_width:
            current.append(word)
            continue
        if current:
            lines.append(" ".join(current))
        current = [word]
    if current:
        lines.append(" ".join(current))
    return lines


def center_text(
    box: tuple[int, int, int, int],
    text: str,
    fnt: ImageFont.ImageFont = FONT_BODY,
    fill: str = TEXT,
    max_width_pad: int = 36,
    line_gap: int = 8,
) -> None:
    x1, y1, x2, y2 = box
    lines = wrapped_lines(text, fnt, (x2 - x1) - max_width_pad * 2)
    heights = [draw.textbbox((0, 0), line, font=fnt)[3] for line in lines]
    total_h = sum(heights) + line_gap * (len(lines) - 1)
    y = y1 + ((y2 - y1) - total_h) / 2 - 2
    for line, height in zip(lines, heights):
        tw = draw.textbbox((0, 0), line, font=fnt)[2]
        draw.text((x1 + ((x2 - x1) - tw) / 2, y), line, font=fnt, fill=fill)
        y += height + line_gap


def rounded_box(
    cx: int,
    cy: int,
    w: int,
    h: int,
    text: str,
    *,
    fill: str = SOFT_GREEN,
    outline: str = GREEN,
    text_fill: str = TEXT,
    fnt: ImageFont.ImageFont = FONT_BODY,
    radius: int = 34,
    stroke: int = 5,
) -> tuple[int, int, int, int]:
    box = bbox_for_center(cx, cy, w, h)
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=stroke)
    center_text(box, text, fnt=fnt, fill=text_fill)
    return box


def pill(
    cx: int,
    cy: int,
    w: int,
    h: int,
    text: str,
    *,
    fill: str = GREEN,
    outline: str = DARK_GREEN,
    text_fill: str = "#ffffff",
) -> tuple[int, int, int, int]:
    return rounded_box(
        cx,
        cy,
        w,
        h,
        text,
        fill=fill,
        outline=outline,
        text_fill=text_fill,
        fnt=FONT_BODY_BOLD,
        radius=h // 2,
        stroke=5,
    )


def decision(
    cx: int,
    cy: int,
    w: int,
    h: int,
    text: str,
    *,
    fill: str = SOFT_GOLD,
    outline: str = GOLD,
) -> tuple[int, int, int, int]:
    points = [(cx, cy - h // 2), (cx + w // 2, cy), (cx, cy + h // 2), (cx - w // 2, cy)]
    draw.polygon(points, fill=fill, outline=outline)
    draw.line([*points, points[0]], fill=outline, width=5, joint="curve")
    box = bbox_for_center(cx, cy, w - 120, h - 42)
    center_text(box, text, fnt=FONT_BODY_BOLD, fill=TEXT, max_width_pad=10, line_gap=7)
    return bbox_for_center(cx, cy, w, h)


def database(cx: int, cy: int, w: int, h: int, text: str) -> tuple[int, int, int, int]:
    x1, y1, x2, y2 = bbox_for_center(cx, cy, w, h)
    ellipse_h = 48
    draw.rectangle((x1, y1 + ellipse_h // 2, x2, y2 - ellipse_h // 2), fill="#ffffff", outline=GREEN, width=5)
    draw.ellipse((x1, y1, x2, y1 + ellipse_h), fill="#ffffff", outline=GREEN, width=5)
    draw.arc((x1, y2 - ellipse_h, x2, y2), 0, 180, fill=GREEN, width=5)
    draw.line((x1, y1 + ellipse_h // 2, x1, y2 - ellipse_h // 2), fill=GREEN, width=5)
    draw.line((x2, y1 + ellipse_h // 2, x2, y2 - ellipse_h // 2), fill=GREEN, width=5)
    center_text((x1 + 60, y1 + 30, x2 - 60, y2 - 10), text, fnt=FONT_BODY_BOLD, fill=TEXT, line_gap=6)
    return (x1, y1, x2, y2)


def arrow(points: list[tuple[int, int]], *, color: str = LINE, width: int = 7) -> None:
    if len(points) < 2:
        return
    draw.line(points, fill=color, width=width, joint="curve")
    x1, y1 = points[-2]
    x2, y2 = points[-1]
    angle = math.atan2(y2 - y1, x2 - x1)
    size = 22
    spread = math.radians(28)
    p1 = (x2 - size * math.cos(angle - spread), y2 - size * math.sin(angle - spread))
    p2 = (x2 - size * math.cos(angle + spread), y2 - size * math.sin(angle + spread))
    draw.polygon([(x2, y2), p1, p2], fill=color)


def label(x: int, y: int, text: str, *, fill: str = "#ffffff", outline: str = LIGHT_LINE) -> None:
    pad_x, pad_y = 18, 8
    box = draw.textbbox((0, 0), text, font=FONT_SMALL_BOLD)
    tw, th = box[2] - box[0], box[3] - box[1]
    rect = (x - tw // 2 - pad_x, y - th // 2 - pad_y, x + tw // 2 + pad_x, y + th // 2 + pad_y)
    draw.rounded_rectangle(rect, radius=18, fill=fill, outline=outline, width=3)
    draw.text((x - tw // 2, y - th // 2 - 2), text, font=FONT_SMALL_BOLD, fill=TEXT)


def panel(cx: int, cy: int, w: int, h: int, title: str, items: list[str], *, accent: str, fill: str) -> tuple[int, int, int, int]:
    x1, y1, x2, y2 = bbox_for_center(cx, cy, w, h)
    draw.rounded_rectangle((x1, y1, x2, y2), radius=34, fill=fill, outline=accent, width=5)
    draw.rounded_rectangle((x1, y1, x2, y1 + 92), radius=34, fill=accent, outline=accent, width=5)
    draw.rectangle((x1, y1 + 48, x2, y1 + 92), fill=accent)
    tw = draw.textbbox((0, 0), title, font=FONT_PANEL_TITLE)[2]
    draw.text((cx - tw / 2, y1 + 24), title, font=FONT_PANEL_TITLE, fill="#ffffff")
    y = y1 + 128
    for item in items:
        bullet_x = x1 + 70
        draw.ellipse((bullet_x - 9, y + 8, bullet_x + 9, y + 26), fill=accent)
        lines = wrapped_lines(item, FONT_PANEL_BODY, w - 150)
        tx = x1 + 102
        for line in lines:
            draw.text((tx, y), line, font=FONT_PANEL_BODY, fill=TEXT)
            y += 37
        y += 20
    return (x1, y1, x2, y2)


# Title
title = "System Flowchart of the CMU ARLO System"
tw = draw.textbbox((0, 0), title, font=FONT_TITLE)[2]
draw.text(((W - tw) / 2, 66), title, font=FONT_TITLE, fill=GREEN)
subtitle = "Web-Based Tracer and Employment Analytics System"
sw = draw.textbbox((0, 0), subtitle, font=FONT_SUB)[2]
draw.text(((W - sw) / 2, 135), subtitle, font=FONT_SUB, fill=MUTED)

# Main access and registration flow
start = pill(2400, 270, 520, 112, "Start")
open_sys = rounded_box(2400, 440, 800, 122, "Open CMU ARLO Web System")
has_account = decision(2400, 660, 660, 190, "Does the user have an account?")

verify = rounded_box(1180, 650, 770, 122, "Verify alumni record")
record_found = decision(1180, 890, 620, 180, "Record found?")
verify_error = rounded_box(430, 890, 620, 112, "Show verification error", fill="#fff9dc", outline="#d39a00")
email = rounded_box(1180, 1125, 690, 112, "Verify email code")
create = rounded_box(1180, 1305, 720, 112, "Create alumni account")

login = rounded_box(2400, 1305, 600, 112, "Log in")
valid = decision(2400, 1525, 620, 180, "Valid credentials?")
login_error = rounded_box(3330, 1525, 740, 122, "Show login error or reset password", fill="#fff9dc", outline="#d39a00")
role = decision(2400, 1745, 620, 180, "Identify user role")

arrow([(2400, 326), (2400, 379)])
arrow([(2400, 501), (2400, 565)])
arrow([(2178, 660), (1565, 650)])
label(1915, 625, "No")
arrow([(2400, 755), (2400, 1247)])
label(2480, 920, "Yes")
arrow([(1180, 711), (1180, 800)])
arrow([(870, 890), (741, 890)])
label(810, 850, "No")
arrow([(430, 834), (430, 650), (795, 650)])
arrow([(1180, 980), (1180, 1068)])
label(1260, 1015, "Yes")
arrow([(1180, 1181), (1180, 1248)])
arrow([(1540, 1305), (2098, 1305)])
arrow([(2400, 1362), (2400, 1435)])
arrow([(2710, 1525), (2960, 1525)])
label(2840, 1486, "No")
arrow([(3330, 1464), (3330, 1305), (2705, 1305)])
arrow([(2400, 1615), (2400, 1656)])
label(2480, 1642, "Yes")

# Role panels
alumni_panel = panel(
    1000,
    2250,
    1130,
    530,
    "Alumni Dashboard",
    [
        "Manage profile and settings",
        "Submit tracer and employment data",
        "View jobs, announcements, and notifications",
        "Submit feedback or job opportunity",
    ],
    accent=GREEN,
    fill=PALE_GREEN,
)
admin_panel = panel(
    2400,
    2250,
    1130,
    530,
    "Administrator Dashboard",
    [
        "Manage alumni records",
        "Review jobs and job submissions",
        "Manage announcements and feedback",
        "View analytics, reports, and audit logs",
    ],
    accent="#d39a00",
    fill="#fffdf0",
)
super_panel = panel(
    3800,
    2250,
    1130,
    530,
    "Super Admin Dashboard",
    [
        "Manage administrator accounts",
        "Assign roles and permissions",
        "View Super Admin audit logs",
    ],
    accent=MUTED,
    fill="#f8faf8",
)

arrow([(2205, 1815), (1000, 1985)])
label(1450, 1912, "Alumni")
arrow([(2400, 1835), (2400, 1985)])
label(2510, 1910, "Administrator")
arrow([(2595, 1815), (3800, 1985)])
label(3350, 1912, "Super Admin")

db = database(
    2400,
    2865,
    2460,
    190,
    "System Database: alumni records, tracer records, jobs, announcements, feedback, notifications, analytics, and audit logs",
)
save = rounded_box(2400, 3090, 880, 112, "Save records and activity logs", fill=SOFT_GREEN, outline=GREEN)
end = pill(2400, 3238, 420, 104, "End")

arrow([(1000, 2515), (1000, 2670), (1400, 2815)])
arrow([(2400, 2515), (2400, 2769)])
arrow([(3800, 2515), (3800, 2670), (3400, 2815)])
arrow([(2400, 2960), (2400, 3033)])
arrow([(2400, 3146), (2400, 3186)])

# Footer note inside image for context, kept small enough not to compete with diagram.
note = "Figure 4. System Flowchart of the Web-Based Tracer and Employment Analytics System"
nw = draw.textbbox((0, 0), note, font=FONT_SMALL)[2]
draw.text(((W - nw) / 2, H - 58), note, font=FONT_SMALL, fill=MUTED)

OUT.parent.mkdir(parents=True, exist_ok=True)
img.save(OUT, dpi=(300, 300), quality=95)
print(OUT)
