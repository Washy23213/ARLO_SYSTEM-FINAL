from __future__ import annotations

import math
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "figure-erd-cmu-arlo-crystal-clear.png"

W, H = 6000, 3900
BG = "#ffffff"
TEXT = "#102414"
MUTED = "#35543b"
GREEN = "#006000"
DARK_GREEN = "#003300"
SOFT_GREEN = "#eef7ec"
PALE_GREEN = "#f8fff4"
GOLD = "#d39a00"
SOFT_GOLD = "#fff8dc"
BLUE = "#2563eb"
SOFT_BLUE = "#eff6ff"
GRAY = "#475569"
SOFT_GRAY = "#f8fafc"
RED = "#c2410c"
SOFT_RED = "#fff7ed"
LINE_GREEN = "#36734a"
LINE_GOLD = "#b58500"
LINE_BLUE = "#4f75c6"
LINE_RED = "#b24a20"
LINE_GRAY = "#94a3b8"


def font(name: str, size: int) -> ImageFont.FreeTypeFont:
    candidates = [
        Path("C:/Windows/Fonts") / name,
        Path("/usr/share/fonts/truetype/dejavu") / name,
    ]
    for path in candidates:
        if path.exists():
            return ImageFont.truetype(str(path), size)
    return ImageFont.load_default()


FONT_TITLE = font("arialbd.ttf", 66)
FONT_SUB = font("arial.ttf", 32)
FONT_ZONE = font("arialbd.ttf", 34)
FONT_ENTITY = font("arialbd.ttf", 42)
FONT_ROW = font("arial.ttf", 38)
FONT_ROW_BOLD = font("arialbd.ttf", 32)
FONT_LABEL = font("arialbd.ttf", 30)
FONT_NOTE = font("arial.ttf", 29)

img = Image.new("RGB", (W, H), BG)
draw = ImageDraw.Draw(img)


