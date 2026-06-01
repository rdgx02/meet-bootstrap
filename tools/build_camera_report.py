from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
import html
import math
import zlib


ROOT = Path(__file__).resolve().parents[1]
OUT_DIR = ROOT / "reports"
PDF_OUT = OUT_DIR / "relatorio_cameras_iq_nvr_01_revisado.pdf"
HTML_OUT = OUT_DIR / "relatorio_cameras_iq_nvr_01_revisado.html"

GOOD_TOTAL = 160
BAD_TOTAL = 37
TOTAL = 197

OFFLINE_CAMERAS = [
    ("IQ_Bl_C3_HIB_CAM02", "10.7.46.45", "00:18:85:07:AC:3F"),
    ("IQ_Bl_C1_HIB_CAM03", "10.7.46.151", "00:18:85:07:AB:8E"),
    ("CDE#Bl_C_ala12_Cam03", "10.7.46.130", "00:18:85:07:AC:25"),
    ("CDE#Bl_C_ala11_Cam03", "10.7.46.38", "00:18:85:07:AB:BF"),
    ("CCI#Bl_C_ala32_Cam03", "10.7.46.128", "00:18:85:07:AC:38"),
    ("G.PRI.CAM02", "10.7.254.182", "00:18:85:08:15:FD"),
    ("Guarita_Serviço", "10.7.254.161", "00:18:85:08:14:76"),
    ("Est. PRI. CAM10", "10.7.254.195", "00:18:85:08:16:50"),
    ("IntelBras", "10.7.254.24", "98:E5:5B:81:6E:D5"),
    ("estoque10.7.254.212", "10.7.254.212", "00:18:85:08:16:A0"),
    ("Externa Dir_ALA1_CAM01", "10.7.254.128", "00:18:85:08:16:72"),
    ("estoque10.7.254.191", "10.7.254.25", "00:18:85:08:16:75"),
]


def pdf_escape(text: str) -> str:
    data = text.encode("cp1252", "replace").decode("cp1252")
    return data.replace("\\", "\\\\").replace("(", "\\(").replace(")", "\\)")


def rgb(hex_color: str) -> tuple[float, float, float]:
    value = hex_color.lstrip("#")
    return tuple(int(value[i : i + 2], 16) / 255 for i in (0, 2, 4))


@dataclass
class Page:
    commands: list[str]

    def add(self, command: str) -> None:
        self.commands.append(command)

    def fill(self, color: str) -> None:
        r, g, b = rgb(color)
        self.add(f"{r:.4f} {g:.4f} {b:.4f} rg")

    def stroke(self, color: str) -> None:
        r, g, b = rgb(color)
        self.add(f"{r:.4f} {g:.4f} {b:.4f} RG")

    def rect(self, x: float, y: float, w: float, h: float, fill: str | None = None, stroke: str | None = None, width: float = 0.6) -> None:
        if fill:
            self.fill(fill)
        if stroke:
            self.stroke(stroke)
            self.add(f"{width:.2f} w")
        op = "B" if fill and stroke else "f" if fill else "S"
        self.add(f"{x:.2f} {y:.2f} {w:.2f} {h:.2f} re {op}")

    def line(self, x1: float, y1: float, x2: float, y2: float, color: str = "#CBD5E1", width: float = 0.6) -> None:
        self.stroke(color)
        self.add(f"{width:.2f} w {x1:.2f} {y1:.2f} m {x2:.2f} {y2:.2f} l S")

    def text(self, x: float, y: float, value: str, size: float = 10, font: str = "F1", color: str = "#1F2937") -> None:
        r, g, b = rgb(color)
        self.add(f"BT {r:.4f} {g:.4f} {b:.4f} rg /{font} {size:.2f} Tf 1 0 0 1 {x:.2f} {y:.2f} Tm ({pdf_escape(value)}) Tj ET")

    def wrap_text(self, x: float, y: float, value: str, width: float, size: float = 9.5, leading: float = 13, font: str = "F1", color: str = "#475569") -> float:
        max_chars = max(12, int(width / (size * 0.48)))
        words = value.split()
        line = ""
        for word in words:
            candidate = f"{line} {word}".strip()
            if len(candidate) > max_chars and line:
                self.text(x, y, line, size, font, color)
                y -= leading
                line = word
            else:
                line = candidate
        if line:
            self.text(x, y, line, size, font, color)
            y -= leading
        return y


