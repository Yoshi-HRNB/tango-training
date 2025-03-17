<?php
/**
 * test_retry_branch_up.php
 *
 * 再テスト時に、同じtest_idでbranch_idを+1する。
 * フロント(例: reveal_test.php)からfetchで呼び出して、
 * セッションのbranch_idを書き換え、is_retry_test=trueにする。
 */

session_start();
header('Content-Type: application/json');

// セッションチェック
if (!isset($_SESSION['test_id'], $_SESSION['branch_id'])) {
    http_response_code(403);
    echo json_encode(['success'=>false, 'error'=>'Unauthorized']);
    exit;
}

// branch_idを+1
$old_branch_id = (int)$_SESSION['branch_id'];
$_SESSION['branch_id'] = $old_branch_id + 1;

// 再テストフラグをtrueに
$_SESSION['is_retry_test'] = true;

// デバッグ用の情報をレスポンスに含める
echo json_encode([
    'success' => true,
    'debug' => [
        'old_branch_id' => $old_branch_id,
        'new_branch_id' => $_SESSION['branch_id'],
        'test_id' => $_SESSION['test_id'],
        'is_retry_test' => $_SESSION['is_retry_test'],
        'session_keys' => array_keys($_SESSION)
    ]
]);

