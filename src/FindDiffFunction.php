<?php

namespace Differ\Differ;

use function Parsers\JsonParser\parseJsonFile;
use function Parsers\YamlParser\parseYamlFile;
use function Differ\Differ\DataProcessing\dataMerge;
use function Differ\Differ\Formaters\defaultFormating;
use function Differ\Differ\Formaters\plainFormating;
use function Differ\Differ\Formaters\addPath;

/**
 * @param  string $firstPath
 * @param  string $secondPath
 * @return string
 */
function genDiff(string $firstPath, string $secondPath, string $format = 'stylish'): string|null|false
{
    $result = null;
    if (boolval(preg_match('/\w+\.json/', $firstPath))) {
        $firstFileData = parseJsonFile($firstPath);
    } elseif (boolval(preg_match('/\w+\.yml/', $firstPath)) or boolval(preg_match('/\w+\.yaml/', $firstPath))) {
        $firstFileData = parseYamlFile($firstPath);
    } else {
        $firstFileData = [];
    }
    if (boolval(preg_match('/\w+\.json/', $secondPath))) {
        $secondFileData = parseJsonFile($secondPath);
    } elseif (boolval(preg_match('/\w+\.yml/', $secondPath)) or boolval(preg_match('/\w+\.yaml/', $secondPath))) {
        $secondFileData = parseYamlFile($secondPath);
    } else {
        $secondFileData = [];
    }
    if ($format === 'stylish') {
        $result = dataMerge($firstFileData, $secondFileData);
        return "{" . implode('', defaultFormating($result)) . "\n" . "}";
    } elseif ($format === 'plain') {
        $result = dataMerge($firstFileData, $secondFileData);
        return plainFormating(addPath($result));
    } elseif ($format === 'json') {
        $result = array_merge($firstFileData, $secondFileData);
        return json_encode($result);
    }
    return $result;
}