def header(page: Page, title: str, subtitle: str, page_num: int) -> None:
    page.rect(0, 0, 595.28, 841.89, fill="#F8FAFC")
    page.rect(0, 764, 595.28, 78, fill="#0F172A")
    page.rect(0, 764, 6, 78, fill="#0EA5E9")
    page.text(36, 814, "Relatório técnico | IQ-NVR-01", 8.5, "F1", "#CBD5E1")
    page.text(508, 814, f"Página {page_num} de 2", 8.5, "F1", "#CBD5E1")
    page.text(36, 789, title, 18, "F2", "#FFFFFF")
    page.text(36, 774, subtitle, 9.5, "F1", "#CBD5E1")


def metric_card(page: Page, x: float, y: float, w: float, title: str, value: str, detail: str, accent: str) -> None:
    page.rect(x, y, w, 82, fill="#FFFFFF", stroke="#E2E8F0")
    page.rect(x, y + 78, w, 4, fill=accent)
    page.text(x + 14, y + 58, title, 8.2, "F2", "#475569")
    page.text(x + 14, y + 27, value, 25, "F2", "#0F172A")
    page.text(x + 14, y + 13, detail, 8.4, "F1", "#64748B")


def build_page_one() -> Page:
    page = Page([])
    header(page, "Relatório de Câmeras", "Quantitativo consolidado de câmeras boas e ruins", 1)

    metric_card(page, 36, 658, 166, "CÂMERAS BOAS", "160", "157 online + 3 em estoque", "#16A34A")
    metric_card(page, 214, 658, 166, "CÂMERAS RUINS", "37", "12 offline + 25 em estoque", "#DC2626")
    metric_card(page, 392, 658, 166, "TOTAL MAPEADO", "197", "servidor ACC + estoque", "#0EA5E9")

    page.text(36, 610, "Composição do Quantitativo", 15, "F2", "#0F172A")
    page.text(36, 592, "Resumo por origem e condição", 9, "F1", "#64748B")

    x, y = 36, 454
    widths = [198, 86, 86, 86]
    headers = ["Origem", "Boas", "Ruins", "Total"]
    rows = [
        ("Servidor ACC", "157", "12", "169"),
        ("Estoque físico", "3", "25", "28"),
        ("Total geral", "160", "37", "197"),
    ]
    page.rect(x, y + 96, sum(widths), 28, fill="#0F172A")
    cx = x
    for i, label in enumerate(headers):
        page.text(cx + 10, y + 105, label, 8.5, "F2", "#FFFFFF")
        cx += widths[i]
    for r, row in enumerate(rows):
        row_y = y + 64 - r * 32
        page.rect(x, row_y, sum(widths), 32, fill="#FFFFFF" if r % 2 == 0 else "#F1F5F9")
        cx = x
        for i, value in enumerate(row):
            page.text(cx + 10, row_y + 11, value, 9, "F2" if r == 2 else "F1", "#1E293B")
            cx += widths[i]
    page.rect(x, y, sum(widths), 124, stroke="#E2E8F0")

    page.text(36, 408, "Leitura Operacional", 15, "F2", "#0F172A")
    page.text(36, 390, "O parque monitorado está majoritariamente funcional; as perdas estão concentradas no estoque físico.", 9, "F1", "#64748B")
    bar_x, bar_y, bar_w = 36, 352, 522
    page.rect(bar_x, bar_y, bar_w, 18, fill="#E2E8F0")
    good_w = bar_w * GOOD_TOTAL / TOTAL
    page.rect(bar_x, bar_y, good_w, 18, fill="#16A34A")
    page.rect(bar_x + good_w, bar_y, bar_w - good_w, 18, fill="#DC2626")
    page.text(bar_x, bar_y - 18, "Boas: 160 (81%)", 9, "F2", "#166534")
    page.text(bar_x + bar_w - 82, bar_y - 18, "Ruins: 37 (19%)", 9, "F2", "#991B1B")

    page.rect(36, 216, 250, 96, fill="#FFFFFF", stroke="#E2E8F0")
    page.text(52, 284, "Servidor ACC", 12.5, "F2", "#0F172A")
    page.text(52, 262, "157 câmeras online", 10, "F1", "#334155")
    page.text(52, 244, "12 câmeras offline", 10, "F1", "#334155")
    page.text(52, 224, "Total no servidor: 169 câmeras", 9, "F2", "#475569")

    page.rect(308, 216, 250, 96, fill="#FFFFFF", stroke="#E2E8F0")
    page.text(324, 284, "Estoque físico", 12.5, "F2", "#0F172A")
    page.text(324, 262, "3 câmeras boas", 10, "F1", "#334155")
    page.text(324, 244, "25 câmeras ruins", 10, "F1", "#334155")
    page.text(324, 224, "Total no estoque: 28 câmeras", 9, "F2", "#475569")

    page.line(36, 168, 558, 168)
    page.text(36, 147, "Observação", 10.5, "F2", "#0F172A")
    page.wrap_text(36, 130, "O estoque físico concentra a maior parte das câmeras ruins fora do servidor. A lista nominal da página seguinte considera apenas as câmeras offline identificadas no servidor.", 522, 9, 12)
    page.text(36, 56, "Base: relatório IQ-NVR-01 e conferência física do estoque.", 8.5, "F1", "#64748B")
    return page


