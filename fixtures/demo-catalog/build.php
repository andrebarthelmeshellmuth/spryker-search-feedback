<?php

declare(strict_types = 1);

/**
 * One-off generator: builds the fictional "Feldwerk" demo-catalog fixture CSVs (product_abstract,
 * product_concrete, product_price DE, product_stock, product_image) from the product list below, plus
 * the flat-SVG demo images (data URIs, no external hosting). Output goes to ./out/ next to this script.
 * Run once with `php build.php`, then copy ./out/*.csv into a package's fixtures/demo-catalog/ dir.
 */

$scriptDir = __DIR__;
$outDir = $scriptDir . '/out';

if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}

function svgDataUri(string $svg): string
{
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

$chairTemplate = file_get_contents($scriptDir . '/chair-template.svg');
$trolleySvg = file_get_contents($scriptDir . '/trolley.svg');
$shredderSvg = file_get_contents($scriptDir . '/shredder.svg');

function chairSvg(string $template, string $color): string
{
    return str_replace('__COLOR__', $color, $template);
}

// category keys from data/import/common/common/category.csv
const CATEGORY_CHAIRS = '1002577-cer';
const CATEGORY_SACK_TRUCKS = '1002745-cer';
const CATEGORY_SHREDDER = '1002666-cer';

$products = [
    [
        'sku' => 'DEMO-CHR-001',
        'name_en' => 'Feldwerk stacking chair, 4-leg frame - anthracite',
        'name_de' => 'Feldwerk Stapelstuhl, 4-Fuß-Gestell - anthrazit',
        'desc_en' => 'Feldwerk stacking chair with a powder-coated 4-leg steel frame and anthracite shell. Stackable up to 8 chairs high for compact storage between conference sessions.',
        'desc_de' => 'Feldwerk Stapelstuhl mit pulverbeschichtetem 4-Fuß-Stahlgestell und anthrazitfarbener Schale. Bis zu 8 Stühle stapelbar für platzsparende Lagerung zwischen Konferenzen.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 11482,
        'image' => chairSvg($chairTemplate, '#3d3d3d'),
    ],
    [
        'sku' => 'DEMO-CHR-002',
        'name_en' => 'Feldwerk stacking chair, upholstered seat - blue',
        'name_de' => 'Feldwerk Stapelstuhl, gepolsterter Sitz - blau',
        'desc_en' => 'Feldwerk stacking chair with an upholstered seat pad in blue fabric, cantilever-style comfort on a stackable frame.',
        'desc_de' => 'Feldwerk Stapelstuhl mit gepolstertem Sitzkissen aus blauem Stoff, Freischwinger-Komfort auf stapelbarem Gestell.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 12185,
        'image' => chairSvg($chairTemplate, '#2f6fb0'),
    ],
    [
        'sku' => 'DEMO-CHR-003',
        'name_en' => 'Feldwerk conference chair, cantilever frame - graphite',
        'name_de' => 'Feldwerk Konferenzstuhl, Freischwinger-Gestell - graphit',
        'desc_en' => 'Feldwerk conference chair on a cantilever frame with a graphite-grey shell, designed for long meetings without a swivel base.',
        'desc_de' => 'Feldwerk Konferenzstuhl auf Freischwinger-Gestell mit graphitgrauer Schale, ausgelegt für lange Besprechungen ohne Drehfuß.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 28767,
        'image' => chairSvg($chairTemplate, '#555555'),
    ],
    [
        'sku' => 'DEMO-CHR-004',
        'name_en' => 'Feldwerk ergonomic desk chair, adjustable lumbar support - black',
        'name_de' => 'Feldwerk ergonomischer Schreibtischstuhl, verstellbare Lordosenstütze - schwarz',
        'desc_en' => 'Feldwerk ergonomic desk chair with adjustable lumbar support, seat depth and armrests. Built for full-day desk work.',
        'desc_de' => 'Feldwerk ergonomischer Schreibtischstuhl mit verstellbarer Lordosenstütze, Sitztiefe und Armlehnen. Für ganztägige Schreibtischarbeit ausgelegt.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 31506,
        'image' => chairSvg($chairTemplate, '#1c1c1c'),
    ],
    [
        'sku' => 'DEMO-CHR-005',
        'name_en' => 'Feldwerk ergonomic desk chair, mesh back - grey',
        'name_de' => 'Feldwerk ergonomischer Schreibtischstuhl, Netzrücken - grau',
        'desc_en' => 'Feldwerk ergonomic desk chair with a breathable mesh backrest and synchronous tilt mechanism.',
        'desc_de' => 'Feldwerk ergonomischer Schreibtischstuhl mit atmungsaktivem Netzrücken und Synchronmechanik.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 45024,
        'image' => chairSvg($chairTemplate, '#8a8a8a'),
    ],
    [
        'sku' => 'DEMO-CHR-006',
        'name_en' => 'Feldwerk executive swivel chair, leather-look - black',
        'name_de' => 'Feldwerk Chefsessel, Leder-Optik - schwarz',
        'desc_en' => 'Feldwerk executive swivel chair upholstered in black leather-look material, with a high backrest and padded armrests.',
        'desc_de' => 'Feldwerk Chefsessel in schwarzer Leder-Optik, mit hoher Rückenlehne und gepolsterten Armlehnen.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 33513,
        'image' => chairSvg($chairTemplate, '#262626'),
    ],
    [
        'sku' => 'DEMO-CHR-007',
        'name_en' => 'Feldwerk visitor chair, stackable, upholstered - red',
        'name_de' => 'Feldwerk Besucherstuhl, stapelbar, gepolstert - rot',
        'desc_en' => 'Feldwerk visitor chair with an upholstered red seat and backrest, stackable frame for flexible seating in reception areas.',
        'desc_de' => 'Feldwerk Besucherstuhl mit gepolstertem rotem Sitz und Rückenlehne, stapelbares Gestell für flexible Bestuhlung im Empfangsbereich.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 17093,
        'image' => chairSvg($chairTemplate, '#c0392b'),
    ],
    [
        'sku' => 'DEMO-CHR-008',
        'name_en' => 'Feldwerk wood shell chair, beech veneer - natural',
        'name_de' => 'Feldwerk Holzschalenstuhl, Buche furniert - natur',
        'desc_en' => 'Feldwerk shell chair in natural beech veneer, a lightweight one-piece seat shell on a slim frame.',
        'desc_de' => 'Feldwerk Schalenstuhl aus natur furnierter Buche, eine leichte einteilige Sitzschale auf schlankem Gestell.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 23834,
        'image' => chairSvg($chairTemplate, '#c9995c'),
    ],
    [
        'sku' => 'DEMO-CHR-009',
        'name_en' => 'Feldwerk standard swivel chair, without armrests - grey',
        'name_de' => 'Feldwerk Standard-Drehstuhl, ohne Armlehnen - grau',
        'desc_en' => 'Feldwerk entry-level swivel chair without armrests, height-adjustable gas lift, grey fabric seat.',
        'desc_de' => 'Feldwerk Einstiegs-Drehstuhl ohne Armlehnen, höhenverstellbare Gasfeder, grauer Stoffsitz.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 9785,
        'image' => chairSvg($chairTemplate, '#a0a0a0'),
    ],
    [
        'sku' => 'DEMO-CHR-010',
        'name_en' => 'Feldwerk plastic shell chair, chrome frame - white',
        'name_de' => 'Feldwerk Kunststoffschalenstuhl, Chromgestell - weiß',
        'desc_en' => 'Feldwerk plastic shell chair in white on a chrome sled frame, easy to wipe down and stack.',
        'desc_de' => 'Feldwerk Kunststoffschalenstuhl in weiß auf Chrom-Kufengestell, leicht abwischbar und stapelbar.',
        'category' => CATEGORY_CHAIRS,
        'price_gross' => 11641,
        'image' => chairSvg($chairTemplate, '#e8e8e8'),
    ],
    [
        'sku' => 'DEMO-WHS-001',
        'name_en' => 'Feldwerk folding hand trolley - load capacity 100 kg',
        'name_de' => 'Feldwerk klappbare Sackkarre - Tragfähigkeit 100 kg',
        'desc_en' => 'Feldwerk folding hand trolley with a load capacity of 100 kg, puncture-proof wheels and a folding platform for compact storage.',
        'desc_de' => 'Feldwerk klappbare Sackkarre mit einer Tragfähigkeit von 100 kg, pannensicheren Rädern und klappbarer Ladefläche für platzsparende Lagerung.',
        'category' => CATEGORY_SACK_TRUCKS,
        'price_gross' => 8990,
        'image' => $trolleySvg,
    ],
    [
        'sku' => 'DEMO-OFF-001',
        'name_en' => 'Feldwerk desktop paper shredder - multi-function button, auto-reverse',
        'name_de' => 'Feldwerk Tisch-Aktenvernichter - Multifunktionstaste, Auto-Reversierung',
        'desc_en' => 'Feldwerk desktop paper shredder with a multi-function button, automatic reversing in case of paper jam, and an energy-saving switch-off mode. Two minutes after use the device switches into standby; after one hour it switches off completely.',
        'desc_de' => 'Feldwerk Tisch-Aktenvernichter mit Multifunktionstaste, automatischer Reversierung bei Papierstau und energiesparendem Abschaltmodus. Zwei Minuten nach Gebrauch schaltet das Gerät in den Standby, nach einer Stunde schaltet es komplett aus.',
        'category' => CATEGORY_SHREDDER,
        'price_gross' => 14990,
        'image' => $shredderSvg,
    ],
];

function slug(string $s): string
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);

    return trim($s, '-');
}

