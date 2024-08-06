<?php

namespace Differ\Differ;

require_once __DIR__ . "/autoload.php";

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
    $firstFileData = [];
    $secondFileData = [];
    $result = null;
    if (preg_match('/\w+\.json/', $firstPath)) {
        $firstFileData = parseJsonFile($firstPath);
    } elseif (preg_match('/\w+\.yml/', $firstPath) or preg_match('/\w+\.yaml/', $firstPath)) {
        $firstFileData = parseYamlFile($firstPath);
    }
    if (preg_match('/\w+\.json/', $secondPath)) {
        $secondFileData = parseJsonFile($secondPath);
    } elseif (preg_match('/\w+\.yml/', $secondPath) or preg_match('/\w+\.yaml/', $secondPath)) {
        $secondFileData = parseYamlFile($secondPath);
    }
    if ($format === 'stylish') {
        $result = dataMerge($firstFileData, $secondFileData);
        $result = "{" . implode('', defaultFormating($result)) . "\n" . "}";
    } elseif ($format === 'plain') {
        $result = dataMerge($firstFileData, $secondFileData);
        $result = plainFormating(addPath($result));
    } elseif ($format === 'json') {
        $result = array_merge($firstFileData, $secondFileData);
        $result = json_encode($result);
    }
    return $result;
}
