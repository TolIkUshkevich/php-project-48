<?php

namespace Parsers\FileParser;

use function Parsers\JsonParser\parseJsonFile;
use function Parsers\YamlParser\parseYamlFile;

/**
 * @param string $filePath
 * @return array<mixed>
 */
function defineFormatAndParseFile (string $filePath){
    if (boolval(preg_match('/\w+\.json/', $filePath))) {
        return parseJsonFile($filePath);
    } elseif (boolval(preg_match('/\w+\.yml/', $filePath)) or boolval(preg_match('/\w+\.yaml/', $filePath))) {
        return parseYamlFile($filePath);
    } else {
        return [];
    }
}
