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
    foreach ([$firstPath, $secondPath] as $path) {
        if (preg_match('/\w+\.json/', $path)) {
            $firstFileData = parseJsonFile($firstPath);
            $secondFileData = parseJsonFile($secondPath);
            }
        elseif (preg_match('/\w+\.yml/', $path) or preg_match('/\w+\.yaml/', $path)) {
            $firstFileData = parseYamlFile($firstPath);
            $secondFileData = parseYamlFile($secondPath);
            }
        }
    $result = dataMerge($firstFileData, $secondFileData);
    $result = formatingData($result, $firstFileData, $secondFileData);
    return $result;
}
