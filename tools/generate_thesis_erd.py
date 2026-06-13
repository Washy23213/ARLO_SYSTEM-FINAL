from pathlib import Path
from typing import Iterable

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUTS = [
    ROOT / "docs" / "arlo-erd-thesis-style.png",
    ROOT / "docs" / "figure-6-erd-cmu-arlo.png",
]

SCALE = 2
WIDTH, HEIGHT = 2400, 1550
TABLE_W = 340
TITLE_W = 38
KEY_W = 58
ROW_H = 25
LINE = 2


def sc(value: int | float) -> int:
    return int(round(value * SCALE))


def font(name: str, size: int) -> ImageFont.FreeTypeFont:
    return ImageFont.truetype(str(Path("C:/Windows/Fonts") / name), size * SCALE)


FONT_TITLE = font("arialbd.ttf", 18)
FONT_CELL = font("arial.ttf", 16)
FONT_CELL_BOLD = font("arialbd.ttf", 15)


class Table:
    def __init__(self, name: str, x: int, y: int, fields: list[tuple[str, str]]):
        self.name = name
        self.x = x
        self.y = y
        self.fields = fields
        self.w = TABLE_W
        self.h = max(ROW_H * len(fields), 140)

    @property
    def left(self) -> int:
        return self.x

    @property
    def right(self) -> int:
        return self.x + self.w

    @property
    def top(self) -> int:
        return self.y

    @property
    def bottom(self) -> int:
        return self.y + self.h

    @property
    def cx(self) -> int:
        return self.x + self.w // 2

    @property
    def cy(self) -> int:
        return self.y + self.h // 2

    def side(self, where: str, offset: int = 0) -> tuple[int, int]:
        if where == "left":
            return (self.left, self.cy + offset)
        if where == "right":
            return (self.right, self.cy + offset)
        if where == "top":
            return (self.cx + offset, self.top)
        if where == "bottom":
            return (self.cx + offset, self.bottom)
        raise ValueError(where)


