<?php

namespace Differ\Differ;

use function Parsers\FileParser\defineFormatAndParseFile;
use function Formaters\stylishFormater\defaultFormating;
use function Formaters\plainFormater\plainFormating;
use function Differ\Differ\DataProcessing\dataMerge;
use function Formaters\plainFormater\addPath;

/**
 * @param  string $firstPath
 * @param  string $secondPath
 * @return string
 */
function genDiff(string $firstPath, string $secondPath, string $format = 'stylish'): string|null|false
{
    $firstFileData = defineFormatAndParseFile($firstPath);
    $secondFileData = defineFormatAndParseFile($secondPath);
    if ($format === 'stylish') {
        $result = dataMerge($firstFileData, $secondFileData);
        return "{" . implode('', defaultFormating($result)) . "\n" . "}";
    } elseif ($format === 'plain') {
        $result = dataMerge($firstFileData, $secondFileData);
        return plainFormating(addPath($result));
    } elseif ($format === 'json') {
        $result = array_merge($firstFileData, $secondFileData);
        return json_encode($result);
    } else {
        return null;
    }
}
