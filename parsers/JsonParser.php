<?php

namespace Parsers\JsonParser;

/**
 * @param string $filepath
 * @return array 
 */
function parseJsonFile(string $filePath): array
{
    $resultData = file_get_contents($filePath, true);
    return json_decode($resultData, true);
}