<?php
namespace TangoTraining\Tests;

use PHPUnit\Framework\TestCase;
use TangoTraining\Database;
use TangoTraining\WordController;

/**
 * WordControllerのテスト例
 */
class WordControllerTest extends TestCase
{
    /** @var WordController */
    private $wc;

    protected function setUp(): void
    {
        $db = new Database();
        $this->wc = new WordController($db);
    }

    public function testCreateAndFetchWord()
    {
        // 1. 新規作成
        $created = $this->wc->createWord('en', 'hello_test', 'こんにちは_test');
        $this->assertTrue($created, '単語作成に失敗');

        // 2. 検索
        $words = $this->wc->getWords('hello_test');
        $this->assertNotEmpty($words, '作成した単語が見つからない');
        $this->assertEquals('hello_test', $words[0]['word']);
    }
}
