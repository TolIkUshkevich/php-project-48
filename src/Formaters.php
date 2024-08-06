<?php

namespace Differ\Differ\Formaters;

use PHPUnit\Runner\Baseline\Writer;

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

function addPath(array $arr, string $postfix = ''): array
{
    $result = array_map(function ($value) use ($postfix) {
        if (isset($value['key'])) {
            if (is_array($value['value']) && $value['status'] !== 'complex') {
                return
                    [
                        'key' => $value['key'],
                        'status' => $value['status'],
                        'path' => $postfix . $value['key'],
                        'value' => addPath($value['value'], "{$postfix}{$value['key']}.")

                    ];
            } else {
                return [
                    'key' => $value['key'],
                    'status' => $value['status'],
                    'path' => $postfix . $value['key'],
                    'value' => $value['value']
                ];
            }
        } else {
            return $value;
        }
    }, $arr);
    return $result;
}

function plainFormating(array $data): string
{
    return implode('', array_map(function ($value) {
        switch ($value['status']) {
            case '+':
                if (is_array($value['value'])) {
                    return "\n" . "Property '{$value['path']}' was added with value: [complex value]";
                } else {
                    $item = printSomeWordForPlain($value['value']);
                    return "\n" . "Property '{$value['path']}' was added with value: {$item}";
                }
            case '-':
                return "\n" . "Property '{$value['path']}' was removed";
            case 'complex':
                if (!is_array($value['value']['old']) and !is_array($value['value']['new'])) {
                    $itemNew = printSomeWordForPlain($value['value']['new']);
                    $itemOld = printSomeWordForPlain($value['value']['old']);
                    return "\n" . "Property '{$value['path']}' was updated. From {$itemOld} to {$itemNew}";
                } elseif (is_array($value['value']['old']) and !is_array($value['value']['new'])) {
                    $itemNew = printSomeWordForPlain($value['value']['new']);
                    return "\n" . "Property '{$value['path']}' was updated. From [complex value] to {$itemNew}";
                } elseif (!is_array($value['value']['old']) and is_array($value['value']['new'])) {
                    $itemOld = printSomeWordForPlain($value['value']['old']);
                    return "\n" . "Property '{$value['path']}' was updated. From {$itemOld} to [complex value]";
                } else {
                    return "\n" . "Property '{$value['path']}' was updated. From [complex value] to [complex value]";
                }
            case 'no':
                return plainFormating($value['value']);
        }
    }, $data));
}

function printSomeWordForPlain(mixed $data)
{
    if ($data === false) {
        return 'false';
    } elseif ($data === true) {
        return 'true';
    } elseif ($data === null) {
        return 'null';
    } elseif ($data === 0) {
        return '0';
    } else {
        return "'{$data}'";
    }
}
