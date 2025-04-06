<?php
/**
 * start_test.php
 *
 * テストを新規開始するときに呼ばれるスクリプト。
 * - testsテーブルに1件INSERTし、test_idを取得
 * - sessionにtest_id, branch_idなどを保存
 * - その後、reveal_test.php 等のテスト画面へリダイレクト
 */

// エラー表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

use TangoTraining\Database;

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


// セッションから test_typeを取得
$testType = '2'; // 単語帳形式に固定
$limit    = $_SESSION['test_limit'] ?? 5;
$userId   = (int)$_SESSION['user_id'];

// DB接続
$db = new Database();
$pdo = $db->getConnection();


// testsテーブルに1件insert
// branch_id=1 (初回分岐)
$sql = "INSERT INTO tests
        (test_type, user_id, attempt_date, branch_id, score, total_questions, created_at, updated_at)
        VALUES
        (:test_type, :user_id, NOW(), 1, 0, 0, NOW(), NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':test_type' => $testType,
    ':user_id'   => $userId
]);
$newTestId = (int)$pdo->lastInsertId();

// 前回の test_id を last_test_id に設定
if (isset($_SESSION['current_test_id'])) {
    $_SESSION['last_test_id'] = $_SESSION['current_test_id'];
}
$_SESSION['current_test_id'] = $newTestId;

// セッションに情報を保存
$_SESSION['test_id']   = $newTestId;
$_SESSION['branch_id'] = 0;
$_SESSION['test_limit']= $limit;     // 出題数
$_SESSION['test_type'] = $testType;

// is_retry_testフラグ等を初期化
$_SESSION['is_retry_test'] = false;

// 常に単語帳形式（reveal_test.php）に遷移
header('Location: reveal_test.php');
exit;
?>
