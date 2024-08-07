<?php

namespace Parsers\YamlParser;

use Symfony\Component\Yaml\Yaml;

/**
 * @param string $filePath
 * @return array<mixed>
 */
function parseYamlFile(string $filePath): array
{
    return Yaml::parse((string)file_get_contents($filePath));
}
