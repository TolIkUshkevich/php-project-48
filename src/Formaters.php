<?php

namespace App\Formaters;

function valueFormation(mixed $value): string
{
    if (is_array($value)) {
        return '[complex value]';
    } elseif (is_string($value)) {
        if ($value === 'true' or $value === 'false' or $value === 'null') {
            return sprintf("%s", $value);
        }
        return sprintf("'%s'", $value);
    }
    return $value;
}

function defaultFormating(array $data, int $deipth = 1): string
{
    $stapleString = str_repeat("    ", $deipth - 1);
    $result = "";
    $result .= array_reduce(array_keys($data), function ($acc, $key) use ($data) {
        $properties = $data[$key];
        $value = $properties['value'];
        $afterKeyString = $value == '' ? '' : ' ';
        $deipthOfElement = $properties['deipth'];
        $status = $properties['status'];
        $string = str_repeat("    ", $deipthOfElement);
        switch ($status) :
            case "equals":
                if (is_array($value)) {
                    $acc .= "\n{$string}{$key}:{$afterKeyString}";
                    $acc .= defaultFormating($value, $deipthOfElement + 1);
                } else {
                    $acc .= "\n{$string}{$key}:{$afterKeyString}{$value}";
                }
                break;
            case "replaced":
                $stringForReplacedStatus = substr($string, 0, -2);
                $firstValue = $value[array_key_first($value)];
                $secondValue = $value[array_key_last($value)];
                $afterKeyFirstString = $firstValue == '' ? '' : ' ';
                $afterKeySecondString = $secondValue == '' ? '' : ' ';
                switch (array_keys($value)) :
                    case ['array', 'value']:
                        $acc .= "\n{$stringForReplacedStatus}- {$key}:{$afterKeyFirstString}";
                        $acc .= defaultFormating($firstValue, $deipthOfElement + 1);
                        $acc .= "\n{$stringForReplacedStatus}+ {$key}:{$afterKeySecondString}{$secondValue['value']}";
                        break;
                    case ['value', 'array']:
                        $acc .= "\n{$stringForReplacedStatus}- {$key}:{$afterKeyFirstString}{$firstValue['value']}";
                        $acc .= "\n{$stringForReplacedStatus}+ {$key}:{$afterKeySecondString}";
                        $acc .= defaultFormating($secondValue, $deipthOfElement + 1);
                        break;
                    case ['value1', 'value2']:
                        $acc .= "\n{$stringForReplacedStatus}- {$key}:{$afterKeyFirstString}{$firstValue}
{$stringForReplacedStatus}+ {$key}:{$afterKeySecondString}{$secondValue}";
                        break;
                endswitch;
                break;
            case "deleted":
                $stringForDeletedStatus = substr($string, 0, -2);
                if (is_array($value)) {
                    $currentValue = defaultFormating($value, $deipthOfElement + 1);
                    $acc .= "\n{$stringForDeletedStatus}- {$key}:{$afterKeyString}{$currentValue}";
                } else {
                    $acc .= "\n{$stringForDeletedStatus}- {$key}:{$afterKeyString}{$value}";
                }
                break;
            case "added":
                $stringForAddedStatus = substr($string, 0, -2);
                if (is_array($value)) {
                    $currentValue = defaultFormating($value, $deipthOfElement + 1);
                    $acc .= "\n{$stringForAddedStatus}+ {$key}:{$afterKeyString}{$currentValue}";
                } else {
                    $acc .= "\n{$stringForAddedStatus}+ {$key}:{$afterKeyString}{$value}";
                }
                break;
        endswitch;
        return $acc;
    });
    return "{{$result}\n{$stapleString}}";
}

function plainFormating(mixed $data, bool $recursively = false): string
{
    $result = "";
    $result = array_reduce(array_keys($data), function ($acc, $key) use ($data) {
        $properties = $data[$key];
        $status = $properties['status'];
        $path = $properties['path'];
        $value = $properties['value'];
        switch ($status) :
            case 'equals':
                if (is_array($value)) {
                    $acc[] = plainFormating($value, true);
                }
                break;
            case 'replaced':
                $resultPath = implode('.', $path);
                switch (array_keys($value)) :
                    case ['value1', 'value2']:
                        $firstValue = valueFormation($value[array_key_first($value)]);
                        $secondValue = valueFormation($value[array_key_last($value)]);
                        break;
                    case ['array', 'value']:
                        $firstValue = '[complex value]';
                        $secondValue = valueFormation($value[array_key_last($value)]['value']);
                        break;
                    case ['value', 'array']:
                        $firstValue = valueFormation($value[array_key_first($value)]['value']);
                        $secondValue = '[complex value]';
                        break;
                    case ['array', 'array']:
                        $firstValue = '[complex value]';
                        $secondValue = '[complex value]';
                        break;
                endswitch;
                $acc[] = "Property '{$resultPath}' was updated. From {$firstValue} to {$secondValue}";
                break;
            case 'deleted':
                $resultPath = implode('.', $path);
                $acc[] = "Property '{$resultPath}' was removed";
                break;
            case 'added':
                $resultValue = valueFormation($value);
                $resultPath = implode('.', $path);
                $acc[] = "Property '{$resultPath}' was added with value: {$resultValue}";
        endswitch;
        return $acc;
    });
    return implode("\n", $result);
}