class Entity:
    def __init__(
        self,
        key: str,
        title: str,
        x: int,
        y: int,
        w: int,
        rows: list[tuple[str, str]],
        color: str,
        fill: str,
    ) -> None:
        self.key = key
        self.title = title
        self.x = x
        self.y = y
        self.w = w
        self.rows = rows
        self.color = color
        self.fill = fill
        self.header_h = 90
        self.row_h = 66
        self.h = self.header_h + self.row_h * len(rows)

    @property
    def left(self) -> tuple[int, int]:
        return (self.x, self.y + self.h // 2)

    @property
    def right(self) -> tuple[int, int]:
        return (self.x + self.w, self.y + self.h // 2)

    @property
    def top(self) -> tuple[int, int]:
        return (self.x + self.w // 2, self.y)

    @property
    def bottom(self) -> tuple[int, int]:
        return (self.x + self.w // 2, self.y + self.h)

    def port(self, side: str, offset: int = 0) -> tuple[int, int]:
        if side == "left":
            return (self.x, self.y + self.h // 2 + offset)
        if side == "right":
            return (self.x + self.w, self.y + self.h // 2 + offset)
        if side == "top":
            return (self.x + self.w // 2 + offset, self.y)
        if side == "bottom":
            return (self.x + self.w // 2 + offset, self.y + self.h)
        raise ValueError(side)


entities = {
    "users": Entity(
        "users",
        "USERS",
        210,
        430,
        1120,
        [
            ("PK", "id"),
            ("UK", "email"),
            ("", "firstname, lastname"),
            ("", "alumni_idnum / student_idnum"),
            ("", "department, course, year_graduated"),
            ("", "employment summary"),
            ("", "scholarship and profile fields"),
            ("", "role, visibility, consent"),
            ("", "batch import and timestamps"),
        ],
        GREEN,
        PALE_GREEN,
    ),
    "tracer": Entity(
        "tracer",
        "TRACER_RECORDS",
        210,
        1210,
        1120,
        [
            ("PK", "id"),
            ("UK", "tracer_number"),
            ("FK", "user_id"),
            ("", "tracer_date"),
            ("", "employment status"),
            ("", "position, company, industry"),
            ("", "income, job level, satisfaction"),
            ("", "employment proof review"),
        ],
        GREEN,
        PALE_GREEN,
    ),
    "feedback": Entity(
        "feedback",
        "FEEDBACK",
        210,
        1940,
        1120,
        [
            ("PK", "id"),
            ("FK", "user_id"),
            ("FK", "assigned_to"),
            ("", "subject, message, type"),
            ("", "status, priority"),
            ("", "resolved_at, timestamps"),
        ],
        GREEN,
        PALE_GREEN,
    ),
    "announcement_reads": Entity(
        "announcement_reads",
        "ANNOUNCEMENT_READS",
        210,
        2595,
        1120,
        [
            ("PK", "id"),
            ("FK", "announcement_id"),
            ("FK", "user_id"),
            ("", "read_at, timestamps"),
        ],
        GREEN,
        PALE_GREEN,
    ),
    "job_submissions": Entity(
        "job_submissions",
        "JOB_SUBMISSIONS",
        1600,
        430,
        1120,
        [
            ("PK", "id"),
            ("FK", "user_id"),
            ("FK", "reviewed_by"),
            ("", "title, company, type"),
            ("", "location, contact, deadline"),
            ("", "description, requirements"),
            ("", "status, admin_notes, reviewed_at"),
        ],
        GOLD,
        SOFT_GOLD,
    ),
    "jobs": Entity(
        "jobs",
        "JOBS",
        1600,
        1210,
        1120,
        [
            ("PK", "id"),
            ("FK", "created_by"),
            ("FK", "archived_by"),
            ("", "title, company, location"),
            ("", "type, salary, deadline"),
            ("", "is_active, views_count"),
            ("", "archive timestamps"),
        ],
        GOLD,
        SOFT_GOLD,
    ),
    "announcements": Entity(
        "announcements",
        "ANNOUNCEMENTS",
        1600,
        1940,
        1120,
        [
            ("PK", "id"),
            ("FK", "created_by"),
            ("FK", "archived_by"),
            ("UK", "facebook_post_id"),
            ("", "title, content, type, priority"),
            ("", "target audience and schedule"),
            ("", "published and archive fields"),
        ],
        GOLD,
        SOFT_GOLD,
    ),
    "notifications": Entity(
        "notifications",
        "NOTIFICATIONS",
        1600,
        2740,
        1120,
        [
            ("PK", "id"),
            ("", "type"),
            ("", "notifiable_type"),
            ("", "notifiable_id"),
            ("", "data, read_at, timestamps"),
        ],
        GOLD,
        SOFT_GOLD,
    ),
    "admins": Entity(
        "admins",
        "ADMINS",
        3090,
        430,
        1120,
        [
            ("PK", "id"),
            ("UK", "email"),
            ("", "firstname, lastname, phone"),
            ("", "role"),
            ("", "password, remember_token"),
            ("", "email_verified_at, timestamps"),
        ],
        BLUE,
        SOFT_BLUE,
    ),
    "admin_role": Entity(
        "admin_role",
        "ADMIN_ROLE",
        3090,
        1160,
        1120,
        [
            ("PK/FK", "admin_id"),
            ("PK/FK", "role_id"),
            ("", "created_at, updated_at"),
        ],
        BLUE,
        SOFT_BLUE,
    ),
    "system_logs": Entity(
        "system_logs",
        "SYSTEM_LOGS",
        3090,
        1830,
        1120,
        [
            ("PK", "id"),
            ("FK", "user_id"),
            ("FK", "admin_id"),
            ("FK", "super_admin_id"),
            ("", "action, description"),
            ("", "ip_address, user_agent"),
            ("", "created_at"),
        ],
        RED,
        SOFT_RED,
    ),
    "super_admins": Entity(
        "super_admins",
        "SUPER_ADMINS",
        4570,
        430,
        1120,
        [
            ("PK", "id"),
            ("UK", "email"),
            ("", "firstname, lastname, phone"),
            ("", "password, remember_token"),
            ("", "email_verified_at, timestamps"),
        ],
        GRAY,
        SOFT_GRAY,
    ),
    "roles": Entity(
        "roles",
        "ROLES",
        4570,
        1090,
        1120,
        [
            ("PK", "id"),
            ("UK", "name"),
            ("", "label, guard_name"),
            ("", "description, timestamps"),
        ],
        BLUE,
        SOFT_BLUE,
    ),
    "role_permission": Entity(
        "role_permission",
        "ROLE_PERMISSION",
        4570,
        1700,
        1120,
        [
            ("PK/FK", "role_id"),
            ("PK/FK", "permission_id"),
            ("", "created_at, updated_at"),
        ],
        BLUE,
        SOFT_BLUE,
    ),
    "permissions": Entity(
        "permissions",
        "PERMISSIONS",
        4570,
        2310,
        1120,
        [
            ("PK", "id"),
            ("UK", "name"),
            ("", "label, guard_name"),
            ("", "description, timestamps"),
        ],
        BLUE,
        SOFT_BLUE,
    ),
}


def text_width(text: str, fnt: ImageFont.ImageFont) -> int:
    box = draw.textbbox((0, 0), text, font=fnt)
    return box[2] - box[0]


def draw_title() -> None:
    title = "Entity Relationship Diagram of the CMU ARLO System"
    subtitle = "Web-Based Tracer and Employment Analytics System for the Alumni Relations and Linkages Office"
    draw.text(((W - text_width(title, FONT_TITLE)) / 2, 52), title, fill=GREEN, font=FONT_TITLE)
    draw.text(((W - text_width(subtitle, FONT_SUB)) / 2, 126), subtitle, fill=MUTED, font=FONT_SUB)


def draw_zone(x: int, y: int, w: int, h: int, text: str, color: str) -> None:
    draw.rounded_rectangle((x, y, x + w, y + h), radius=28, fill="#ffffff", outline=color, width=3)
    draw.rounded_rectangle((x, y, x + w, y + 54), radius=28, fill=color, outline=color, width=3)
    draw.rectangle((x, y + 28, x + w, y + 54), fill=color)
    draw.text((x + 24, y + 9), text, fill="#ffffff", font=FONT_ZONE)


def draw_entity(entity: Entity) -> None:
    x, y, w, h = entity.x, entity.y, entity.w, entity.h
    draw.rounded_rectangle((x, y, x + w, y + h), radius=24, fill=entity.fill, outline=entity.color, width=5)
    draw.rounded_rectangle((x, y, x + w, y + entity.header_h), radius=24, fill=entity.color, outline=entity.color, width=5)
    draw.rectangle((x, y + 34, x + w, y + entity.header_h), fill=entity.color)
    draw.text((x + (w - text_width(entity.title, FONT_ENTITY)) / 2, y + 22), entity.title, fill="#ffffff", font=FONT_ENTITY)

    key_w = 165
    for index, (kind, value) in enumerate(entity.rows):
        row_y = y + entity.header_h + index * entity.row_h
        if index > 0:
            draw.line((x, row_y, x + w, row_y), fill=entity.color, width=2)
        draw.line((x + key_w, row_y, x + key_w, row_y + entity.row_h), fill=entity.color, width=2)
        kind_color = entity.color if kind else MUTED
        draw.text((x + 18, row_y + 16), kind, fill=kind_color, font=FONT_ROW_BOLD)
        draw.text((x + key_w + 24, row_y + 13), value, fill=TEXT, font=FONT_ROW)


def arrow(points: list[tuple[int, int]], color: str, width: int = 7, dashed: bool = False) -> None:
    if len(points) < 2:
        return
    if dashed:
        for p1, p2 in zip(points, points[1:]):
            draw_dashed_line(p1, p2, fill=color, width=width, dash=24, gap=16)
    else:
        draw.line(points, fill=color, width=width, joint="curve")

    x1, y1 = points[-2]
    x2, y2 = points[-1]
    angle = math.atan2(y2 - y1, x2 - x1)
    size = 23
    spread = math.radians(28)
    p1 = (x2 - size * math.cos(angle - spread), y2 - size * math.sin(angle - spread))
    p2 = (x2 - size * math.cos(angle + spread), y2 - size * math.sin(angle + spread))
    draw.polygon([(x2, y2), p1, p2], fill=color)


def draw_dashed_line(p1: tuple[int, int], p2: tuple[int, int], fill: str, width: int, dash: int, gap: int) -> None:
    x1, y1 = p1
    x2, y2 = p2
    dx, dy = x2 - x1, y2 - y1
    length = math.hypot(dx, dy)
    if length == 0:
        return
    ux, uy = dx / length, dy / length
    dist = 0
    while dist < length:
        start = dist
        end = min(dist + dash, length)
        draw.line(
            (
                x1 + ux * start,
                y1 + uy * start,
                x1 + ux * end,
                y1 + uy * end,
            ),
            fill=fill,
            width=width,
        )
        dist += dash + gap


def relation_label(x: int, y: int, text: str, color: str) -> None:
    lines = text.split("\n")
    widths = [text_width(line, FONT_LABEL) for line in lines]
    box_w = max(widths) + 34
    box_h = 46 * len(lines) + 20
    draw.rounded_rectangle((x - box_w // 2, y - box_h // 2, x + box_w // 2, y + box_h // 2), radius=18, fill="#ffffff", outline=color, width=3)
    ty = y - (46 * len(lines)) / 2 + 6
    for line in lines:
        draw.text((x - text_width(line, FONT_LABEL) / 2, ty), line, fill=color, font=FONT_LABEL)
        ty += 46


def rel(points: list[tuple[int, int]], label: str, label_xy: tuple[int, int], color: str, dashed: bool = False) -> None:
    arrow(points, color=color, dashed=dashed)
    relation_label(label_xy[0], label_xy[1], label, color)


def draw_relationships() -> None:
    e = entities

    # Alumni-owned records
    rel([e["users"].port("bottom", -220), (430, 1125), e["tracer"].port("top", -220)], "1:M\nhas", (430, 1110), LINE_GREEN)
    rel([e["users"].port("right", -120), (1460, 660), e["job_submissions"].port("left", -80)], "1:M\nsubmits", (1452, 620), LINE_GREEN)
    rel([e["users"].port("bottom", 190), (1040, 1810), (1040, 2070), e["feedback"].port("right", -40)], "1:M\nsends", (1025, 1835), LINE_GREEN)
    rel([e["users"].port("bottom", 320), (640, 2520), e["announcement_reads"].port("top", -260)], "1:M\nreads", (670, 2490), LINE_GREEN)
    rel([e["users"].port("right", 160), (1500, 1030), (1500, 2980), e["notifications"].port("left", -20)], "1:M\nreceives", (1500, 2870), LINE_GREEN)

    # Engagement relationships
    rel([e["announcements"].port("bottom", -210), (1940, 2570), e["announcement_reads"].port("right", -60)], "1:M\nread by", (1940, 2570), LINE_GOLD)
    rel([e["job_submissions"].port("bottom", 180), (2500, 1120), (2500, 1210)], "approved\ninto", (2550, 1135), LINE_GOLD, dashed=True)

    # Admin operations
    rel([e["admins"].port("left", -110), (2860, 680), e["job_submissions"].port("right", -70)], "1:M\nreviews", (2865, 650), LINE_GOLD)
    rel([e["admins"].port("left", 20), (2860, 1340), e["jobs"].port("right", -50)], "1:M\ncreates", (2875, 1320), LINE_GOLD)
    rel([e["admins"].port("left", 155), (2870, 2120), e["announcements"].port("right", -80)], "1:M\npublishes", (2870, 2095), LINE_GOLD)
    rel([e["admins"].port("bottom", -320), (3400, 1010), (3400, 1830), e["system_logs"].port("top", -260)], "1:M\nlogs", (3388, 1548), LINE_RED)
    rel([e["users"].port("right", 300), (3000, 1020), (3000, 2030), e["system_logs"].port("left", -70)], "1:M\nlogs", (3010, 1548), LINE_RED)
    rel([e["admins"].port("bottom", 180), (3660, 1070)], "M:N\nroles", (3740, 1015), LINE_BLUE)
    rel([e["admin_role"].port("right", -20), (4480, 1250), e["roles"].port("left", 0)], "M:1\nrole", (4480, 1245), LINE_BLUE)
    rel([e["roles"].port("bottom", 200), (5125, 1700)], "1:M\ngrants", (5225, 1615), LINE_BLUE)
    rel([e["role_permission"].port("bottom", -100), (5020, 2310)], "M:1\npermission", (5020, 2225), LINE_BLUE)

    # Super Admin accountability and account control
    rel([e["super_admins"].port("left", 0), (4350, 640), e["admins"].port("right", -30)], "manages\naccounts", (4350, 620), LINE_GRAY, dashed=True)
    rel([e["super_admins"].port("bottom", -280), (4940, 1650), (4210, 2130)], "1:M\nlogs", (4940, 1570), LINE_RED)


def draw_legend() -> None:
    items = [
        (240, 3460, GREEN, PALE_GREEN, "Alumni records"),
        (1120, 3460, GOLD, SOFT_GOLD, "Engagement and services"),
        (2280, 3460, BLUE, SOFT_BLUE, "Role-based access control"),
        (3620, 3460, RED, SOFT_RED, "Audit trail"),
        (4580, 3460, GRAY, SOFT_GRAY, "Super Admin account control"),
    ]
    for x, y, color, fill, text in items:
        draw.rounded_rectangle((x, y - 30, x + 54, y + 18), radius=10, fill=fill, outline=color, width=4)
        draw.text((x + 72, y - 23), text, fill=MUTED, font=FONT_NOTE)

    note = (
        "Note: The figure shows the core paper-ready ERD. Laravel support tables such as password_reset_tokens, "
        "sessions, cache, cache_locks, failed_jobs, and migrations are implementation support tables."
    )
    draw.rounded_rectangle((220, 3545, 5780, 3660), radius=24, fill="#ffffff", outline=LINE_GRAY, width=3)
    draw.text((260, 3582), note, fill=MUTED, font=FONT_NOTE)

    footer = "Figure. Entity Relationship Diagram of the Web-Based Tracer and Employment Analytics System"
    draw.text(((W - text_width(footer, FONT_NOTE)) / 2, H - 72), footer, fill=MUTED, font=FONT_NOTE)


draw_title()
draw_zone(170, 360, 1200, 2935, "Alumni Data", GREEN)
draw_zone(1560, 360, 1200, 2935, "Engagement Records", GOLD)
draw_zone(3050, 360, 1200, 2500, "Administration and Audit", BLUE)
draw_zone(4530, 360, 1200, 2500, "Super Admin and RBAC", GRAY)
draw_relationships()
for entity in entities.values():
    draw_entity(entity)
draw_legend()

OUT.parent.mkdir(parents=True, exist_ok=True)
img.save(OUT, dpi=(300, 300), quality=95)
print(OUT)
