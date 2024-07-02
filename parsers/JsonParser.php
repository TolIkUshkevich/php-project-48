<?php

namespace Parsers\JsonParser;

/**
 * @param string $filePath
 * @return array<mixed>
 */
function parseJsonFile(string $filePath): array
{
    $resultData = file_get_contents($filePath, true);
    return json_decode($resultData, true);
}
