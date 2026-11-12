<?php
// T006 discovery only; no Laravel boot, real customer data, or product entry point.
declare(strict_types=1);

require __DIR__.'/../../vendor/autoload.php';

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;

$started = microtime(true);
$out = __DIR__.'/../../docs/evidence/T006';
$token = 'SYNTHETIC_T006_0123456789abcdef0123456789abcdef';
$url = 'https://repair.example/t/'.$token;
$fixture = [
    'number' => 'TTS-2611-006',
    'customer' => 'Pelanggan Contoh T006',
    'phone' => '0800-0000-0000 (contoh)',
    'item' => 'Laptop Asus X441, adaptor dan tas kain abu-abu disertakan',
    'complaint' => 'Layar bergaris setelah menyala sekitar tiga puluh menit; gambar kadang hilang ketika engsel dibuka. Mohon periksa konektor dan uji kembali dengan adaptor bawaan.',
    'technician' => 'Teknisi Contoh',
    'received' => '10-11-2026 10:42 WITA',
    'issued' => '12-11-2026 16:30 WITA',
    'lines' => [
        ['description' => 'LCD 14 inci HD pengganti; pemeriksaan konektor dan pemasangan pada bingkai asli', 'qty' => 1, 'sell' => 1000000, 'cost' => 734567],
        ['description' => "Jasa pemasangan LCD\nUji gambar dan engsel setelah pemasangan", 'qty' => 1, 'sell' => 100000, 'cost' => 23456],
        ['description' => 'Bantalan pelindung kabel engsel kiri dan kanan', 'qty' => 2, 'sell' => 15000, 'cost' => 4567],
    ],
    'internal_note' => 'INTERNAL_COST_SENTINEL',
];
// Explicit customer projection: internal prices and notes never enter the renderer.
$public = array_intersect_key($fixture, array_flip(['number', 'customer', 'phone', 'item', 'complaint', 'technician', 'received', 'issued']));
$public['lines'] = array_map(fn ($line) => array_intersect_key($line, array_flip(['description', 'qty', 'sell'])), $fixture['lines']);
$public['tracking_url'] = $url;
$public['total'] = array_sum(array_map(fn ($line) => $line['qty'] * $line['sell'], $public['lines']));
assert($public['total'] === 1130000);
assert(!str_contains(json_encode($public), 'cost'));
assert(!str_contains(json_encode($public), $fixture['internal_note']));
assert($url === 'https://repair.example/t/'.$token);
file_put_contents($out.'/public-fixture.json', json_encode($public, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

$svg = (new Writer(new ImageRenderer(new RendererStyle(256, 4), new SvgImageBackEnd())))->writeString($url);
file_put_contents($out.'/tracking.svg', $svg);
$qr = 'data:image/svg+xml;base64,'.base64_encode($svg);
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(int $value): string { return number_format($value, 0, ',', '.'); }
$manifest = ['tracking_url' => $url, 'documents' => []];
foreach (['tts', 'nota'] as $kind) {
    foreach (['a5', '58mm'] as $format) {
        $thermal = $format === '58mm';
        $invoice = $kind === 'nota';
        // shortcut: fixed sample roll length, T017 must size or paginate real unbounded content.
        [$width, $height] = $thermal ? [58, $invoice ? 400 : 280] : [148, 210];
        $title = $invoice ? 'Nota Servis' : 'Tanda Terima Servis';
        $html = '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'.h($title).' - contoh T006</title><style>';
        $html .= '@page { size: '.$width.'mm '.$height.'mm; margin: '.($thermal ? '4mm 5mm 6mm' : '12mm').'; }
body { margin: 0; color: #131416; background: white; font-family: "DejaVu Sans", sans-serif; font-size: '.($thermal ? '9pt' : '9pt').'; line-height: '.($thermal ? '1.35' : '1.25').'; }
p { margin: 0 0 6pt; } .sample { text-align: center; font-size: 7pt; letter-spacing: .3pt; margin-bottom: 8pt; }
.head { border-bottom: 1pt '.($thermal ? 'dashed' : 'solid').' #131416; padding-bottom: 8pt; margin-bottom: 8pt; }
.shop { font-size: '.($thermal ? '11pt' : '12pt').'; font-weight: bold; } .muted, .label { color: #5d5f60; font-size: 8pt; }
.title { font-weight: bold; font-size: '.($thermal ? '10pt' : '12pt').'; } .id { font-family: "DejaVu Sans Mono", monospace; font-size: '.($thermal ? '10pt' : '13pt').'; }
.center { text-align: center; } .right { text-align: right; } table { width: 100%; border-collapse: collapse; table-layout: fixed; } td, th { vertical-align: top; padding: 4pt 0; }
th { font-size: 8pt; color: #5d5f60; text-align: left; border-bottom: .5pt solid #e3e7e9; } .items td { border-bottom: .5pt solid #eef1f2; } .desc { padding-right: 6pt; }
.meta { margin-bottom: 8pt; } .meta td { padding: 2pt 6pt 2pt 0; } .total { font-weight: bold; font-size: 11pt; margin: 10pt 0; }
.warranty { background: #f5f7f8; padding: 7pt; margin: 10pt 0; font-size: 8.5pt; } .foot { margin-top: 10pt; font-size: 8pt; }
.qr { display: block; width: '.($thermal ? '26mm' : '22mm').'; height: '.($thermal ? '26mm' : '22mm').'; margin: 5pt auto; }
.line { border-bottom: .5pt dashed #131416; padding: 6pt 0; } .amounts td { padding: 0; font-size: 8.5pt; } .small { font-size: 8pt; }
@media screen { body { width: '.($width - ($thermal ? 10 : 24)).'mm; margin: 12px auto; padding: '.($thermal ? '4mm 5mm 6mm' : '12mm').'; border: 1px solid #e3e7e9; } html { background: #f2f5f6; } }
</style></head><body><p class="sample">CONTOH SINTETIS T006</p>';
        $shop = '<div class="shop">ANUGERAH</div><div class="muted">Servis Elektronik<br>Jl. Contoh No. 12<br>0800-0000-0000 (contoh)</div>';
        $heading = '<div class="title">'.h($title).'</div><div class="id">'.h($public['number']).'</div>';
        $html .= $thermal ? '<div class="head center">'.$shop.'<br>'.$heading.'</div>' : '<table class="head"><tr><td>'.$shop.'</td><td class="right">'.$heading.'</td></tr></table>';
        $meta = ['Customer' => $public['customer'], 'HP' => $public['phone'], 'Barang' => $public['item'], 'Tanggal terima' => $public['received'], 'Keluhan' => $public['complaint']];
        if ($invoice) $meta += ['Tanggal nota' => $public['issued'], 'Teknisi' => $public['technician']];
        $html .= '<div class="meta">';
        if ($thermal) {
            foreach ($meta as $label => $value) $html .= '<p><span class="label">'.h($label).'</span><br>'.h($value).'</p>';
        } else {
            $html .= '<table>';
            $complaint = $meta['Keluhan'];
            unset($meta['Keluhan']);
            foreach (array_chunk($meta, 2, true) as $pair) {
                $html .= '<tr>';
                foreach ($pair as $label => $value) $html .= '<td style="width:50%"><span class="label">'.h($label).'</span><br>'.h($value).'</td>';
                $html .= '</tr>';
            }
            $html .= '<tr><td colspan="2"><span class="label">Keluhan</span><br>'.h($complaint).'</td></tr></table>';
        }
        $html .= '</div>';
        if ($invoice) {
            if (!$thermal) $html .= '<table class="items"><thead><tr><th style="width:48%">Pekerjaan</th><th class="right" style="width:8%">Qty</th><th class="right" style="width:22%">Harga</th><th class="right" style="width:22%">Subtotal</th></tr></thead><tbody>';
            foreach ($public['lines'] as $line) {
                $description = nl2br(h($line['description']));
                $subtotal = $line['qty'] * $line['sell'];
                $html .= $thermal ? '<div class="line">'.$description.'<table class="amounts"><tr><td>'.$line['qty'].' x '.money($line['sell']).'</td><td class="right">'.money($subtotal).'</td></tr></table></div>' : '<tr><td class="desc">'.$description.'</td><td class="right">'.$line['qty'].'</td><td class="right">'.money($line['sell']).'</td><td class="right">'.money($subtotal).'</td></tr>';
            }
            if (!$thermal) $html .= '</tbody></table>';
            $html .= '<table class="total"><tr><td>Total</td><td class="right">Rp'.money($public['total']).'</td></tr></table><p class="warranty"><strong>Garansi 30 hari</strong><br>Berlaku sampai 12 Desember 2026. Berlaku untuk kerusakan yang sama; tidak berlaku untuk kerusakan karena jatuh atau terkena air.</p>';
        }
        $html .= '<div class="foot center"><img class="qr" src="'.$qr.'" alt="QR tracking contoh"><p>Pindai untuk cek status servis'.($invoice ? ' dan unduh nota' : '').'.</p><p>'.($invoice ? 'Terima kasih atas kepercayaan Anda.' : 'Simpan struk ini untuk pengambilan barang.').'</p><p class="label">CONTOH - bukan transaksi nyata</p></div></body></html>';
        if (!$thermal) {
            $html = str_replace('<div class="foot center"><img class="qr" src="'.$qr.'" alt="QR tracking contoh">', '<div class="foot"><table><tr><td style="width:75%">', $html);
            $html = str_replace('</p></div></body>', '</p></td><td><img class="qr" src="'.$qr.'" alt="QR tracking contoh"></td></tr></table></div></body>', $html);
        }
        $name = $kind.'-'.$format;
        file_put_contents($out.'/'.$name.'.html', $html);
        $options = new Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'defaultFont' => 'DejaVu Sans', 'chroot' => [realpath(__DIR__.'/../../vendor/dompdf/dompdf')], 'defaultMediaType' => 'print']);
        $pdf = new Dompdf($options);
        $pdf->setPaper([0, 0, $width * 72 / 25.4, $height * 72 / 25.4]);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->render();
        $expectedPages = $invoice && !$thermal ? 2 : 1;
        assert($pdf->getCanvas()->get_page_count() === $expectedPages, $name.' unexpected page count');
        file_put_contents($out.'/'.$name.'.pdf', $pdf->output());
        $manifest['documents'][$name] = ['width_mm' => $width, 'height_mm' => $height, 'pages' => $pdf->getCanvas()->get_page_count()];
    }
}
$manifest['duration_seconds'] = round(microtime(true) - $started, 3);
file_put_contents($out.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
