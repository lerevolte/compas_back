<?php

define('COMPAS_SYNC_TOKEN', 'CHANGE_ME');
define('COMPAS_SYNC_IBLOCK_ID', 2);
define('COMPAS_SYNC_LOG', $_SERVER['DOCUMENT_ROOT'] . '/upload/compas_sync.log');
define('COMPAS_SYNC_SITE_URL', 'https://opt6.ru');

$COMPAS_PROPERTY_MAP = [
    'length' => 'HEIGHT',
    'width' => 'WIDTH',
    'height' => 'DEPTH',
    'volume' => 'VOLUME',
    'weight' => 'WEIGHT',
    'storage_unit' => 'STORAGE_UNIT',
    'supplier_barcode' => 'SUPPLIER_BARCODE',
    'pallet_quantity' => 'PRODUCTS_COUNT_ON_PALLET',
    'replenishment_period' => 'REPLENISHMENT_PERIOD',
    'replenishment_method' => 'REPLENISHMENT_METHOD',
    'storage_conditions' => 'STORAGE_CONDITIONS',
];

$COMPAS_ENUM_FORMATS = [
    'WEIGHT' => '%s кг',
    'VOLUME' => '%s л',
];

$COMPAS_CATALOG_WEIGHT = true;

mb_internal_encoding('UTF-8');
header('Content-Type: application/json; charset=utf-8');

function compas_respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function compas_log(string $line): void
{
    @file_put_contents(COMPAS_SYNC_LOG, date('d.m.Y H:i:s') . ' ' . $line . "\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    compas_respond(['ok' => false, 'error' => 'POST only'], 405);
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    compas_respond(['ok' => false, 'error' => 'Invalid JSON'], 400);
}

$token = (string) ($payload['token'] ?? ($_SERVER['HTTP_X_COMPAS_TOKEN'] ?? ''));
if (strlen(COMPAS_SYNC_TOKEN) < 16 || !hash_equals(COMPAS_SYNC_TOKEN, $token)) {
    compas_log('unauthorized from ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
    compas_respond([
        'ok' => false,
        'error' => 'Unauthorized',
        'version' => 4,
        'expected_fp' => substr(hash('sha256', COMPAS_SYNC_TOKEN), 0, 8),
        'expected_len' => strlen(COMPAS_SYNC_TOKEN),
        'got_fp' => substr(hash('sha256', $token), 0, 8),
        'got_len' => strlen($token),
    ], 403);
}

$action = (string) ($payload['action'] ?? 'write');
$products = $payload['products'] ?? null;
if ($action === 'read' || $action === 'links') {
    $readIds = array_values(array_filter(array_map('intval', (array) ($payload['ids'] ?? []))));
    if (!count($readIds)) {
        compas_respond(['ok' => false, 'error' => 'Empty ids'], 400);
    }
} elseif (!is_array($products) || !count($products)) {
    compas_respond(['ok' => false, 'error' => 'Empty products'], 400);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog')) {
    compas_respond(['ok' => false, 'error' => 'iblock/catalog module not available'], 500);
}

if ($action === 'links') {
    $items = [];
    $found = [];
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => COMPAS_SYNC_IBLOCK_ID, 'ID' => array_slice($readIds, 0, 500)],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL', 'DETAIL_PICTURE', 'PREVIEW_PICTURE']
    );
    $absolute = fn ($path) => preg_match('#^https?://#i', $path) ? $path : COMPAS_SYNC_SITE_URL . '/' . ltrim($path, '/');
    while ($row = $res->GetNext()) {
        $id = (int) $row['ID'];
        $found[] = $id;
        $path = trim((string) $row['DETAIL_PAGE_URL']);
        $photo = null;
        foreach (['DETAIL_PICTURE', 'PREVIEW_PICTURE'] as $field) {
            if (!empty($row[$field])) {
                $file = CFile::GetPath((int) $row[$field]);
                if ($file) {
                    $photo = $absolute($file);
                    break;
                }
            }
        }
        if ($path === '' && !$photo) {
            continue;
        }
        $items[] = [
            'id' => $id,
            'url' => $path === '' ? null : $absolute($path),
            'photo' => $photo,
        ];
    }
    compas_respond([
        'ok' => true,
        'items' => $items,
        'missing' => array_values(array_diff($readIds, $found)),
    ]);
}

if ($action === 'read') {
    $codes = array_flip($COMPAS_PROPERTY_MAP);
    $items = [];
    $found = [];
    $res = \Bitrix\Iblock\ElementTable::getList([
        'select' => ['ID'],
        'filter' => ['IBLOCK_ID' => COMPAS_SYNC_IBLOCK_ID, 'ID' => array_slice($readIds, 0, 500)],
    ]);
    while ($row = $res->fetch()) {
        $found[] = (int) $row['ID'];
    }
    foreach ($found as $id) {
        $item = ['id' => $id];
        $props = CIBlockElement::GetProperty(COMPAS_SYNC_IBLOCK_ID, $id, ['sort' => 'asc'], []);
        while ($prop = $props->Fetch()) {
            $code = (string) $prop['CODE'];
            if (!isset($codes[$code])) {
                continue;
            }
            $field = $codes[$code];
            $value = $prop['PROPERTY_TYPE'] === 'L' ? $prop['VALUE_ENUM'] : $prop['VALUE'];
            if ($value === null || $value === '' || $value === false) {
                continue;
            }
            if (($prop['MULTIPLE'] ?? 'N') === 'Y') {
                $item[$field] = array_merge($item[$field] ?? [], [(string) $value]);
            } else {
                $item[$field] = (string) $value;
            }
        }
        $catalog = CCatalogProduct::GetByID($id);
        if ($catalog && (float) $catalog['WEIGHT'] > 0) {
            $item['catalog_weight'] = (float) $catalog['WEIGHT'];
        }
        $items[] = $item;
    }
    compas_respond([
        'ok' => true,
        'items' => $items,
        'missing' => array_values(array_diff($readIds, $found)),
    ]);
}

