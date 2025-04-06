<?php
namespace TangoTraining;

use PDO;
use PDOException;
use Exception;

/**
 * Authクラス
 * ユーザーのログイン/ログアウト認証ロジックをまとめる
 * パスワードハッシュ化やセッション管理、RememberMe機能を行う
 */
class Auth
{
    /** @var \PDO PDOインスタンス */
    private $pdo;

    /** クッキー名 */
    public const REMEMBER_ME_COOKIE = 'remember_me_token';
    /** クッキーとトークンの有効期間 (秒) - 例: 30日 */
    private const REMEMBER_ME_EXPIRY = 60 * 60 * 24 * 30;

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
     * RememberMeが有効ならトークンを生成・保存・クッキー設定する
     * @param string $email
     * @param string $password
     * @param bool $rememberMe ログイン状態を保持するかどうか
     * @return array|false 認証成功ならユーザー情報配列、失敗ならfalse
     */
    public function login(string $email, string $password, bool $rememberMe = false)
    {
        $sql = "SELECT user_id, email, password, name, role FROM users WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // password_verify でハッシュ照合
            if (password_verify($password, $user['password'])) {
                // Remember Me が有効な場合
                if ($rememberMe) {
                    list($selector, $validator) = $this->generateToken();
                    if ($this->storeToken($user['user_id'], $selector, $validator)) {
                        $this->setRememberMeCookie($selector, $validator);
                    } else {
                        // トークン保存失敗時のエラーハンドリング（ログ記録など）
                        error_log("Failed to store remember me token for user: " . $user['user_id']);
                    }
                }
                return $user; // 認証成功 → ユーザー情報を返す
            }
        }
        return false; // 認証失敗
    }

    /**
     * ログアウト処理 (セッション破棄とRememberMeトークン削除)
     */
    public static function logout(Database $db) // DB接続が必要になったため引数追加
    {
        $auth = new self($db); // インスタンスメソッドを呼ぶため

        // RememberMeクッキーとDBトークンを削除
        if (isset($_COOKIE[self::REMEMBER_ME_COOKIE])) {
            $cookieValue = $_COOKIE[self::REMEMBER_ME_COOKIE];
            if (strpos($cookieValue, ':') !== false) {
                list($selector, $validator) = explode(':', $cookieValue, 2);
                if ($selector && $validator) {
                    $deleted = $auth->deleteToken($selector);
                }
            }
            $auth->clearRememberMeCookie();
        }

        // セッション変数をクリアして破棄
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; // Secureフラグ判定追加
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $secure, $params["httponly"] // Secureフラグ適用
            );
        }
        session_destroy();
    }

    /**
     * RememberMeクッキーを検証して自動ログインを試みる
     * 成功すればセッションにユーザー情報を格納する
     * @return bool ログイン成功ならtrue
     */
    public function loginWithToken(): bool
    {
        try {
            // クッキーが存在しない場合は何もしない
            if (!isset($_COOKIE[self::REMEMBER_ME_COOKIE])) {
                return false;
            }
            
            // クッキーからセレクタとバリデータを取得
            $cookie = $_COOKIE[self::REMEMBER_ME_COOKIE];
            if (!$cookie || !strpos($cookie, ':')) {
                $this->clearRememberMeCookie();
                return false;
            }
            
            list($selector, $validator) = explode(':', $cookie, 2);
            
            if (!$selector || !$validator) {
                $this->clearRememberMeCookie(); // 不正なクッキーは削除
                return false;
            }
            
            // データベースからトークン情報を取得
            $userId = $this->validateToken($selector, $validator);
            
            if ($userId === false) {
                $this->clearRememberMeCookie();
                return false;
            }
            
            // トークン有効 → ユーザー情報を取得してセッション開始
            try {
                $sql = "SELECT user_id, email, name, role FROM users WHERE user_id = :user_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    $this->clearRememberMeCookie();
                    return false;
                }
                
                // ユーザー情報をセッションに格納
                if (session_status() == PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true); // セッション固定化対策
                }
                
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // セキュリティ向上：使用済みトークンを削除し、新しいトークンを発行
                $this->deleteToken($selector);
                
                // 新しいトークンを生成・保存・設定
                list($newSelector, $newValidator) = $this->generateToken();
                if ($this->storeToken($user['user_id'], $newSelector, $newValidator)) {
                    $this->setRememberMeCookie($newSelector, $newValidator);
                }
                
                return true;
            } catch (PDOException $e) {
                error_log("Error in loginWithToken: " . $e->getMessage());
                return false;
            }
        } catch (Exception $e) {
            // 何らかの例外が発生した場合でもfalseを返し、ログイン処理は続行
            error_log("Exception in loginWithToken: " . $e->getMessage());
            return false;
        }
    }

    // --- Remember Me Helper Methods ---

    /**
     * 安全なトークン（セレクタとバリデータ）を生成する
     * @return array [selector, validator]
     */
    private function generateToken(): array
    {
        $selector = bin2hex(random_bytes(16)); // 128ビット (16バイト)
        $validator = bin2hex(random_bytes(32)); // 256ビット (32バイト)
        return [$selector, $validator];
    }

    /**
     * トークン情報をデータベースに保存する
     * @param int $userId
     * @param string $selector
     * @param string $validator
     * @return bool 成功ならtrue
     */
    private function storeToken(int $userId, string $selector, string $validator): bool
    {
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
        $expires = date('Y-m-d H:i:s', time() + self::REMEMBER_ME_EXPIRY);

        // ON DUPLICATE KEY UPDATE を使用する前に、テーブルの存在確認を行う
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'user_tokens'");
            if ($check->rowCount() === 0) {
                // テーブルが存在しない場合、エラーログに記録して失敗を返す
                error_log("Error storing token: user_tokens table does not exist");
                return false;
            }
            
            // 単純なINSERT文を使用（テーブルや制約に依存しないように）
            $sql = "INSERT INTO user_tokens (user_id, selector, hashed_validator, expires_at, created_at, updated_at)
                    VALUES (:user_id, :selector, :hashed_validator, :expires, NOW(), NOW())";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':selector', $selector);
            $stmt->bindValue(':hashed_validator', $hashedValidator);
            $stmt->bindValue(':expires', $expires);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error storing token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * セレクタを元にデータベースからトークン情報を検索する
     * @param string $selector
     * @return array|false トークン情報が見つかれば配列、なければfalse
     */
    private function findTokenBySelector(string $selector)
    {
        try {
            // テーブル存在確認
            $check = $this->pdo->query("SHOW TABLES LIKE 'user_tokens'");
            if ($check->rowCount() === 0) {
                // テーブルが存在しない場合は false を返す
                return false;
            }
            
            $sql = "SELECT id, user_id, selector, hashed_validator, expires_at
                    FROM user_tokens
                    WHERE selector = :selector AND expires_at > NOW()"; // 有効期限もチェック
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':selector', $selector);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * データベースからトークンを削除する
     * @param string $selector
     * @return bool 成功ならtrue
     */
    private function deleteToken(string $selector): bool
    {
        // テーブル存在確認を追加
        try {
            $check = $this->pdo->query("SHOW TABLES LIKE 'user_tokens'");
            if ($check->rowCount() === 0) {
                error_log("[DEBUG] Error deleting token: user_tokens table does not exist."); // このエラーログは残しても良いかもしれないが、一旦削除
                return false;
            }
        } catch (PDOException $e) {
            error_log("[DEBUG] Error checking user_tokens table existence: " . $e->getMessage()); // このエラーログは残しても良いかもしれないが、一旦削除
            return false; // テーブル確認でエラーなら削除も失敗とする
        }
        
        $sql = "DELETE FROM user_tokens WHERE selector = :selector";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':selector', $selector);
            $result = $stmt->execute();
            // error_log("[DEBUG] Token deletion execute result for selector " . $selector . ": " . ($result ? 'Success' : 'Failure') . ", Rows affected: " . $rowCount); // 削除
             return $result; // 元の挙動に戻す (rowCount確認はデバッグ用だったため)
        } catch (PDOException $e) {
            error_log("Error deleting token for selector " . $selector . ": " . $e->getMessage()); // [DEBUG] を削除し、通常のエラーログに戻す
            return false;
        }
    }

    /**
     * クッキーから受け取ったセレクタとバリデータを検証する
     * @param string $selector
     * @param string $validator
     * @return int|false 有効ならユーザーID、無効ならfalse
     */
    private function validateToken(string $selector, string $validator)
    {
        $tokenData = $this->findTokenBySelector($selector);

        if ($tokenData && password_verify($validator, $tokenData['hashed_validator'])) {
            return (int)$tokenData['user_id'];
        }
        return false;
    }

    /**
     * RememberMeクッキーを設定する
     * @param string $selector
     * @param string $validator
     */
    private function setRememberMeCookie(string $selector, string $validator): void
    {
        $value = $selector . ':' . $validator;
        $expiry = time() + self::REMEMBER_ME_EXPIRY;

        // HttpOnly と Secure フラグを設定（本番環境では Secure=true を推奨）
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'; // HTTPS接続か判定
        setcookie(self::REMEMBER_ME_COOKIE, $value, $expiry, '/', '', $secure, true);
    }

    /**
     * RememberMeクッキーを削除する
     */
    private function clearRememberMeCookie(): void
    {
        // Secure フラグを setRememberMeCookie と同様に判定
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        // 過去の時刻を設定してクッキーを無効化
        setcookie(self::REMEMBER_ME_COOKIE, '', time() - 3600, '/', '', $secure, true); // $secure を使用
        //念のためクッキー配列からも削除
        unset($_COOKIE[self::REMEMBER_ME_COOKIE]);
    }
}