TABLES = {
    "roles": Table(
        "Roles",
        60,
        80,
        [
            ("PK", "id"),
            ("", "name"),
            ("", "label"),
            ("", "guard_name"),
            ("", "description"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "admin_role": Table(
        "AdminRole",
        450,
        80,
        [
            ("PK FK", "admin_id"),
            ("PK FK", "role_id"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "admins": Table(
        "Admins",
        840,
        80,
        [
            ("PK", "id"),
            ("", "firstname"),
            ("", "lastname"),
            ("UK", "email"),
            ("", "password"),
            ("", "phone"),
            ("", "role"),
            ("", "email_verified_at"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "super_admins": Table(
        "SuperAdmins",
        1230,
        80,
        [
            ("PK", "id"),
            ("", "firstname"),
            ("", "lastname"),
            ("UK", "email"),
            ("", "password"),
            ("", "phone"),
            ("", "email_verified_at"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "role_permission": Table(
        "RolePermission",
        1620,
        80,
        [
            ("PK FK", "role_id"),
            ("PK FK", "permission_id"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "permissions": Table(
        "Permissions",
        2010,
        80,
        [
            ("PK", "id"),
            ("UK", "name"),
            ("", "label"),
            ("", "guard_name"),
            ("", "description"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "users": Table(
        "Users",
        60,
        520,
        [
            ("PK", "id"),
            ("", "firstname"),
            ("", "middlename"),
            ("", "lastname"),
            ("UK", "email"),
            ("", "password"),
            ("", "phone"),
            ("", "student_idnum"),
            ("", "alumni_idnum"),
            ("", "department"),
            ("", "course"),
            ("", "year_graduated"),
            ("", "employed_after_grad"),
            ("", "data_privacy_consent"),
            ("", "newsletter_opt_in"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "jobs": Table(
        "Jobs",
        450,
        520,
        [
            ("PK", "id"),
            ("", "title"),
            ("", "company"),
            ("", "description"),
            ("", "requirements"),
            ("", "location"),
            ("", "type"),
            ("", "salary_range"),
            ("", "application_deadline"),
            ("FK", "created_by"),
            ("FK", "archived_by"),
            ("", "is_active"),
            ("", "views_count"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "announcements": Table(
        "Announcements",
        840,
        520,
        [
            ("PK", "id"),
            ("", "title"),
            ("", "content"),
            ("", "type"),
            ("", "priority"),
            ("", "target_audience"),
            ("", "event_date"),
            ("", "is_published"),
            ("", "published_at"),
            ("", "expires_at"),
            ("FK", "created_by"),
            ("FK", "archived_by"),
            ("", "target_department"),
            ("", "target_graduation_year"),
            ("", "facebook_post_id"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "announcement_reads": Table(
        "AnnouncementRead",
        1230,
        520,
        [
            ("PK", "id"),
            ("FK", "announcement_id"),
            ("FK", "user_id"),
            ("", "read_at"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "feedback": Table(
        "Feedback",
        1620,
        520,
        [
            ("PK", "id"),
            ("FK", "user_id"),
            ("", "subject"),
            ("", "message"),
            ("", "type"),
            ("", "status"),
            ("", "priority"),
            ("FK", "assigned_to"),
            ("", "resolved_at"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "notifications": Table(
        "Notifications",
        1230,
        810,
        [
            ("PK", "id"),
            ("", "type"),
            ("", "notifiable_type"),
            ("", "notifiable_id"),
            ("", "data"),
            ("", "read_at"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "job_submissions": Table(
        "JobSubmission",
        450,
        960,
        [
            ("PK", "id"),
            ("FK", "user_id"),
            ("", "title"),
            ("", "company"),
            ("", "type"),
            ("", "location"),
            ("", "contact_email"),
            ("", "application_deadline"),
            ("", "status"),
            ("", "admin_notes"),
            ("FK", "reviewed_by"),
            ("", "reviewed_at"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
    "system_logs": Table(
        "SystemLog",
        840,
        1010,
        [
            ("PK", "id"),
            ("", "user_id"),
            ("", "action"),
            ("", "description"),
            ("", "ip_address"),
            ("", "user_agent"),
            ("FK", "admin_id"),
            ("FK", "super_admin_id"),
            ("", "created_at"),
        ],
    ),
    "tracer_records": Table(
        "TracerRecord",
        60,
        1030,
        [
            ("PK", "id"),
            ("FK", "user_id"),
            ("UK", "tracer_number"),
            ("", "tracer_date"),
            ("", "is_employed"),
            ("", "employment_type"),
            ("", "current_position"),
            ("", "current_company"),
            ("", "monthly_income"),
            ("", "industry"),
            ("", "job_satisfaction"),
            ("", "present_employment_status"),
            ("", "employment_proof_status"),
            ("", "employment_proof_reviewed_by_id"),
            ("", "created_at"),
            ("", "updated_at"),
        ],
    ),
}


def draw_fit(draw: ImageDraw.ImageDraw, xy: tuple[int, int], text: str, max_w: int, *, bold=False):
    fonts = [FONT_CELL_BOLD if bold else FONT_CELL]
    for size in [15, 14, 13, 12]:
        fonts.append(font("arialbd.ttf" if bold else "arial.ttf", size))
    for fnt in fonts:
        bbox = draw.textbbox((0, 0), text, font=fnt)
        if bbox[2] - bbox[0] <= sc(max_w):
            draw.text((sc(xy[0]), sc(xy[1])), text, fill="black", font=fnt)
            return
    clipped = text[: max(6, max_w // 9)] + "..."
    draw.text((sc(xy[0]), sc(xy[1])), clipped, fill="black", font=fonts[-1])


def draw_rotated(img: Image.Image, text: str, rect: tuple[int, int, int, int]):
    x, y, w, h = rect
    layer = Image.new("RGBA", (sc(h), sc(w)), (255, 255, 255, 0))
    d = ImageDraw.Draw(layer)

    title_font = FONT_TITLE
    for size in [18, 17, 16, 15, 14, 13, 12]:
        candidate = font("arialbd.ttf", size)
        bbox = d.textbbox((0, 0), text, font=candidate)
        if bbox[2] - bbox[0] <= sc(h - 12):
            title_font = candidate
            break

    bbox = d.textbbox((0, 0), text, font=title_font)
    tw, th = bbox[2] - bbox[0], bbox[3] - bbox[1]
    d.text(((sc(h) - tw) // 2, (sc(w) - th) // 2), text, fill="black", font=title_font)
    rotated = layer.rotate(90, expand=True)
    img.paste(rotated, (sc(x), sc(y)), rotated)


def draw_table(img: Image.Image, draw: ImageDraw.ImageDraw, table: Table):
    x, y, w, h = table.x, table.y, table.w, table.h
    draw.rectangle((sc(x), sc(y), sc(x + w), sc(y + h)), fill="white", outline="black", width=sc(LINE))
    draw.line((sc(x + TITLE_W), sc(y), sc(x + TITLE_W), sc(y + h)), fill="black", width=sc(LINE))
    draw.line((sc(x + TITLE_W + KEY_W), sc(y), sc(x + TITLE_W + KEY_W), sc(y + h)), fill="black", width=sc(1))

    draw_rotated(img, table.name, (x, y, TITLE_W, h))

    for i, (key, name) in enumerate(table.fields):
        row_y = y + i * ROW_H
        if i > 0:
            draw.line((sc(x + TITLE_W), sc(row_y), sc(x + w), sc(row_y)), fill="black", width=sc(1))
        draw_fit(draw, (x + TITLE_W + 7, row_y + 5), key, KEY_W - 12, bold=bool(key))
        draw_fit(draw, (x + TITLE_W + KEY_W + 9, row_y + 5), name, w - TITLE_W - KEY_W - 18)


def draw_polyline(draw: ImageDraw.ImageDraw, points: Iterable[tuple[int, int]], *, dashed=False):
    pts = list(points)
    for a, b in zip(pts, pts[1:]):
        if dashed:
            draw_dashed(draw, a, b)
        else:
            draw.line((sc(a[0]), sc(a[1]), sc(b[0]), sc(b[1])), fill="black", width=sc(2))


def draw_dashed(draw: ImageDraw.ImageDraw, a: tuple[int, int], b: tuple[int, int]):
    x1, y1 = a
    x2, y2 = b
    dx, dy = x2 - x1, y2 - y1
    length = max(1, (dx * dx + dy * dy) ** 0.5)
    dash, gap = 12, 8
    dist = 0
    while dist < length:
        end = min(dist + dash, length)
        sx = x1 + dx * dist / length
        sy = y1 + dy * dist / length
        ex = x1 + dx * end / length
        ey = y1 + dy * end / length
        draw.line((sc(sx), sc(sy), sc(ex), sc(ey)), fill="black", width=sc(2))
        dist += dash + gap


def marker_bar(draw: ImageDraw.ImageDraw, point: tuple[int, int], prev: tuple[int, int]):
    x, y = point
    dx, dy = point[0] - prev[0], point[1] - prev[1]
    if abs(dx) >= abs(dy):
        draw.line((sc(x), sc(y - 9), sc(x), sc(y + 9)), fill="black", width=sc(2))
    else:
        draw.line((sc(x - 9), sc(y), sc(x + 9), sc(y)), fill="black", width=sc(2))


def marker_crow(draw: ImageDraw.ImageDraw, point: tuple[int, int], prev: tuple[int, int]):
    x, y = point
    dx, dy = point[0] - prev[0], point[1] - prev[1]
    if abs(dx) >= abs(dy):
        sign = -1 if dx > 0 else 1
        base = x + sign * 18
        draw.line((sc(x), sc(y), sc(base), sc(y)), fill="black", width=sc(2))
        draw.line((sc(x), sc(y), sc(base), sc(y - 10)), fill="black", width=sc(2))
        draw.line((sc(x), sc(y), sc(base), sc(y + 10)), fill="black", width=sc(2))
    else:
        sign = -1 if dy > 0 else 1
        base = y + sign * 18
        draw.line((sc(x), sc(y), sc(x), sc(base)), fill="black", width=sc(2))
        draw.line((sc(x), sc(y), sc(x - 10), sc(base)), fill="black", width=sc(2))
        draw.line((sc(x), sc(y), sc(x + 10), sc(base)), fill="black", width=sc(2))


def relation(draw: ImageDraw.ImageDraw, points: list[tuple[int, int]], dashed=False):
    draw_polyline(draw, points, dashed=dashed)
    if len(points) >= 2:
        marker_bar(draw, points[0], points[1])
        marker_crow(draw, points[-1], points[-2])


def make():
    img = Image.new("RGB", (sc(WIDTH), sc(HEIGHT)), "white")
    draw = ImageDraw.Draw(img)

    # Relationships are drawn first, then entities are drawn over them for a neat ERD look.
    rels = [
        # RBAC
        [TABLES["roles"].side("right"), TABLES["admin_role"].side("left")],
        [TABLES["admins"].side("left"), TABLES["admin_role"].side("right")],
        [TABLES["permissions"].side("left"), TABLES["role_permission"].side("right")],
        [TABLES["roles"].side("top", 40), (TABLES["roles"].cx + 40, 35), (TABLES["role_permission"].cx - 40, 35), TABLES["role_permission"].side("top", -40)],
        # Alumni data
        [TABLES["users"].side("bottom", -45), TABLES["tracer_records"].side("top", -45)],
        [TABLES["users"].side("right", -20), (420, TABLES["users"].cy - 20), (420, TABLES["job_submissions"].cy - 35), TABLES["job_submissions"].side("left", -35)],
        [TABLES["users"].side("right", 45), (410, TABLES["users"].cy + 45), (410, 495), (1600, 495), TABLES["feedback"].side("left", -20)],
        # Admin-managed jobs and submissions
        [TABLES["admins"].side("bottom", -45), (TABLES["admins"].cx - 45, 470), (TABLES["jobs"].cx + 55, 470), TABLES["jobs"].side("top", 55)],
        [TABLES["admins"].side("bottom", -5), (TABLES["admins"].cx - 5, 930), TABLES["job_submissions"].side("right", -20)],
        # Announcements and reads
        [TABLES["admins"].side("bottom", 45), (TABLES["admins"].cx + 45, 480), (TABLES["announcements"].cx - 25, 480), TABLES["announcements"].side("top", -25)],
        [TABLES["announcements"].side("right", -20), TABLES["announcement_reads"].side("left", -20)],
        [TABLES["users"].side("top", 45), (TABLES["users"].cx + 45, 470), (TABLES["announcement_reads"].cx + 45, 470), TABLES["announcement_reads"].side("top", 45)],
        # Feedback and audit trail
        [TABLES["admins"].side("right", 15), (1560, TABLES["admins"].cy + 15), (1560, TABLES["feedback"].cy + 30), TABLES["feedback"].side("left", 30)],
        [TABLES["admins"].side("bottom", 75), (TABLES["admins"].cx + 75, 960), TABLES["system_logs"].side("top", 30)],
        [TABLES["super_admins"].side("bottom", -20), (TABLES["super_admins"].cx - 20, 965), TABLES["system_logs"].side("right", -25)],
    ]
    for pts in rels:
        relation(draw, pts)

    # Laravel notifications are polymorphic, so show the link as logical instead of a physical FK.
    relation(
        draw,
        [TABLES["users"].side("right", 100), (1180, TABLES["users"].cy + 100), TABLES["notifications"].side("left", -10)],
        dashed=True,
    )

    for table in TABLES.values():
        draw_table(img, draw, table)

    img = img.resize((WIDTH, HEIGHT), Image.Resampling.LANCZOS)
    for out in OUTS:
        out.parent.mkdir(parents=True, exist_ok=True)
        img.save(out, "PNG", optimize=True)
        print(out)


if __name__ == "__main__":
    make()