$propertyCache = [];
function compas_property(string $code)
{
    global $propertyCache;
    if (array_key_exists($code, $propertyCache)) {
        return $propertyCache[$code];
    }
    $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => COMPAS_SYNC_IBLOCK_ID, 'CODE' => $code]);
    $propertyCache[$code] = ($row = $res->Fetch()) ? $row : null;
    return $propertyCache[$code];
}

function compas_enum_id(array $property, string $value): ?int
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $res = CIBlockPropertyEnum::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => COMPAS_SYNC_IBLOCK_ID, 'PROPERTY_ID' => $property['ID'], 'VALUE' => $value]);
    while ($row = $res->Fetch()) {
        if (trim((string) $row['VALUE']) === $value) {
            return (int) $row['ID'];
        }
    }
    $enum = new CIBlockPropertyEnum();
    $id = $enum->Add(['PROPERTY_ID' => $property['ID'], 'VALUE' => $value, 'XML_ID' => md5($property['CODE'] . '|' . $value)]);
    return $id ? (int) $id : null;
}

function compas_format_number($value): string
{
    if (!is_numeric($value)) {
        return trim((string) $value);
    }
    $text = number_format((float) $value, 3, '.', '');
    return rtrim(rtrim($text, '0'), '.');
}

function compas_property_value(array $property, $value, array $enumFormats)
{
    $code = $property['CODE'];
    $multiple = ($property['MULTIPLE'] ?? 'N') === 'Y';
    $values = is_array($value) ? array_values($value) : [$value];
    $values = array_values(array_filter($values, fn ($v) => $v !== null && $v !== ''));
    if (!count($values)) {
        return $multiple ? [] : false;
    }
    if (($property['PROPERTY_TYPE'] ?? 'S') === 'L') {
        $ids = [];
        foreach ($values as $v) {
            $text = is_numeric($v) && isset($enumFormats[$code]) ? sprintf($enumFormats[$code], compas_format_number($v)) : trim((string) $v);
            $id = compas_enum_id($property, $text);
            if ($id) {
                $ids[] = $id;
            }
        }
        return $multiple ? $ids : ($ids[0] ?? false);
    }
    if (($property['PROPERTY_TYPE'] ?? 'S') === 'N') {
        $values = array_map(fn ($v) => is_numeric($v) ? compas_format_number($v) : (string) $v, $values);
    } else {
        $values = array_map(fn ($v) => (string) $v, $values);
    }
    return $multiple ? $values : $values[0];
}

$updated = [];
$missing = [];
$errors = [];
$unknownProps = [];

foreach ($products as $item) {
    if (!is_array($item)) {
        continue;
    }
    $id = (int) ($item['id'] ?? ($item['article'] ?? 0));
    if (!$id) {
        continue;
    }
    $exists = \Bitrix\Iblock\ElementTable::getList([
        'select' => ['ID'],
        'filter' => ['IBLOCK_ID' => COMPAS_SYNC_IBLOCK_ID, 'ID' => $id],
        'limit' => 1,
    ])->fetch();
    if (!$exists) {
        $missing[] = $id;
        continue;
    }

    try {
        $props = [];
        foreach ($COMPAS_PROPERTY_MAP as $field => $code) {
            if (!array_key_exists($field, $item)) {
                continue;
            }
            $property = compas_property($code);
            if (!$property) {
                $unknownProps[$code] = true;
                continue;
            }
            $props[$code] = compas_property_value($property, $item[$field], $COMPAS_ENUM_FORMATS);
        }
        if (count($props)) {
            CIBlockElement::SetPropertyValuesEx($id, COMPAS_SYNC_IBLOCK_ID, $props);
        }
        if ($COMPAS_CATALOG_WEIGHT && array_key_exists('weight', $item) && is_numeric($item['weight'])) {
            CCatalogProduct::Update($id, ['WEIGHT' => (float) $item['weight']]);
        }
        $el = new CIBlockElement();
        $el->Update($id, []);
        $updated[] = $id;
    } catch (\Throwable $e) {
        $errors[] = ['id' => $id, 'error' => $e->getMessage()];
        compas_log('error ' . $id . ': ' . $e->getMessage());
    }
}

compas_log('updated=' . count($updated) . ' missing=' . count($missing) . ' errors=' . count($errors) . (count($unknownProps) ? ' unknown_props=' . implode(',', array_keys($unknownProps)) : ''));

compas_respond([
    'ok' => count($errors) === 0,
    'updated' => $updated,
    'missing' => $missing,
    'errors' => $errors,
    'unknown_properties' => array_keys($unknownProps),
]);
