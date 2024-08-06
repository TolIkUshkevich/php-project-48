<?php

namespace Differ\Differ\DataProcessing;

require_once __DIR__ . "/autoload.php";

use function Functional\sort;

/**
 * @param array<mixed>$array
 * @return array<mixed>$array
 */
function arrayBoolValuesSort(array $array): array
{
    return array_reduce(array_keys($array), function ($acc, $key) use ($array) {
        $value = $array[$key];
        if (is_bool($value)) {
            switch ($value) :
                case true:
                    $newValue = 'true';
                    break;
                case false:
                    $newValue = 'false';
                    break;
            endswitch;
        } elseif ($value === null) {
            $newValue = 'null';
        } else {
            $newValue = $value;
        }
        return array_merge($acc, [$key => $newValue]);
    }, []);
}

/**
 * @param string$key
 * @param array<mixed> $data
 * @return bool
 */
function findKey(string $key, array $data): bool
{
    return in_array($key, array_keys($data), true);
}

/**
 * @param string$key
 * @param array<mixed>$firstData
 * @param array<mixed>$secondData
 * @return string
 */
function getValueStatus(string $key, array $firstData, array $secondData): string
{
    switch ([findKey($key, $firstData), findKey($key, $secondData)]) :
        case [true, true]:
            if ($firstData[$key] === $secondData[$key]) {
                $resultStatus = "equals";
                break;
            }
            $resultStatus = "replaced";
            break;
        case [true, false]:
            $resultStatus = "deleted";
            break;
        case [false, true]:
            $resultStatus = "added";
            break;
        default:
            $resultStatus = "added";
    endswitch;
    return $resultStatus;
}

/**
 * @param string$key
 * @param array<mixed>$firstData
 * @param array<mixed>$secondData
 * @return mixed
 */
function getValueByKey(string $key, array $firstData, array $secondData = null): mixed
{
    return $secondData === null ? $firstData[$key] : [$firstData[$key], $secondData[$key]];
}
/**
 * @param mixed$data
 * @param string$status
 * @param int$deipth
 * @param array<mixed>$path
 * @return array<mixed>
 */
function setParams(mixed $data, string $status, int $deipth, array $path, string $key = null): array
{
    if (!is_array($data)) {
        return [
            'key' => $key,
            'status' => $status,
            'deipth' => $deipth,
            'path' => $path,
            'value' => $data
        ];
    }
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data, $status, $deipth, $path) {
        $value = $data[$key];
        $newPath = [...$path, $key];
        if (is_array($value)) {
            return array_merge($acc, [
                'key' => $key,
                'status' => $status,
                'deipth' => $deipth,
                'path' => $newPath,
                'value' => setParams($value, $status, $deipth + 1, $newPath)
            ]);
        } else {
            return array_merge($acc, [
                'key' => $key,
                'status' => $status,
                'deipth' => $deipth,
                'path' => $newPath,
                'value' => $value
            ]);
        }
    }, []);
    return $result;
}

/**
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @param int$deipth
 * @param array<string>$path
 * @return array<mixed>$result
 */
function dataMerge(array $firstJsonData, array $secondJsonData, int $deipth = 1, array $path = []): array
{
    $keys = array_unique(array_merge(array_keys($firstJsonData), array_keys($secondJsonData)));
    $sortKeys = sort($keys, fn($left, $right) => strcmp($left, $right));
    return array_map(function ($key) use ($firstJsonData, $secondJsonData) {
        if (array_key_exists($key, $firstJsonData) and !array_key_exists($key, $secondJsonData)) {
            return ['key' => $key, 'status' => '-', 'value' => $firstJsonData[$key]];
        } elseif (!array_key_exists($key, $firstJsonData) and array_key_exists($key, $secondJsonData)) {
            return ['key' => $key, 'status' => '+', 'value' => $secondJsonData[$key]];
        } elseif (array_key_exists($key, $firstJsonData) and array_key_exists($key, $secondJsonData)) {
            if (is_array($firstJsonData[$key]) and is_array($secondJsonData[$key])) {
                return ['key' => $key, 'status' => 'no', 'value' => dataMerge($firstJsonData[$key], $secondJsonData[$key])];
            } elseif ($firstJsonData[$key] === $secondJsonData[$key]) {
                return ['key' => $key, 'status' => 'both', 'value' => $firstJsonData[$key]];
            } else {
                return ['key' => $key, 'status' => 'complex', 'value' => ['old' => $firstJsonData[$key], 'new' => $secondJsonData[$key]]];
            }
        }
    }, $sortKeys);
}
