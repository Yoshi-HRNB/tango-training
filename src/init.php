<?php
/**
 * init.php
 * アプリケーションの初期化処理
 * - セッションの開始
 * - RememberMe クッキーによる自動ログイン
 * - 必要なクラスの読み込み
 */

// エラー表示の設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // セッション設定（必要に応じてカスタマイズ）
    ini_set('session.cookie_httponly', 1); // HttpOnly属性を強制
    ini_set('session.use_strict_mode', 1); // セッションIDの厳格モード
    ini_set('session.use_trans_sid', 0);   // URLへのセッションID埋め込みを無効化
    session_name('TANGO_SESSID'); // 固有のセッション名

    // すでにセッションが開始されていなければ開始
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // 依存ファイルの読み込み
    require_once __DIR__ . '/Database.php';
    require_once __DIR__ . '/Auth.php';

    // ログイン済みでない場合、RememberMe クッキーをチェック
    if (!isset($_SESSION['user_id'])) {
        // トークンベースの自動ログイン試行
        // データベースエラーなどがあっても致命的なエラーにならないようにする
        try {
            $db = new \TangoTraining\Database();
            $auth = new \TangoTraining\Auth($db);
            // 自動ログインを試みる
            $auth->loginWithToken();
        } catch (Exception $e) {
            // エラーがあってもサイレントに失敗させる（ログ記録は内部的に行う）
            error_log('自動ログイン処理に失敗: ' . $e->getMessage());
        }
    }
} catch (Exception $e) {
    error_log('初期化処理エラー: ' . $e->getMessage());
    // 基本的な初期化だけは行う
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// ログアウトメッセージやその他のフラッシュメッセージを表示するヘルパー関数など（必要に応じて）
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info'; // デフォルトは info
        echo "<div class='flash-message flash-{$type}'>" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "</div>";
        // メッセージを削除
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}
?> 