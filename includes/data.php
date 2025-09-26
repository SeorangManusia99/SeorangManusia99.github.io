<?php
const DATA_DIR = __DIR__ . '/../data';

function ensureDataDir(): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0777, true);
    }
}

function getFilePath(string $filename): string
{
    ensureDataDir();
    return DATA_DIR . '/' . $filename;
}

function loadData(string $filename): array
{
    $path = getFilePath($filename);
    if (!file_exists($path)) {
        return [];
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    return is_array($data) ? $data : [];
}

function saveData(string $filename, array $data): void
{
    $path = getFilePath($filename);
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function upsertRecord(array &$collection, array $record): void
{
    $found = false;
    foreach ($collection as $index => $item) {
        if ($item['id'] === $record['id']) {
            $collection[$index] = $record;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $collection[] = $record;
    }
}

function deleteRecord(array &$collection, string $id): void
{
    $collection = array_values(array_filter($collection, fn($item) => $item['id'] !== $id));
}
