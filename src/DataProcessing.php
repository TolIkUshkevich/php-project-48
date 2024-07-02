<?php

namespace Differ\Differ\DataProcessing;

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
    return in_array($key, array_keys($data));
}

/**
 * @param string$key
 * @param array<mixed>$firstData
 * @param array<mixed>$secondData
 * @return string
 */
function getValueStatus(string $key, array $firstData, array $secondData): string
{
    $resultStatus = "";
    switch ([findKey($key, $firstData), findKey($key, $secondData)]) :
        case [true, true]:
            if ($firstData[$key] === $secondData[$key]) {
                $resultStatus = "equals";
            }
            $resultStatus = "replaced";
        case [true, false]:
            $resultStatus = "deleted";
        case [false, true]:
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
function setParams(mixed $data, string $status, int $deipth, array $path): array
{
    if (!is_array($data)) {
        $result = ['status' => $status, 'deipth' => $deipth, 'path' => $path, 'value' => $data];
        return $result;
    }
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data, $status, $deipth, $path) {
        $value = $data[$key];
        $newPath = [...$path, $key];
        if (is_array($value)) {
            $acc[$key] = [
                'status' => $status,
                'deipth' => $deipth,
                'path' => $newPath,
                'value' => setParams($value, $status, $deipth + 1, $newPath)
            ];
        } else {
            $acc[$key] = [
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
                if (is_array($firstValue) and is_array($secondValue)) {
                    $value = dataMerge($firstValue, $secondValue, $deipth + 1, $newPath);
                    $status = "equals";
                } elseif (is_array($firstValue)) {
                    $value = [
                        'array' => setParams($firstValue, "equals", $deipth + 1, $newPath),
                        'value' => setParams($secondValue, "added", $deipth + 1, $newPath)
                    ];
                } elseif (is_array($secondValue)) {
                    $value = [
                        'value' => setParams($firstValue, "deleted", $deipth + 1, $newPath),
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
        $acc[$key] = [
            'status' => $status,
            'deipth' => $deipth,
            'path' => $newPath,
            'value' => $value
        ];
        return $acc;
    });
    ksort($result);
    return $result;
}
