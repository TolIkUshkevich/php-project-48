<?php

namespace Tests\GendiffTest;

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use function Differ\Differ\gendiff;

class GenDiffTest extends TestCase
{
    public function testGendiff(): void
    {
        $expected = file_get_contents(__DIR__ . '/fixtures/firstTestExpectation.txt');

        $actual = gendiff(
            './tests/fixtures/first.json',
            'tests/fixtures/second.json',
            'stylish'
        );

        $this->assertEquals(
            $expected,
            $actual
        );

        $expected2 = file_get_contents(__DIR__ . '/fixtures/secondTestExpectations.txt');

        $actual2 = gendiff(
            './tests/fixtures/first.json',
            './tests/fixtures/second.json',
            'plain'
        );

        $this->assertEquals(
            $expected2,
            $actual2
        );

        $expected3 = file_get_contents(__DIR__ . '/fixtures/thirdTestExpectations.txt');

        $actual3 = gendiff(
            'tests/fixtures/first.yml',
            'tests/fixtures/second.yaml'
        );

        $this->assertEquals($expected3, $actual3);
    }
}
