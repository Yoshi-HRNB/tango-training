<?php
/**
 * delete.php
 * ----------
 * 指定したユーザーを削除する(admin専用)
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('権限がありません。');
}

// idをGETで受け取り削除
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    require_once __DIR__ . '/../../../src/Database.php';
    require_once __DIR__ . '/../../../src/UserController.php';
    $db = new \TangoTraining\Database();
    $userCtrl = new \TangoTraining\UserController($db);
    $userCtrl->deleteUser($id);
}

// 削除完了後は一覧へ
header('Location: list.php');
exit;
