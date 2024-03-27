<?php

require_once __DIR__ . '/autoload.php';

use PHPUnit\Framework\TestCase;
use function App\Finddiff\gendiff;

class GenDiffTest extends TestCase
{
    public function testGendiff(): void
    {
        $expected ='{
  + aaa: huq
  - follow: false
    host: hexlet.io
  - proxy: 123.234.53.22
  - timeout: 50
  + timeout: 20
  + verbose: 1
  - zzz: true
}';
        $actual = gendiff('tests/fixtures/first.json',
            '/Users/user/projects/php-project-48/tests/fixtures/second.json');
        $this->assertEquals($expected, $actual);
    }
}