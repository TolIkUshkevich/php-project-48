<?php

namespace App\DataProcessing;

/**
 * @param array<mixed>$array
 * @return array<mixed>$array
 */
function arrayBoolValuesSort(array $array): array
{
    foreach ($array as $key => $value) {
        if (is_bool($value)) {
            $newValue = $value ? 'true' : 'false';
            $array[$key] = $newValue;
        }
    }
    return $array;
}

/**
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @return array<mixed>$result
 */
function dataMerge(array $firstJsonData, array $secondJsonData): array
{
    $result = [];
    $firstJsonData = arrayBoolValuesSort($firstJsonData);
    $secondJsonData = arrayBoolValuesSort($secondJsonData);

    foreach ($firstJsonData as $key => $value) {
        if (array_key_exists($key, $secondJsonData)) {
            if ($value === $secondJsonData[$key]) {
                $result[$key] = $value;
                unset($secondJsonData[$key]);
                continue;
            }
            $result[$key] = [$value, $secondJsonData[$key]];
            unset($secondJsonData[$key]);
            continue;
        }
        $result[$key] = $value;
        unset($secondJsonData[$key]);
    }
    foreach ($secondJsonData as $key => $value) {
        $result[$key] = $value;
    }
    ksort($result);
    return $result;
}

/**
 * @param array<mixed>$json
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @return string $result
 */
function formatingData(array $json, array $firstJsonData, array $secondJsonData): string
{
    $result = "{\n";
    foreach ($json as $key => $value) {
        if (is_array($value)) {
            $result .= "  - {$key}: $value[0]\n  + {$key}: {$value[1]}\n";
            continue;
        }
        switch ([array_key_exists($key, $firstJsonData), array_key_exists($key, $secondJsonData)]) {
            case [true, true]:
                $result .= "    {$key}: {$value}\n";
                break;
            case [true, false]:
                $result .= "  - {$key}: {$value}\n";
                break;
            case [false, true]:
                $result .= "  + {$key}: {$value}\n";
        }
    }
    $result .= "}";
    return $result;
}
