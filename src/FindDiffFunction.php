<?php

namespace App\FindDiff;

require_once __DIR__ . "/autoload.php";

use function Parsers\JsonParser\parseJsonFile;
use function Parsers\YamlParser\parseYamlFile;
use function App\DataProcessing\dataMerge;
use function App\DataProcessing\formatingData;

/**
 * @param  string $firstPath
 * @param  string $secondPath
 * @return string
 */
function genDiff(string $firstPath, string $secondPath): string
{
    if (preg_match('/\w+\.json/', $firstPath)) {
        $firstFileData = parseJsonFile($firstPath);
    }
    elseif (preg_match('/\w+\.yml/', $firstPath) or preg_match('/\w+\.yaml/', $firstPath)) {
        $firstFileData = parseYamlFile($firstPath);
    }
    if (preg_match('/\w+\.json/', $secondPath)) {
        $secondFileData = parseJsonFile($secondPath);
    }
    elseif (preg_match('/\w+\.yml/', $secondPath) or preg_match('/\w+\.yaml/', $secondPath)) {
        $secondFileData = parseYamlFile($secondPath);
    }
    $result = dataMerge($firstFileData, $secondFileData);
    $result = formatingData($result, $firstFileData, $secondFileData);
    return $result;
}