// --- product_abstract.csv (57 columns, matches data/import/common/common/product_abstract.csv header) ---
$abstractHeader = [
    'category_key', 'category_product_order', 'abstract_sku', 'tax_set_name', 'name.de_DE', 'name.en_US',
    'description.de_DE', 'description.en_US', 'url.de_DE', 'url.en_US', 'meta_title.de_DE', 'meta_title.en_US',
    'meta_keywords.de_DE', 'meta_keywords.en_US', 'meta_description.de_DE', 'meta_description.en_US',
    'attribute_key_1', 'value_1', 'attribute_key_1.de_DE', 'value_1.de_DE', 'attribute_key_1.en_US', 'value_1.en_US',
    'attribute_key_2', 'value_2', 'attribute_key_2.de_DE', 'value_2.de_DE', 'attribute_key_2.en_US', 'value_2.en_US',
    'attribute_key_3', 'value_3', 'attribute_key_3.de_DE', 'value_3.de_DE', 'attribute_key_3.en_US', 'value_3.en_US',
    'attribute_key_4', 'value_4', 'attribute_key_4.de_DE', 'value_4.de_DE', 'attribute_key_4.en_US', 'value_4.en_US',
    'attribute_key_5', 'value_5', 'attribute_key_5.de_DE', 'value_5.de_DE', 'attribute_key_5.en_US', 'value_5.en_US',
    'attribute_key_6', 'value_6', 'attribute_key_6.de_DE', 'value_6.de_DE', 'attribute_key_6.en_US', 'value_6.en_US',
    'color_code', 'new_from', 'new_to',
    'attribute_key_7', 'value_7', 'attribute_key_7.de_DE', 'value_7.de_DE', 'attribute_key_7.en_US', 'value_7.en_US',
    'attribute_key_8', 'value_8', 'attribute_key_8.de_DE', 'value_8.de_DE', 'attribute_key_8.en_US', 'value_8.en_US',
];

