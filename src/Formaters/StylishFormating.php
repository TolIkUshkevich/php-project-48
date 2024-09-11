<?php

namespace Formaters\stylishFormater;

function valueFormation(mixed $value): mixed
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

function defaultFormating(mixed $data, string $indent = ''): array
{
    $arrayStrings = array_map(function ($value) use ($indent) {
        switch ($value['status']) {
            case '+':
            case '-':
                if (is_array($value['value'])) {
                    return printNonAnilizeArray($value['value'], $indent, $value['status'], $value['key']);
                }
                return printString($value['value'], $indent, $value['status'], $value['key']);
            case 'no':
            case 'both':
                if (is_array($value['value'])) {
                    return printArray($value['value'], $indent, ' ', $value['key']);
                }
                return printString($value['value'], $indent, ' ', $value['key']);
            case 'complex':
                if (is_array($value['value']['old'])) {
                    $param1 = printNonAnilizeArray($value['value']['old'], $indent, '-', $value['key']);
                } else {
                    $param1 = printString($value['value']['old'], $indent, '-', $value['key']);
                }
                if (is_array($value['value']['new'])) {
                    $param2 = printNonAnilizeArray($value['value']['new'], $indent, '+', $value['key']);
                } else {
                    $param2 = printString($value['value']['new'], $indent, '+', $value['key']);
                }
                return "$param1" . "$param2";
        }
    }, $data);
    return $arrayStrings;
}

function printNonAnilizeArray(array $value, string $indent, string $status, string $key): string
{
    return "\n" . "{$indent}  {$status} " . "{$key}: {" .
        implode('', printArrayWithoutStatus($value, $indent .
            str_repeat(' ', 4))) .
        "\n" . $indent . str_repeat(' ', 4) . "}";
}

function printString(mixed $value, string $indent, string $status, string $key): string
{
    $arg = printSomeWord($value);
    return "\n" . "$indent" . "  {$status} {$key}: {$arg}";
}

function printArray(array $value, string $indent, string $status, string $key): string
{
    return "\n" . "{$indent}  {$status} " . "{$key}: {" .
        implode('', defaultFormating($value, $indent
            . str_repeat(' ', 4))) .
        "\n" . "$indent" . str_repeat(' ', 4) . "}";
}

function printArrayWithoutStatus(array $value, string $indent): array
{
    $result = array_map(function ($key, $value) use ($indent) {
        if (!is_array($value)) {
            $arg = printSomeWord($value);
            return "\n" . "$indent" . "    {$key}: {$arg}";
        } else {
            return "\n" . "{$indent}    " . "{$key}: {" .
                implode('', printArrayWithoutStatus($value, $indent .
                    str_repeat(' ', 4))) .
                "\n" . $indent . str_repeat(' ', 4) . "}";
        }
    }, array_keys($value), $value);
    return $result;
}

function printSomeWord(mixed $str)
{
    if ($str === false) {
        return 'false';
    } elseif ($str === true) {
        return 'true';
    } elseif ($str === null) {
        return 'null';
    } else {
        return $str;
    }
}
