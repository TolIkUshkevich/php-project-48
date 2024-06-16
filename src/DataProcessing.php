<?php

namespace App\DataProcessing;

use PhpParser\Node\Expr\Cast\Array_;

/**
 * @param array<mixed>$array
 * @return array<mixed>$array
 */
function arrayBoolValuesSort(array $array): array
{
    $resultArray = array_reduce(array_keys($array), function ($acc, $key) use ($array){
        $value = $array[$key];
        if (is_bool($value)) {
            switch ($value):
                case true:
                    $newValue = 'true';
                    break;
                case false:
                    $newValue = 'false';
                    break;
                endswitch;
        } elseif ($value === null){
            $newValue = 'null';
        } else {
            $newValue = $value;
        }
        $acc[$key] = $newValue;
        return $acc;
    });
    return $resultArray;
}

function findKey($key, $data){
    return in_array($key, array_keys($data));
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
        var_dump($key, $secondData);
        die;
}

function getValueByKey ($key, $firstData, $secondData = null){
    return $secondData === null ? $firstData[$key] : [$firstData[$key], $secondData[$key]];
}

function setParams(mixed $data, string $status, int $deipth){
    if (!is_array($data)){
        $result = ['status' => $status, 'deipth' => $deipth, 'value' => $data];
        return $result;
    }
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data, $status, $deipth){
        $value = $data[$key];
        if (is_array($value)){
            $acc[$key] = ['status' => $status, 'deipth' => $deipth, 'value' => setParams($value, $status, $deipth+1)];
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
        $status = getValueStatus($key, $firstJsonData, $secondJsonData);
        switch ($status):
            case "equals":
                $value = getValueByKey($key, $firstJsonData);
                if (is_array($value)){
                    $value = setParams($value, "equals", $deipth+1);
                }
                break;
            case "replaced":
                $firstValue = getValueByKey($key, $firstJsonData);
                $secondValue = getValueByKey($key, $secondJsonData);
                if (is_array($firstValue) and is_array($secondValue)){
                    $value = dataMerge($firstValue, $secondValue, $deipth+1);
                    $status = "equals";
                } elseif (is_array($firstValue)){
                    $value = ['array' => setParams($firstValue, "equals", $deipth+1), 'value' => setParams($secondValue, "added", $deipth+1)];
                } elseif (is_array($secondValue)){
                    $value = ['value' => setParams($firstValue, "deleted", $deipth+1), 'array' => setParams($secondValue, "equals", $deipth+1)];
                } else {
                    $value = ['value1' => $firstValue, 'value2' => $secondValue];
                }
                break;
            case "deleted":
                if (is_array(getValueByKey($key, $firstJsonData))){
                    $value = setParams(getValueByKey($key, $firstJsonData), "equals", $deipth+1);
                } else {
                    $value = getValueByKey($key, $firstJsonData);
                }
                break;
            case "added":
                if (is_array(getValueByKey($key, $secondJsonData))){
                    $value = setParams(getValueByKey($key, $secondJsonData), "equals", $deipth+1);
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
function formatingData(array $data, int $deipth = 1): string
{
    $result = "";
    $stapleString = str_repeat("    ", $deipth-1);
    $result .= array_reduce(array_keys($data), function ($acc, $key) use ($data) {
        $value = $data[$key];
        if ($value == ''){
            var_dump($key, $value);
            die;
        }
        $afterKeyString = $value == '' ? '' : ' ';
        $deipthOfElement = $value['deipth'];
        $string = str_repeat("    ", $deipthOfElement);
        switch ($value['status']):
            case "equals":
                if (is_array($value['value'])){
                    $acc .= "\n{$string}{$key}:{$afterKeyString}";
                    $acc .= formatingData($value['value'], $deipthOfElement+1);
                } else {
                    $acc .= "\n{$string}{$key}:{$afterKeyString}{$value['value']}";
                }
                break;
            case "replaced":
                $stringForReplacedStatus = substr($string, 0, -2);
                $firstValue = $value['value'][array_key_first($value['value'])];
                $secondValue = $value['value'][array_key_last($value['value'])];
                $afterKeyFirstString = $firstValue == '' ? '' : ' ';
                $afterKeySecondString = $secondValue == '' ? '' : ' ';
                // var_dump($firstValue, $secondValue);
                // die;
                switch (array_keys($value['value'])):
                case ['array', 'value']:
                    $acc .= "\n{$stringForReplacedStatus}- {$key}:{$afterKeyFirstString}";
                    $acc .= formatingData($firstValue, $deipthOfElement+1);
                    $acc .= "\n{$stringForReplacedStatus}+ {$key}:{$afterKeySecondString}{$secondValue['value']}";
                    break;
                case ['value', 'array']:
                    $acc .= "\n{$stringForReplacedStatus}- {$key}:{$afterKeyFirstString}{$firstValue['value']}";
                    $acc .= "\n{$stringForReplacedStatus}+ {$key}:{$afterKeySecondString}";
                    $acc .= formatingData($secondValue, $deipthOfElement+1);
                    break;
                case ['value1', 'value2']:
                    $acc .= "\n{$stringForReplacedStatus}- {$key}:{$afterKeyFirstString}{$firstValue}\n{$stringForReplacedStatus}+ {$key}:{$afterKeySecondString}{$secondValue}";
                    break;
                endswitch;
                break;
            case "deleted":
                $stringForDeletedStatus = substr($string, 0, -2);
                if (is_array($value['value'])){
                    $currentValue = formatingData($value['value'], $deipthOfElement+1);
                    $acc .= "\n{$stringForDeletedStatus}- {$key}:{$afterKeyString}{$currentValue}";
                } else{
                    $acc .= "\n{$stringForDeletedStatus}- {$key}:{$afterKeyString}{$value['value']}";
                }
                break;
            case "added":
                $stringForAddedStatus = substr($string, 0, -2);
                if (is_array($value['value'])){
                    $currentValue = formatingData($value['value'], $deipthOfElement+1);
                    $acc .= "\n{$stringForAddedStatus}+ {$key}:{$afterKeyString}{$currentValue}";
                } else { 
                    $acc .= "\n{$stringForAddedStatus}+ {$key}:{$afterKeyString}{$value['value']}";
                }
                break;
        endswitch;
        return $acc;
    });
    return "{{$result}\n{$stapleString}}";
}