$abstractRows = [];

foreach ($products as $p) {
    $row = array_fill_keys($abstractHeader, '');
    $row['category_key'] = $p['category'];
    $row['category_product_order'] = '0';
    $row['abstract_sku'] = $p['sku'];
    $row['tax_set_name'] = 'Standard Taxes';
    $row['name.de_DE'] = $p['name_de'];
    $row['name.en_US'] = $p['name_en'];
    $row['description.de_DE'] = $p['desc_de'];
    $row['description.en_US'] = $p['desc_en'];
    $row['url.de_DE'] = '/de/' . slug($p['name_de']) . '-' . strtolower($p['sku']);
    $row['url.en_US'] = '/en/' . slug($p['name_en']) . '-' . strtolower($p['sku']);
    $row['attribute_key_1'] = 'brand';
    $row['value_1'] = 'Feldwerk';
    $row['attribute_key_1.de_DE'] = 'brand';
    $row['value_1.de_DE'] = 'Feldwerk';
    $row['attribute_key_1.en_US'] = 'brand';
    $row['value_1.en_US'] = 'Feldwerk';
    $abstractRows[] = $row;
}

// --- product_abstract_store DE (abstract_sku,store_name) — required for the product to be considered
// "in this store" at all; without it, ProductAbstractPagePublisher silently no-ops the page-search write ---
$abstractStoreHeader = ['abstract_sku', 'store_name'];
$abstractStoreRows = [];

