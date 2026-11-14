<?php

require __DIR__.'/../../../../vendor/autoload.php';
$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$html = Illuminate\Support\Facades\Blade::render(<<<'BLADE'
<x-ui.field name="text" label="Nama" />
<x-ui.field name="phone" label="Telepon" type="tel" />
<x-ui.field name="password" label="Password baru" type="password" />
<x-ui.field name="number" label="Default hari garansi" type="number" />
<x-ui.field name="zero" label="Teks" placeholder="0" />
<x-ui.field name="filled" label="Nama" value="Budi" />
<x-ui.field name="date" label="Tanggal" type="date" />
<x-ui.field name="file" label="Logo" type="file" />
<x-ui.money name="money" label="Nominal" />
<textarea name="textarea" placeholder="Masukkan alamat toko"></textarea>
BLADE);

if (in_array('--html', $argv, true)) {
    echo $html;
    exit;
}

$doc = new DOMDocument;
@$doc->loadHTML($html);
$xpath = new DOMXPath($doc);
$expected = ['text' => 'Masukkan nama', 'phone' => '08xxxxxxxxxx', 'password' => 'Minimal 9 karakter', 'number' => '0', 'zero' => '0', 'filled' => 'Masukkan nama', 'date' => '', 'file' => ''];
$results = [];
foreach ($expected as $name => $placeholder) {
    $input = $xpath->query('//input[@name="'.$name.'"]')->item(0);
    $actual = $input->getAttribute('placeholder');
    $results[$name] = ['expected' => $placeholder, 'actual' => $actual, 'value' => $input->getAttribute('value'), 'pass' => $actual === $placeholder];
}
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit(count(array_filter($results, fn ($case) => !$case['pass'])) ? 1 : 0);
