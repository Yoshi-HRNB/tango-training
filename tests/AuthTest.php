<?php
namespace TangoTraining\Tests;

use PHPUnit\Framework\TestCase;
use TangoTraining\Database;
use TangoTraining\Auth;

/**
 * Authクラスの単体テスト例
 * 実際にDBにユーザーを登録→ログイン確認などを行うにはテスト用DBが必要
 */
class AuthTest extends TestCase
{
    /** @var Auth */
    private $auth;

    protected function setUp(): void
    {
        $db = new Database();
        $this->auth = new Auth($db);
    }

    public function testRegisterAndLogin()
    {
        // テスト用ユーザー情報
        $email = 'testuser@example.com';
        $password = 'testpass';
        $name = 'Test User';

        // ユーザー登録（重複時はfalseになる場合あり）
        $result = $this->auth->registerUser($email, $password, $name);
        $this->assertTrue($result, 'ユーザー登録に失敗');

        // ログインできるか
        $user = $this->auth->login($email, $password);
        $this->assertNotFalse($user, 'ログインに失敗');
        $this->assertEquals($email, $user['email'], 'メールアドレスが一致しない');
    }
}
