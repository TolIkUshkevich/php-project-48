<?php

namespace Tests\GendiffTest;

require_once __DIR__ . '/autoload.php';

use PHPUnit\Framework\TestCase;
use function Differ\Differ\gendiff;

class GenDiffTest extends TestCase
{
    public function testGendiff(): void
    {
        $expected = '{
    common: {
      + follow: false
        setting1: Value 1
      - setting2: 200
      - setting3: true
      + setting3: null
      + setting4: blah blah
      + setting5: {
            key5: value5
        }
        setting6: {
            doge: {
              - wow: 
              + wow: so much
            }
            key: value
          + ops: vops
        }
    }
    group1: {
      - baz: bas
      + baz: bars
        foo: bar
      - nest: {
            key: value
        }
      + nest: str
    }
  - group2: {
        abc: 12345
        deep: {
            id: 45
        }
    }
  + group3: {
        deep: {
            id: {
                number: 45
            }
        }
        fee: 100500
    }
}';

        $actual = gendiff(
            './tests/fixtures/first.json',
            '/Users/user/projects/php-project-48/tests/fixtures/second.json',
            'stylish'
        );

        $this->assertEquals(
            $expected,
            $actual
        );

        $expected2 = "Property 'common.follow' was added with value: false
Property 'common.setting2' was removed
Property 'common.setting3' was updated. From true to null
Property 'common.setting4' was added with value: 'blah blah'
Property 'common.setting5' was added with value: [complex value]
Property 'common.setting6.doge.wow' was updated. From '' to 'so much'
Property 'common.setting6.ops' was added with value: 'vops'
Property 'group1.baz' was updated. From 'bas' to 'bars'
Property 'group1.nest' was updated. From [complex value] to 'str'
Property 'group2' was removed
Property 'group3' was added with value: [complex value]";

        $actual2 = gendiff(
            './tests/fixtures/first.json',
            './tests/fixtures/second.json',
            'plain'
        );

        $this->assertEquals(
            $expected2,
            $actual2
        );

        $expected3 = '{"common":{"follow":false,"setting1":"Value 1","setting3":null,"setting4":"blah blah","setting5":{"key5":"value5"},"setting6":{"key":"value","ops":"vops","doge":{"wow":"so much"}}},"group1":{"foo":"bar","baz":"bars","nest":"str"},"group2":{"abc":12345,"deep":{"id":45}},"group3":{"deep":{"id":{"number":45}},"fee":100500}}';

        $actual3 = gendiff(
            './tests/fixtures/first.json',
            './tests/fixtures/second.json',
            'json'
        );

        $this->assertEquals(
            $expected3,
            $actual3
        );

        $expected4 = "{
  + addition: false
  + asd: no
  - common: {
        asd: 1
        lad: 2
    }
  + name: nvidia
  + tytle: 1040
}";

    $actual4 = gendiff(
        'tests/fixtures/first.yml',
        '/Users/user/projects/php-project-48/tests/fixtures/second.yaml'
    );

    $this->assertEquals($expected4, $actual4);
    }
}
