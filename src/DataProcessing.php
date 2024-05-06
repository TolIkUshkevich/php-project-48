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

function getValueStatus($key, $firstData, $secondData)
{
    switch ([array_key_exists($key, $firstData), array_key_exists($key, $secondData)]):
        case [true, true]:
            if ($firstData[$key] === $secondData[$key]){
                return 3;
            }
            return 2;
        case [true, false]:
            return 1;
        case [false, true]:
            return 0;
        endswitch;
}

function getValueByKey ($key, $firstData, $secondData = null){
    return $secondData === null ? $firstData[$key] : [$firstData[$key], $secondData[$key]];
}

/**
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @return array<mixed>$result
 */
function dataMerge(array $firstJsonData, array $secondJsonData, int $deipth = 1): array
{
    $result = [];
    $firstJsonData = arrayBoolValuesSort($firstJsonData);
    $secondJsonData = arrayBoolValuesSort($secondJsonData);
    $deipth = $deipth;

    $result = array_reduce(array_merge(array_keys($firstJsonData), array_keys($secondJsonData)), function ($acc, $key) use ($firstJsonData, $secondJsonData, $deipth) {
        $status = getValueStatus($key, $firstJsonData, $secondJsonData);
        $value = 1;
        switch ($status):
            case 3:
                $value = getValueByKey($key, $firstJsonData);
                break;
            case 2:
                $firstValue = getValueByKey($key, $firstJsonData);
                $secondValue = getValueByKey($key, $secondJsonData);
                if (is_array($firstValue) and is_array($secondValue)){
                    $value = dataMerge($firstValue, $secondValue);
                } else{
                    $value = [$firstValue, $secondValue];
                }
                break;
            case 1:
                $value = getValueByKey($key, $firstJsonData);
                break;
            case 0:
                $value = getValueByKey($key, $secondJsonData);
                break;
            endswitch;
        $acc[$key] = ['value' => $value, 'status' => $status, 'deipth' => $deipth];
        return $acc;
    });
    return $result;
}

/**
 * @param array<mixed>$json
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @return string $result
 */
function formatingData(array $data, array $firstJsonData = null, array $secondJsonData = null): string
{
    // $result = "{\n";
    // foreach ($json as $key => $value) {
    //     if (is_array($value)) {
    //         $result .= "  - {$key}: $value[0]\n  + {$key}: {$value[1]}\n";
    //         continue;
    //     }
    //     switch ([array_key_exists($key, $firstJsonData), array_key_exists($key, $secondJsonData)]) {
    //         case [true, true]:
    //             $result .= "    {$key}: {$value}\n";
    //             break;
    //         case [true, false]:
    //             $result .= "  - {$key}: {$value}\n";
    //             break;
    //         case [false, true]:
    //             $result .= "  + {$key}: {$value}\n";
    //     }
    // }
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data) {
        $value = $data[$key];
        switch ($value['status']):
            case 3:
                return "{$key}: {$value['value']}\n";
                break;
            case 2:
                $firstValue = $value['value'][array_key_first($value['value'])];
                $secondValue = $value['value'][array_key_last($value['value'])];
                if (is_array($firstValue) and is_array($secondValue)){
                    $acc .= formatingData($value['value']);
                } else {
                    $acc .= "- {$key}: {$firstValue}\n+ {$key}: {$secondValue}\n";
                }
                break;
            case 1:
                $acc .= "- {$key}: {$value['value']}\n";
                break;
            case 0:
                $acc .= "+ {$key}: {$value['value']}\n";
                break;
        endswitch;
        return $acc;
    }, "{\n");
    $result .= "}";
    return $result;
}