foreach ($products as $p) {
    $abstractStoreRows[] = ['abstract_sku' => $p['sku'], 'store_name' => 'DE'];
}

// --- product_abstract_approval_status.csv (sku,approval_status) — without this, ProductApproval's own
// ProductPageSearchCollectionFilterPlugin silently drops every product from page search (and storage)
// whose approval_status isn't exactly "approved"; a brand-new product_abstract row has it NULL ---
$approvalStatusHeader = ['sku', 'approval_status'];
$approvalStatusRows = [];

foreach ($products as $p) {
    $approvalStatusRows[] = ['sku' => $p['sku'], 'approval_status' => 'approved'];
}

// --- product_concrete.csv (matches header: abstract_sku,concrete_sku,name.de_DE,name.en_US,description.de_DE,description.en_US,is_searchable.de_DE,is_searchable.en_US,bundled,is_quantity_splittable,attribute_key_1,value_1,attribute_key_1.de_DE,value_1.de_DE,attribute_key_1.en_US,value_1.en_US,attribute_key_2,value_2,attribute_key_2.de_DE,value_2.de_DE,attribute_key_2.en_US,value_2.en_US,is_active) ---
$concreteHeader = [
    'abstract_sku', 'concrete_sku', 'name.de_DE', 'name.en_US', 'description.de_DE', 'description.en_US',
    'is_searchable.de_DE', 'is_searchable.en_US', 'bundled', 'is_quantity_splittable',
    'attribute_key_1', 'value_1', 'attribute_key_1.de_DE', 'value_1.de_DE', 'attribute_key_1.en_US', 'value_1.en_US',
    'attribute_key_2', 'value_2', 'attribute_key_2.de_DE', 'value_2.de_DE', 'attribute_key_2.en_US', 'value_2.en_US',
    'is_active',
];

$concreteRows = [];

foreach ($products as $p) {
    $row = array_fill_keys($concreteHeader, '');
    $row['abstract_sku'] = $p['sku'];
    $row['concrete_sku'] = $p['sku'] . '-1';
    $row['name.de_DE'] = $p['name_de'];
    $row['name.en_US'] = $p['name_en'];
    $row['description.de_DE'] = $p['desc_de'];
    $row['description.en_US'] = $p['desc_en'];
    $row['is_searchable.de_DE'] = '1';
    $row['is_searchable.en_US'] = '1';
    $row['is_active'] = '1';
    $concreteRows[] = $row;
}

