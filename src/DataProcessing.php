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

function findKey($key, $data){
    $result = array_reduce(array_keys($data), function($acc, $value) use ($key){
        if (is_array($value)){
            $acc = findKey($key, $value);
        } else {
            $acc = $key === $value;
        }
        return $acc;
    });
    return $result;
}

function getValueStatus($key, $firstData, $secondData)
{
    switch ([findKey($key, $firstData), findKey($key, $secondData)]):
        case [true, true]:
            if ($firstData[$key] === $secondData[$key]){
                return "equals";
            }
            return "replaced";
        case [true, false]:
            return "deleted";
        case [false, true]:
            return "added";
        endswitch;
}

function getValueByKey ($key, $firstData, $secondData = null){
    return $secondData === null ? $firstData[$key] : [$firstData[$key], $secondData[$key]];
}

function setParams(mixed $data, string $status, $deipth){
    if (!is_array($data)){
        $result = ['status' => $status, 'deipth' => $deipth, 'value' => $data];
        return $result;
    }
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data, $status, $deipth){
        $value = $data[$key];
        if (is_array($value)){
            $acc[$key] = ['status' => $status, 'deipth' => $deipth, 'value' => setParams($value, $status, $deipth++)];
        } else {
            $acc[$key] = ['status' => $status, 'deipth' => $deipth, 'value' => $value];
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
    $keys = array_unique(array_merge(array_keys($firstJsonData), array_keys($secondJsonData)));

    $result = array_reduce($keys, function ($acc, $key) use ($firstJsonData, $secondJsonData, $deipth) {
        var_dump($key, $deipth);
        $status = getValueStatus($key, $firstJsonData, $secondJsonData);
        switch ($status):
            case "equals":
                $value = getValueByKey($key, $firstJsonData);
                if (is_array($value)){
                    $value = setParams($value, "equals", $deipth++);
                } else {
                    $value = $value;
                }
                break;
            case "replaced":
                $firstValue = getValueByKey($key, $firstJsonData);
                $secondValue = getValueByKey($key, $secondJsonData);
                if (is_array($firstValue) and is_array($secondValue)){
                    $value = dataMerge($firstValue, $secondValue, $deipth++);
                } elseif (is_array($firstValue) or is_array($secondValue)){
                    $value = [setParams($firstValue, "deleted", $deipth++), setParams($secondValue, "added", $deipth++)];
                } else {
                    $value = [$firstValue, $secondValue];
                }
                break;
            case "deleted":
                if (is_array(getValueByKey($key, $firstJsonData))){
                    $value = setParams(getValueByKey($key, $firstJsonData), "equals", $deipth++);
                } else {
                    $value = getValueByKey($key, $firstJsonData);
                }
                break;
            case "added":
                if (is_array(getValueByKey($key, $secondJsonData))){
                    $value = setParams(getValueByKey($key, $secondJsonData), "equals", $deipth++);
                } else {
                    $value = getValueByKey($key, $secondJsonData);
                }
                break;
            endswitch;
        $acc[$key] = ['status' => $status, 'deipth' => $deipth, 'value' => $value];
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
        $deipth = $value['deipth'];
        // $deipth = 1;

        // if ($key === 'data'){
        //     var_dump($value);
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
                } else {
                    $string = str_repeat("  ", $deipth);
                    $acc .= "{$string}- {$key}: {$firstValue}\n{$string}+ {$key}: {$secondValue}\n";
                }
                break;
            case 1:
                $string = str_repeat("  ", $deipth);
                if (is_array($value['value'])){
                    $currentValue = formatingData($value['value']);
                    $acc .= "{$string}- {$key}: {$currentValue}\n";
                } else{
                    $acc .= "{$string}- {$key}: {$value['value']}\n";
                }
                break;
            case 0:
                $string = str_repeat("  ", $deipth);
                if (is_array($value['value'])){
                    $currentValue = formatingData($value['value']);
                    $acc .= "{$string}- {$key}: {$currentValue}\n";
                } else { 
                    $string = str_repeat("  ", $deipth);
                    $acc .= "{$string}+ {$key}: {$value['value']}\n";
                }
                break;
        endswitch;
        return $acc;
    });
    $result .= "}\n";
    // var_dump($result);
    return $result;
}
