<?php

namespace App\DataProcessing;

/**
 * @param array<mixed>$array
 * @return array<mixed>$array
 */
function arrayBoolValuesSort(array $array): array
{
    $resultArray = array_reduce(array_keys($array), function ($acc, $key) use ($array){
        $value = $array[$key];
        if (is_array($value)){
            $acc[$key] = arrayBoolValuesSort($value);
        }
        if (is_bool($value) or $value === null) {
            switch ($value):
                case true:
                    $newValue = 'true';
                    break;
                case false:
                    $newValue = 'false';
                    break;
                case null:
                    $newValue = 'null';
                    break;
                endswitch;
        } else {
            $newValue = $value;
        }
        $acc[$key] = $newValue;
        return $acc;
    });
    return $resultArray;
}

function getValueStatus($key, $firstData, $secondData)
{
    switch ([array_key_exists($key, $firstData), array_key_exists($key, $secondData)]):
        case [true, true]:
            if ($firstData[$key] === $secondData[$key]){
                return 3;
            } elseif (is_array($firstData[$key]) or is_array($secondData[$key])){
                return 4;
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

function setParams(mixed $data, int $status, $deipth = 1){
    if (!is_array($data)){
        $result = ['value' => $data, 'status' => $status, 'deipth' => $deipth];
        return $result;
    }
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data, $status, $deipth){
        $value = $data[$key];
        if (is_array($value)){
            $acc[$key] = ['value' => setParams($value, $status, $deipth+1), 'status' => $status, 'deipth' => $deipth];
        } else {
            $acc[$key] = ['value' => $value, 'status' => $status, 'deipth' => $deipth];
        }
        return $acc;
    });
    return $result;
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

    $result = array_reduce(array_unique(array_merge(array_keys($firstJsonData), array_keys($secondJsonData))), function ($acc, $key) use ($firstJsonData, $secondJsonData, $deipth) {
        $status = getValueStatus($key, $firstJsonData, $secondJsonData);
        $value = 1;
        switch ($status):
            case 4:
                
            case 3:
                $value = getValueByKey($key, $firstJsonData);
                if (is_array($value)){
                    $value = setParams($value, 3, $deipth++);
                } else {
                    $value = $value;
                }
                break;
            case 2:
                $firstValue = getValueByKey($key, $firstJsonData);
                $secondValue = getValueByKey($key, $secondJsonData);
                if (is_array($firstValue) and is_array($secondValue)){
                    $value = dataMerge($firstValue, $secondValue, $deipth+1);
                } elseif (is_array($firstValue) or is_array($secondValue)){
                    $value = [setParams($firstValue, 1), setParams($secondValue, 0)];
                } else {
                    $value = [$firstValue, $secondValue];
                }
                break;
            case 1:
                if (is_array(getValueByKey($key, $firstJsonData))){
                    $value = setParams(getValueByKey($key, $firstJsonData), 3, $deipth++);
                } else {
                    $value = getValueByKey($key, $firstJsonData);
                }
                break;
            case 0:
                if (is_array(getValueByKey($key, $secondJsonData))){
                    $value = setParams(getValueByKey($key, $secondJsonData), 3, $deipth++);
                } else {
                    $value = getValueByKey($key, $secondJsonData);
                }
                break;
            endswitch;
        $acc[$key] = ['value' => $value, 'status' => $status, 'deipth' => $deipth];
        return $acc;
    }); 
    ksort($result);
    return $result;
}

/**
 * @param array<mixed>$json
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @return string $result
 */
function formatingData(array $data): string
{
    $result = "{\n";
    $result .= array_reduce(array_keys($data), function ($acc, $key) use ($data) {
        $value = $data[$key];
        // print_r($value['deipth']);
        // $deipth = $value['deipth'];
        $deipth = 1;

        // if ($value['status'] === null){
        //     var_dump($key);
        //     die;
        // }
        switch ($value['status']):
            case 3:
                if (is_array($value['value'])){
                    $acc .= formatingData($value['value']);
                } else {
                    $string = str_repeat("  ", $deipth);
                    $acc .= "{$string}  {$key}: {$value['value']}\n";
                }
                break;
            case 2:
                $firstValue = $value['value'][array_key_first($value['value'])];
                $secondValue = $value['value'][array_key_last($value['value'])];
                if (is_array($firstValue) or is_array($secondValue)){
                    $acc .= $key;
                    $acc .= formatingData($value['value']);
                    // var_dump($acc);
                } else {
                    $string = str_repeat("  ", $deipth);
                    $acc .= "{$string}- {$key}: {$firstValue}\n{$string}+ {$key}: {$secondValue}\n";
                }
                break;
            case 1:
                $string = str_repeat("  ", $deipth);
                if (is_array($value['value'])){
                    $acc .= formatingData($value['value']);
                } else{
                    $acc .= "{$string}- {$key}: {$value['value']}\n";
                }
                break;
            case 0:
                $currentValue = $value['value'];
                if (is_array($currentValue)){
                    $acc .= formatingData($value['value']);
                } else { 
                    $string = str_repeat("  ", $deipth);
                    $acc .= "{$string}+ {$key}: {$currentValue}\n";
                }
                break;
        endswitch;
        return $acc;
    });
    $result .= "}\n";
    // var_dump($result);
    return $result;
}
