from pathlib import Path
import textwrap

from PIL import Image, ImageDraw, ImageFont


ROOT = Path(__file__).resolve().parents[1]
OUTS = [
    ROOT / "docs" / "arlo-dfd-level0.png",
    ROOT / "docs" / "arlo-dfd-context-hd.png",
]

SCALE = 3
W, H = 1600, 900


def font(name: str, size: int) -> ImageFont.FreeTypeFont:
    font_path = Path("C:/Windows/Fonts") / name
    return ImageFont.truetype(str(font_path), size * SCALE)


FONT_REG = font("arial.ttf", 27)
FONT_SMALL = font("arial.ttf", 23)
FONT_BOLD = font("arialbd.ttf", 28)
FONT_TITLE = font("arialbd.ttf", 30)
FONT_NUM = font("arialbd.ttf", 30)


def s(v: int) -> int:
    return v * SCALE


def box(draw: ImageDraw.ImageDraw, xy, radius=10, width=3):
    x1, y1, x2, y2 = [s(v) for v in xy]
    draw.rounded_rectangle(
        (x1, y1, x2, y2),
        radius=s(radius),
        fill="white",
        outline="black",
        width=s(width),
    )


def centered(draw: ImageDraw.ImageDraw, text: str, cx: int, cy: int, fnt, fill="black", spacing=8):
    lines = text.split("\n")
    heights = []
    widths = []
    for line in lines:
        bbox = draw.textbbox((0, 0), line, font=fnt)
        widths.append(bbox[2] - bbox[0])
        heights.append(bbox[3] - bbox[1])
    total_h = sum(heights) + s(spacing) * (len(lines) - 1)
    y = s(cy) - total_h // 2
    for line, line_w, line_h in zip(lines, widths, heights):
        draw.text((s(cx) - line_w // 2, y), line, font=fnt, fill=fill)
        y += line_h + s(spacing)


def wrapped_label(draw: ImageDraw.ImageDraw, text: str, cx: int, cy: int, width_chars: int = 20):
    wrapped = "\n".join(textwrap.wrap(text, width=width_chars))
    centered(draw, wrapped, cx, cy, FONT_SMALL, spacing=6)


def arrow(draw: ImageDraw.ImageDraw, start, end, width=3):
    x1, y1 = [s(v) for v in start]
    x2, y2 = [s(v) for v in end]
    draw.line((x1, y1, x2, y2), fill="black", width=s(width))

    if x2 >= x1:
        head = [(x2, y2), (x2 - s(18), y2 - s(9)), (x2 - s(18), y2 + s(9))]
    else:
        head = [(x2, y2), (x2 + s(18), y2 - s(9)), (x2 + s(18), y2 + s(9))]
    draw.polygon(head, fill="black")


def main():
    img = Image.new("RGB", (s(W), s(H)), "white")
    draw = ImageDraw.Draw(img)

    # Process and external entities
    process = (565, 155, 1035, 685)
    alumni = (80, 145, 300, 275)
    arlo_admin = (80, 595, 300, 725)
    super_admin = (1300, 145, 1520, 275)
    university_admin = (1300, 595, 1520, 725)

    box(draw, process, radius=28, width=3)
    draw.line((s(process[0]), s(225), s(process[2]), s(225)), fill="black", width=s(3))
    centered(draw, "0", 800, 190, FONT_NUM)
    centered(
        draw,
        "CMU ARLO\nWeb-Based Tracer and\nEmployment Analytics System",
        800,
        435,
        FONT_TITLE,
        spacing=10,
    )

    for rect, label in [
        (alumni, "Alumni"),
        (arlo_admin, "ARLO Admin"),
        (super_admin, "Super Admin"),
        (university_admin, "University\nAdministrators"),
    ]:
        box(draw, rect, radius=10, width=3)
        centered(draw, label, (rect[0] + rect[2]) // 2, (rect[1] + rect[3]) // 2, FONT_BOLD)

    # Alumni flows
    arrow(draw, (300, 200), (565, 200))
    wrapped_label(draw, "Registration, profile, tracer and feedback data", 432, 118)
    arrow(draw, (565, 260), (300, 260))
    wrapped_label(draw, "Announcements, notifications and job posts", 432, 335)

    # ARLO Admin flows
    arrow(draw, (300, 625), (565, 625))
    wrapped_label(draw, "Alumni, job, announcement and review updates", 432, 545)
    arrow(draw, (565, 675), (300, 675))
    wrapped_label(draw, "Alumni records, analytics, reports and feedback logs", 432, 770)

    # Super Admin flows
    arrow(draw, (1300, 200), (1035, 200))
    wrapped_label(draw, "Admin account, role and security requests", 1168, 118)
    arrow(draw, (1035, 260), (1300, 260))
    wrapped_label(draw, "Account, role and audit reports", 1168, 335)

    # University administrator flows
    arrow(draw, (1300, 625), (1035, 625))
    wrapped_label(draw, "Monitoring and report requests", 1168, 545)
    arrow(draw, (1035, 675), (1300, 675))
    wrapped_label(draw, "Employment analytics and summary reports", 1168, 770)

    img = img.resize((W, H), Image.Resampling.LANCZOS)
    for out in OUTS:
        out.parent.mkdir(parents=True, exist_ok=True)
        img.save(out, "PNG", optimize=True)
        print(out)


if __name__ == "__main__":
    main()
