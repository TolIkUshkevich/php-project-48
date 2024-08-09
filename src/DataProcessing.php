<?php

namespace Differ\Differ\DataProcessing;

use Functional;

/**
 * @param array<mixed>$firstJsonData
 * @param array<mixed>$secondJsonData
 * @param int$deipth
 * @param array<string>$path
 * @return array<mixed>$result
 */
function dataMerge(array $firstJsonData, array $secondJsonData, int $deipth = 1, array $path = []): array
{
    $keys = array_unique(array_merge(array_keys($firstJsonData), array_keys($secondJsonData)));
    $sortKeys = Functional\sort($keys, fn($left, $right) => strcmp($left, $right));
    return array_map(function ($key) use ($firstJsonData, $secondJsonData) {
        if (array_key_exists($key, $firstJsonData) and !array_key_exists($key, $secondJsonData)) {
            return ['key' => $key, 'status' => '-', 'value' => $firstJsonData[$key]];
        } elseif (!array_key_exists($key, $firstJsonData) and array_key_exists($key, $secondJsonData)) {
            return ['key' => $key, 'status' => '+', 'value' => $secondJsonData[$key]];
        } elseif (array_key_exists($key, $firstJsonData) and array_key_exists($key, $secondJsonData)) {
            if (is_array($firstJsonData[$key]) and is_array($secondJsonData[$key])) {
                return ['key' => $key, 'status' => 'no', 'value' => dataMerge($firstJsonData[$key], $secondJsonData[$key])];
            } elseif ($firstJsonData[$key] === $secondJsonData[$key]) {
                return ['key' => $key, 'status' => 'both', 'value' => $firstJsonData[$key]];
            } else {
                return ['key' => $key, 'status' => 'complex', 'value' => ['old' => $firstJsonData[$key], 'new' => $secondJsonData[$key]]];
            }
        }
    }, $sortKeys);
}
