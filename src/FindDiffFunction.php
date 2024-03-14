<?php

namespace App\FindDiff;

require_once __DIR__ . "/autoload.php";

use function App\JsonFunctions\jsonMerge;
use function App\JsonFunctions\jsonToString;

function genDiff(string $firstPath, string $secondPath): string
{
    $firstFile = file_get_contents($firstPath, true);
    $secondFile = file_get_contents($secondPath, true);
    $firstJsonData = json_decode($firstFile, true);
    $secondJsonData = json_decode($secondFile, true);
    $result = jsonMerge($firstJsonData, $secondJsonData);
    $result = jsonToString($result, $firstJsonData, $secondJsonData);
    return $result;
}