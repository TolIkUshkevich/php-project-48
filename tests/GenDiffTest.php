<?php

namespace Tests\GendiffTest;

require_once __DIR__ . '/autoload.php';

use PHPUnit\Framework\TestCase;
use function App\Finddiff\gendiff;

class GenDiffTest extends TestCase
{
    public function testGendiff(): void
    {
        $expected = '{
  + aaa: huq
  - follow: false
    host: hexlet.io
  - proxy: 123.234.53.22
  - timeout: 50
  + timeout: 20
  + verbose: true
  - zzz: true
}';
        $actual = gendiff(
            'tests/fixtures/first.json',
            '/Users/tolya/projects/php-project-48/tests/fixtures/second.json'
        );
        $this->assertEquals(
            $expected,
            $actual
        );

        $expected = '{
  + aaa: huq
  - addition: asd
  + host: hexlet.io
  - name: nvidia
  + timeout: 20
  - tytle: 4080
  + verbose: true
}';
        $actual = gendiff(
            './tests/fixtures/first.yml',
            './tests/fixtures/second.json'
        );
        $this->assertEquals(
            $expected,
            $actual
        );
    }
}
