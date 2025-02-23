<?php
/**
 * update.php
 * ----------
 * edit.php で入力したユーザー情報を更新する(admin専用)
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('権限がありません。');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if ($id > 0 && $name !== '' && ($role === 'user' || $role === 'admin')) {
        require_once __DIR__ . '/../../../src/Database.php';
        require_once __DIR__ . '/../../../src/UserController.php';
        $db = new \TangoTraining\Database();
        $userCtrl = new \TangoTraining\UserController($db);

        $userCtrl->updateUser($id, $name, $role);
    }
}

header('Location: list.php');
exit;
