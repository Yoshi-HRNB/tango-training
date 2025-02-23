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
$_SESSION['branch_id'] = ((int)$_SESSION['branch_id']) + 1;
// 再テストフラグをtrueに
$_SESSION['is_retry_test'] = true;

echo json_encode(['success'=>true]);

