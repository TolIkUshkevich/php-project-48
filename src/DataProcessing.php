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
    $resultArray = array_reduce(array_keys($array), function ($acc, $key) use ($array) {
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
        $acc[$key] = $newValue;
        return $acc;
    });
    return $resultArray;
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
            $acc[] = [
                'key' => $key,
                'status' => $status,
                'deipth' => $deipth,
                'path' => $newPath,
                'value' => setParams($value, $status, $deipth + 1, $newPath)
            ];
        } else {
            $acc[$key] = [
                'key' => $key,
                'status' => $status,
                'deipth' => $deipth,
                'path' => $newPath,
                'value' => $value
            ];
        }
        return $acc;
    });
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
    $result = [];
    $firstJsonData = arrayBoolValuesSort($firstJsonData);
    $secondJsonData = arrayBoolValuesSort($secondJsonData);
    $keys = array_unique(array_merge(array_keys($firstJsonData), array_keys($secondJsonData)));
    $firstJsonData = sort($firstJsonData, function ($left, $right) use ($firstJsonData) {
        $leftKey = key($firstJsonData);
        next($firstJsonData);
        $rightKey = key($firstJsonData);
        return strcmp($leftKey, $rightKey);
    }, true);
    $secondJsonData = sort($secondJsonData, function ($left, $right) use ($secondJsonData) {
        $leftKey = key($secondJsonData);
        next($secondJsonData);
        $rightKey = key($secondJsonData);
        return strcmp($leftKey, $rightKey);
    }, true);


    $result = array_reduce($keys, function ($acc, $key) use ($firstJsonData, $secondJsonData, $deipth, $path) {
        $newPath = [...$path, $key];
        $status = getValueStatus($key, $firstJsonData, $secondJsonData);
        $value = '';
        switch ($status) :
            case "equals":
                $value = getValueByKey($key, $firstJsonData);
                if (is_array($value)) {
                    $value = setParams($value, "equals", $deipth + 1, $newPath);
                }
                break;
            case "replaced":
                $firstValue = getValueByKey($key, $firstJsonData);
                $secondValue = getValueByKey($key, $secondJsonData);
                // if ($key === 'common'){
                //     var_dump($firstValue) . "\n";
                //     var_dump($secondValue) . "\n";
                //     die;
                // }
                if (is_array($firstValue) and is_array($secondValue)) {
                    $value = dataMerge($firstValue, $secondValue, $deipth + 1, $newPath);
                    $status = "equals";
                } elseif (is_array($firstValue)) {
                    $value = [
                        'array' => setParams($firstValue, "equals", $deipth + 1, $newPath),
                        'value' => setParams($secondValue, "added", $deipth + 1, $newPath, $key)
                    ];
                } elseif (is_array($secondValue)) {
                    $value = [
                        'value' => setParams($firstValue, "deleted", $deipth + 1, $newPath, $key),
                        'array' => setParams($secondValue, "equals", $deipth + 1, $newPath)
                    ];
                } else {
                    $value = [
                        'value1' => $firstValue,
                        'value2' => $secondValue
                    ];
                }
                break;
            case "deleted":
                if (is_array(getValueByKey($key, $firstJsonData))) {
                    $value = setParams(getValueByKey($key, $firstJsonData), "equals", $deipth + 1, $newPath);
                } else {
                    $value = getValueByKey($key, $firstJsonData);
                }
                break;
            case "added":
                if (is_array(getValueByKey($key, $secondJsonData))) {
                    $value = setParams(getValueByKey($key, $secondJsonData), "equals", $deipth + 1, $newPath);
                } else {
                    $value = getValueByKey($key, $secondJsonData);
                }
                break;
        endswitch;
        $acc[] = [
            'key' => $key,
            'status' => $status,
            'deipth' => $deipth,
            'path' => $newPath,
            'value' => $value
        ];
        return $acc;
    });
    $result = sort ($result, function($a, $b){
        if ($a['key'] == $b['key']) {
            return 0;
        }
        return ($a['key'] < $b['key']) ? -1 : 1;
    });
    return $result;
}
