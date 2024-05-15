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
            '/Users/user/projects/php-project-48/tests/fixtures/second.json'
        );
        $this->assertEquals(
            $expected,
            $actual
        );

//         $expected = '{
//   + aaa: huq
//   - addition: asd
//   + host: hexlet.io
//   - name: nvidia
//   + timeout: 20
//   - tytle: 4080
//   + verbose: true
// }';
//         $actual = gendiff(
//             './tests/fixtures/first.yml',
//             './tests/fixtures/second.json'
//         );
//         $this->assertEquals(
//             $expected,
//             $actual
//         );
    }
}