// --- product_price DE (abstract_sku,concrete_sku,price_type,store,currency,value_net,value_gross,price_data.volume_prices) ---
$priceHeader = ['abstract_sku', 'concrete_sku', 'price_type', 'store', 'currency', 'value_net', 'value_gross', 'price_data.volume_prices'];
$priceRows = [];

foreach ($products as $p) {
    $row = array_fill_keys($priceHeader, '');
    $row['abstract_sku'] = $p['sku'];
    $row['price_type'] = 'DEFAULT';
    $row['store'] = 'DE';
    $row['currency'] = 'EUR';
    $row['value_net'] = (string)(int)round($p['price_gross'] / 1.19);
    $row['value_gross'] = (string)$p['price_gross'];
    $priceRows[] = $row;
}

// --- product_stock.csv (concrete_sku,name,quantity,is_never_out_of_stock,is_bundle) ---
$stockHeader = ['concrete_sku', 'name', 'quantity', 'is_never_out_of_stock', 'is_bundle'];
$stockRows = [];

foreach ($products as $p) {
    $stockRows[] = [
        'concrete_sku' => $p['sku'] . '-1',
        'name' => 'Warehouse1',
        'quantity' => '50',
        'is_never_out_of_stock' => '0',
        'is_bundle' => '0',
    ];
}

// --- product_image.csv (image_set_name,external_url_large,external_url_small,locale,abstract_sku,concrete_sku,sort_order,product_image_key,product_image_set_key,alt_text_small.de_DE,alt_text_small.en_US,alt_text_large.de_DE,alt_text_large.en_US) ---
$imageHeader = [
    'image_set_name', 'external_url_large', 'external_url_small', 'locale', 'abstract_sku', 'concrete_sku',
    'sort_order', 'product_image_key', 'product_image_set_key',
    'alt_text_small.de_DE', 'alt_text_small.en_US', 'alt_text_large.de_DE', 'alt_text_large.en_US',
];
$imageRows = [];
$imgCounter = 1;

foreach ($products as $p) {
    $dataUri = svgDataUri($p['image']);

    foreach (['de_DE', 'en_US'] as $locale) {
        $imageRows[] = [
            'image_set_name' => 'default',
            'external_url_large' => $dataUri,
            'external_url_small' => $dataUri,
            'locale' => $locale,
            'abstract_sku' => $p['sku'],
            'concrete_sku' => '',
            'sort_order' => '0',
            'product_image_key' => 'demo_feldwerk_image_' . $imgCounter,
            'product_image_set_key' => '',
            'alt_text_small.de_DE' => '',
            'alt_text_small.en_US' => '',
            'alt_text_large.de_DE' => '',
            'alt_text_large.en_US' => '',
        ];
        $imgCounter++;
    }
}

/**
 * @param string $path
 * @param array<int, string> $header
 * @param array<int, array<string, string>> $rows
 */
function writeCsv(string $path, array $header, array $rows): void
{
    $handle = fopen($path, 'w');
    fputcsv($handle, $header);

    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn (string $key): string => $row[$key] ?? '', $header));
    }

    fclose($handle);
}

writeCsv($outDir . '/product_abstract.csv', $abstractHeader, $abstractRows);
writeCsv($outDir . '/product_abstract_store_DE.csv', $abstractStoreHeader, $abstractStoreRows);
writeCsv($outDir . '/product_abstract_approval_status.csv', $approvalStatusHeader, $approvalStatusRows);
writeCsv($outDir . '/product_concrete.csv', $concreteHeader, $concreteRows);
writeCsv($outDir . '/product_price_DE.csv', $priceHeader, $priceRows);
writeCsv($outDir . '/product_stock.csv', $stockHeader, $stockRows);
writeCsv($outDir . '/product_image.csv', $imageHeader, $imageRows);

echo 'Generated ' . count($products) . " products into $outDir\n";
