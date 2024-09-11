<?php

namespace Formaters\plainFormater;

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