def build_page_two() -> Page:
    page = Page([])
    header(page, "Relatório de Câmeras", "Relação nominal das câmeras offline no servidor", 2)
    page.text(36, 724, "Câmeras Offline no Servidor", 15, "F2", "#0F172A")
    page.text(36, 706, "Total identificado no relatório: 12 câmeras", 9, "F1", "#64748B")

    x, y = 36, 338
    widths = [30, 260, 110, 156]
    page.rect(x, y + 336, sum(widths), 28, fill="#0F172A")
    labels = ["#", "Nome da câmera", "IP", "MAC Address"]
    cx = x
    for i, label in enumerate(labels):
        page.text(cx + 9, y + 345, label, 8.2, "F2", "#FFFFFF")
        cx += widths[i]
    for idx, (name, ip, mac) in enumerate(OFFLINE_CAMERAS, 1):
        row_y = y + 308 - (idx - 1) * 28
        page.rect(x, row_y, sum(widths), 28, fill="#FFFFFF" if idx % 2 else "#F1F5F9")
        values = [str(idx), name, ip, mac]
        cx = x
        for col, value in enumerate(values):
            page.text(cx + 9, row_y + 10, value, 8.2, "F2" if col == 0 else "F1", "#1E293B")
            cx += widths[col]
    page.rect(x, y, sum(widths), 364, stroke="#E2E8F0")

    page.rect(36, 118, 522, 74, fill="#FFFFFF", stroke="#E2E8F0")
    page.text(52, 166, "Observação", 10.5, "F2", "#0F172A")
    page.wrap_text(52, 149, "A relação acima contempla somente as câmeras offline identificadas no servidor. O quantitativo de câmeras ruins em estoque foi consolidado na página anterior.", 490, 9, 12)
    page.text(36, 56, "Base: relatório IQ-NVR-01 e conferência física do estoque.", 8.5, "F1", "#64748B")
    return page


