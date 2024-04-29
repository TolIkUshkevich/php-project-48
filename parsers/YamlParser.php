<?php

namespace Parsers\YamlParser;

use Symfony\Component\Yaml\Yaml;

function parseYamlFile($filePath) {
    return Yaml::parse(file_get_contents($filePath));
}