<?php
// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

header('Content-Type: application/json');

// パラメータチェック
if (!isset($_POST['word_id'], $_POST['result'])) {
    echo json_encode(['error' => 'パラメータ不足です。']);
    exit;
}
$wordId = (int)$_POST['word_id'];
$result = $_POST['result'];

// "known" または "unknown" のみ許可
$allowed = ['known','unknown'];
if (!in_array($result, $allowed, true)) {
    echo json_encode(['error'=>'不正な結果タイプです。']);
    exit;
}

// セッションに学習結果を保存
if (!isset($_SESSION['learn_results'])) {
    $_SESSION['learn_results'] = [];
}
$_SESSION['learn_results'][$wordId] = $result;

echo json_encode(['success' => true]);
?>

