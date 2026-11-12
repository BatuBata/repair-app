"""T006 fixture checks; requires the already installed pypdf, no app boot or DB."""
import hashlib
import json
import re
import time
from pathlib import Path
from pypdf import PdfReader

started = time.monotonic()
out = Path(__file__).resolve().parents[2] / 'docs/evidence/T006'
fixture = json.loads((out / 'public-fixture.json').read_text())
render = json.loads((out / 'render-check.json').read_text())
expected_url = 'https://repair.example/t/SYNTHETIC_T006_0123456789abcdef0123456789abcdef'
assert fixture['tracking_url'] == expected_url
assert sum(row['qty'] * row['sell'] for row in fixture['lines']) == fixture['total'] == 1130000

def compact(text):
    return re.sub(r'\s+', '', text)

def money(value):
    return f'{value:,}'.replace(',', '.')

results = []
for name, width, height, pages in [('tts-a5', 148, 210, 1), ('tts-58mm', 58, 280, 1), ('nota-a5', 148, 210, 2), ('nota-58mm', 58, 400, 1)]:
    pdf_path = out / f'{name}.pdf'
    reader = PdfReader(pdf_path)
    assert len(reader.pages) == pages, name
    for page in reader.pages:
        assert abs(float(page.mediabox.width) * 25.4 / 72 - width) < 0.01
        assert abs(float(page.mediabox.height) * 25.4 / 72 - height) < 0.01
        assert page.extract_text().strip(), f'{name}: blank page'
    text = compact(' '.join(page.extract_text() for page in reader.pages))
    html = (out / f'{name}.html').read_text()
    for key in ['number', 'customer', 'phone', 'item', 'complaint', 'received']:
        assert compact(fixture[key]) in text, (name, key)
    for private in ['734567', '734.567', '23456', '23.456', '4567', '4.567', 'modal', 'INTERNAL_COST_SENTINEL']:
        assert private not in text and private not in html, (name, private)
    if name.startswith('nota'):
        for key in ['issued', 'technician']:
            assert compact(fixture[key]) in text, (name, key)
        for row in fixture['lines']:
            assert compact(row['description']) in text, (name, row['description'])
            assert money(row['sell']) in text and money(row['qty'] * row['sell']) in text
        assert 'Rp1.130.000' in text
        assert 'Garansi30hari' in text and '12Desember2026' in text
        assert 'tidakberlakuuntukkerusakankarenajatuhatauterkenaair.' in text
    else:
        assert 'Simpanstrukiniuntukpengambilanbarang.' in text
        assert 'Rp' not in text and '1.000.000' not in text
    checks = [item for item in render['results'] if item['document'] == name]
    assert len(checks) == pages
    assert [url for page in checks for url in page['decoded']] == [expected_url]
    results.append({'document': name, 'pages': pages, 'size_mm': [width, height], 'content': 'PASS', 'private_cost_absent': 'PASS', 'rendered_qr': 'PASS', 'sha256': hashlib.sha256(pdf_path.read_bytes()).hexdigest()})
report = {'verdict': 'PASS', 'scope': 'synthetic discovery fixtures only', 'duration_seconds': round(time.monotonic() - started, 3), 'results': results}
(out / 'self-check.json').write_text(json.dumps(report, indent=2) + '\n')
print(json.dumps(report, indent=2))