def build_pdf(pages: list[Page]) -> bytes:
    objects: list[bytes] = []

    def add_obj(value: bytes) -> int:
        objects.append(value)
        return len(objects)

    font_regular = add_obj(b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>")
    font_bold = add_obj(b"<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>")
    page_obj_ids: list[int] = []
    content_obj_ids: list[int] = []

    for page in pages:
        content = ("\n".join(page.commands) + "\n").encode("cp1252", "replace")
        compressed = zlib.compress(content)
        content_obj_ids.append(add_obj(b"<< /Length %d /Filter /FlateDecode >>\nstream\n" % len(compressed) + compressed + b"\nendstream"))
        page_obj_ids.append(add_obj(b""))

    pages_id = add_obj(b"")
    catalog_id = add_obj(f"<< /Type /Catalog /Pages {pages_id} 0 R >>".encode())

    kids = " ".join(f"{pid} 0 R" for pid in page_obj_ids)
    objects[pages_id - 1] = f"<< /Type /Pages /Kids [{kids}] /Count {len(page_obj_ids)} >>".encode()
    for i, pid in enumerate(page_obj_ids):
        content_id = content_obj_ids[i]
        objects[pid - 1] = (
            f"<< /Type /Page /Parent {pages_id} 0 R /MediaBox [0 0 595.28 841.89] "
            f"/Resources << /Font << /F1 {font_regular} 0 R /F2 {font_bold} 0 R >> >> "
            f"/Contents {content_id} 0 R >>"
        ).encode()

    output = bytearray(b"%PDF-1.4\n%\xe2\xe3\xcf\xd3\n")
    offsets = [0]
    for i, obj in enumerate(objects, 1):
        offsets.append(len(output))
        output.extend(f"{i} 0 obj\n".encode())
        output.extend(obj)
        output.extend(b"\nendobj\n")
    xref = len(output)
    output.extend(f"xref\n0 {len(objects) + 1}\n0000000000 65535 f \n".encode())
    for offset in offsets[1:]:
        output.extend(f"{offset:010d} 00000 n \n".encode())
    output.extend(f"trailer\n<< /Size {len(objects) + 1} /Root {catalog_id} 0 R >>\nstartxref\n{xref}\n%%EOF\n".encode())
    return bytes(output)


def build_html() -> str:
    rows = "\n".join(
        f"<tr><td>{i}</td><td>{html.escape(name)}</td><td>{ip}</td><td>{mac}</td></tr>"
        for i, (name, ip, mac) in enumerate(OFFLINE_CAMERAS, 1)
    )
    good_pct = math.floor(GOOD_TOTAL / TOTAL * 100)
    return f"""<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>Relatório de Câmeras - IQ-NVR-01</title>
<style>
@page {{ size: A4; margin: 0; }}
* {{ box-sizing: border-box; }}
body {{ margin: 0; background: #e5e7eb; color: #1e293b; font-family: Arial, Helvetica, sans-serif; }}
.page {{ width: 210mm; min-height: 297mm; margin: 16px auto; background: #f8fafc; padding-bottom: 22mm; position: relative; }}
header {{ height: 78px; background: #0f172a; color: white; border-left: 6px solid #0ea5e9; padding: 18px 36px; }}
.meta {{ color: #cbd5e1; font-size: 11px; display: flex; justify-content: space-between; }}
h1 {{ margin: 10px 0 2px; font-size: 24px; letter-spacing: 0; }}
.sub {{ color: #cbd5e1; font-size: 12px; }}
main {{ padding: 34px 36px 0; }}
.metrics {{ display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }}
.metric {{ background: white; border: 1px solid #e2e8f0; padding: 16px; border-top: 4px solid var(--accent); }}
.metric small {{ color: #64748b; font-weight: 700; font-size: 10px; }}
.metric strong {{ display: block; color: #0f172a; font-size: 34px; margin-top: 8px; }}
.metric span {{ color: #64748b; font-size: 11px; }}
h2 {{ margin: 34px 0 4px; color: #0f172a; font-size: 19px; }}
p.lead {{ margin: 0 0 14px; color: #64748b; font-size: 12px; }}
table {{ width: 100%; border-collapse: collapse; background: white; border: 1px solid #e2e8f0; font-size: 12px; }}
th {{ background: #0f172a; color: white; text-align: left; padding: 10px; }}
td {{ padding: 10px; border-top: 1px solid #e2e8f0; }}
tr:nth-child(even) td {{ background: #f1f5f9; }}
.bar {{ height: 18px; background: #dc2626; margin: 18px 0 6px; }}
.bar > div {{ height: 100%; width: {good_pct}%; background: #16a34a; }}
.bar-labels {{ display: flex; justify-content: space-between; font-weight: 700; font-size: 12px; }}
.two {{ display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; margin-top: 34px; }}
.panel {{ background: white; border: 1px solid #e2e8f0; padding: 18px; }}
.panel h3 {{ margin: 0 0 12px; font-size: 16px; color: #0f172a; }}
.panel p {{ margin: 7px 0; font-size: 13px; }}
.note {{ margin-top: 28px; border-top: 1px solid #cbd5e1; padding-top: 16px; font-size: 12px; color: #475569; }}
footer {{ position: absolute; left: 36px; right: 36px; bottom: 22px; color: #64748b; font-size: 11px; }}
@media print {{ body {{ background: white; }} .page {{ margin: 0; page-break-after: always; }} }}
</style>
</head>
<body>
<section class="page">
<header><div class="meta"><span>Relatório técnico | IQ-NVR-01</span><span>Página 1 de 2</span></div><h1>Relatório de Câmeras</h1><div class="sub">Quantitativo consolidado de câmeras boas e ruins</div></header>
<main>
<div class="metrics">
<div class="metric" style="--accent:#16a34a"><small>CÂMERAS BOAS</small><strong>160</strong><span>157 online + 3 em estoque</span></div>
<div class="metric" style="--accent:#dc2626"><small>CÂMERAS RUINS</small><strong>37</strong><span>12 offline + 25 em estoque</span></div>
<div class="metric" style="--accent:#0ea5e9"><small>TOTAL MAPEADO</small><strong>197</strong><span>servidor ACC + estoque</span></div>
</div>
<h2>Composição do Quantitativo</h2><p class="lead">Resumo por origem e condição</p>
<table><thead><tr><th>Origem</th><th>Boas</th><th>Ruins</th><th>Total</th></tr></thead><tbody><tr><td>Servidor ACC</td><td>157</td><td>12</td><td>169</td></tr><tr><td>Estoque físico</td><td>3</td><td>25</td><td>28</td></tr><tr><td><strong>Total geral</strong></td><td><strong>160</strong></td><td><strong>37</strong></td><td><strong>197</strong></td></tr></tbody></table>
<h2>Leitura Operacional</h2><p class="lead">O parque monitorado está majoritariamente funcional; as perdas estão concentradas no estoque físico.</p>
<div class="bar"><div></div></div><div class="bar-labels"><span style="color:#166534">Boas: 160 (81%)</span><span style="color:#991b1b">Ruins: 37 (19%)</span></div>
<div class="two"><div class="panel"><h3>Servidor ACC</h3><p>157 câmeras online</p><p>12 câmeras offline</p><p><strong>Total no servidor: 169 câmeras</strong></p></div><div class="panel"><h3>Estoque físico</h3><p>3 câmeras boas</p><p>25 câmeras ruins</p><p><strong>Total no estoque: 28 câmeras</strong></p></div></div>
<div class="note"><strong>Observação</strong><br>O estoque físico concentra a maior parte das câmeras ruins fora do servidor. A lista nominal da página seguinte considera apenas as câmeras offline identificadas no servidor.</div>
</main><footer>Base: relatório IQ-NVR-01 e conferência física do estoque.</footer>
</section>
<section class="page">
<header><div class="meta"><span>Relatório técnico | IQ-NVR-01</span><span>Página 2 de 2</span></div><h1>Relatório de Câmeras</h1><div class="sub">Relação nominal das câmeras offline no servidor</div></header>
<main>
<h2 style="margin-top:0">Câmeras Offline no Servidor</h2><p class="lead">Total identificado no relatório: 12 câmeras</p>
<table><thead><tr><th style="width:38px">#</th><th>Nome da câmera</th><th>IP</th><th>MAC Address</th></tr></thead><tbody>{rows}</tbody></table>
<div class="note"><strong>Observação</strong><br>A relação acima contempla somente as câmeras offline identificadas no servidor. O quantitativo de câmeras ruins em estoque foi consolidado na página anterior.</div>
</main><footer>Base: relatório IQ-NVR-01 e conferência física do estoque.</footer>
</section>
</body>
</html>"""


def main() -> None:
    OUT_DIR.mkdir(exist_ok=True)
    PDF_OUT.write_bytes(build_pdf([build_page_one(), build_page_two()]))
    HTML_OUT.write_text(build_html(), encoding="utf-8")
    print(PDF_OUT)
    print(HTML_OUT)


if __name__ == "__main__":
    main()
