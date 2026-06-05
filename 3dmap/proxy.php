<?php
/**
 * PZ Map3D – proxy pro ČÚZK DMR 5G (Digitální model reliéfu 5. generace)
 *
 * Stahuje LERC raster (Float32, 2 m grid) z veřejného ArcGIS ImageServer
 * ČÚZK a vrací jej do prohlížeče s povoleným CORS hlavičkou.
 *
 * Použití v JS:
 *   fetch('proxy.php?bbox=14.40,50.08,14.42,50.09&size=512,512')
 *     .then(r => r.arrayBuffer())
 *     .then(buf => Lerc.decode(buf));   // → pixels: Float32Array
 *
 * Forpsi: vyžaduje pouze curl_exec (zapnuto), nevyžaduje shell_exec.
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: *');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

// ----- validace vstupů (jen číslice, čárky, tečky, mínus) -----
$bbox = $_GET['bbox'] ?? '';
$size = $_GET['size'] ?? '512,512';
$format = strtolower($_GET['format'] ?? 'lerc');   // lerc | tiff | png

if (!preg_match('/^-?\d+(\.\d+)?(,-?\d+(\.\d+)?){3}$/', $bbox)) {
    http_response_code(400); exit('bad bbox');
}
if (!preg_match('/^\d{1,4},\d{1,4}$/', $size)) {
    http_response_code(400); exit('bad size');
}
[$w, $h] = array_map('intval', explode(',', $size));
if ($w < 16 || $h < 16 || $w > 2048 || $h > 2048) {
    http_response_code(400); exit('size out of range');
}
if (!in_array($format, ['lerc', 'tiff', 'png'], true)) {
    http_response_code(400); exit('bad format');
}

// ----- sestavení dotazu na ČÚZK ImageServer -----
$base = 'https://ags.cuzk.gov.cz/arcgis2/rest/services/dmr5g/ImageServer/exportImage';
$params = [
    'bbox'          => $bbox,
    'bboxSR'        => '4326',         // WGS84 vstup (jednoduché pro JS)
    'imageSR'       => '4326',         // ponecháme v lat/lon
    'size'          => $size,
    'format'        => $format,
    'pixelType'     => 'F32',
    'interpolation' => 'RSP_BilinearInterpolation',
    'noData'        => '',
    'f'             => 'image',
];
$url = $base . '?' . http_build_query($params);

// ----- curl -----
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_USERAGENT      => 'PZ-Map3D/1.0 (+petrzavorka.cz)',
    CURLOPT_SSL_VERIFYPEER => true,
]);
$data = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err  = curl_error($ch);
curl_close($ch);

if ($code !== 200 || $data === false || strlen($data) < 32) {
    http_response_code(502);
    header('Content-Type: text/plain; charset=utf-8');
    echo "ČÚZK upstream selhal\nHTTP {$code}\n{$err}\nURL: {$url}";
    exit;
}

// ČÚZK občas vrátí JSON s chybou i s HTTP 200 – detekujeme to
if ($ctype && stripos($ctype, 'json') !== false) {
    http_response_code(502);
    header('Content-Type: application/json; charset=utf-8');
    echo $data;
    exit;
}

// ----- výstup -----
$mime = $ctype ?: match($format) {
    'lerc' => 'application/octet-stream',
    'tiff' => 'image/tiff',
    'png'  => 'image/png',
};
header('Content-Type: ' . $mime);
header('Content-Length: ' . strlen($data));
header('Cache-Control: public, max-age=3600');
echo $data;
