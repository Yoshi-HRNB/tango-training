<?php
namespace TangoTraining;

use PDO;
use PDOException;

/**
 * Authクラス
 * ユーザーのログイン/ログアウト認証ロジックをまとめる
 * パスワードハッシュ化やセッション管理を行う
 */
class Auth
{
    /** @var \PDO PDOインスタンス */
    private $pdo;

    /**
     * コンストラクタ
     * DatabaseクラスからPDO接続を受け取る
     */
    public function __construct(Database $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * 新規ユーザー登録（任意）
     * メールアドレスとプレーンパスワードを受け取り、ハッシュ化してINSERT
     * @param string $email
     * @param string $password
     * @param string $name
     * @return bool 登録成功ならtrue
     */
    public function registerUser(string $email, string $password, string $name): bool
    {
        // password_hash() で安全にハッシュ化
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (email, password, name, created_at, updated_at)
                VALUES (:email, :password, :name, NOW(), NOW())";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', $hashed);
            $stmt->bindValue(':name', $name);
            return $stmt->execute();
        } catch (PDOException $e) {
            // 重複エラー等が考えられる
            return false;
        }
    }

    /**
     * ログイン処理
     * メールアドレスと生パスワードから認証を試みる
     * @param string $email
     * @param string $password
     * @return array|false 認証成功ならユーザー情報配列、失敗ならfalse
     */
    public function login(string $email, string $password)
    {
        $sql = "SELECT id, email, password, name, role FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // password_verify でハッシュ照合
            if (password_verify($password, $user['password'])) {
                return $user; // 認証成功 → ユーザー情報を返す
            }
        }
        return false; // 認証失敗
    }

    /**
     * ログアウト処理 (セッション破棄)
     */
    public static function logout()
    {
        // セッション変数をクリアして破棄
        $_SESSION = [];
        session_destroy();
    }
}
