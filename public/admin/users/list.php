<?php
/**
 * list.php (ユーザー管理画面)
 * -------------------------
 * すべてのユーザーを表示し、adminが編集や削除を行える。
 */

session_start();

// ログイン必須
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// adminロールのみ許可
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('権限がありません。'); // もしくは 403ページへ
}

require_once __DIR__ . '/../../../src/Database.php';
require_once __DIR__ . '/../../../src/UserController.php';

$db = new \TangoTraining\Database();
$userCtrl = new \TangoTraining\UserController($db);

// 一覧を取得
$users = $userCtrl->getAllUsers();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ユーザー管理(管理者)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<div class="container">
  <h1>ユーザー管理画面 (admin専用)</h1>
  <p><a href="../../index.php">トップに戻る</a></p>
  <table border="1" cellpadding="5" cellspacing="0">
    <tr>
      <th>ID</th>
      <th>メールアドレス</th>
      <th>名前</th>
      <th>ロール</th>
      <th>作成日時</th>
      <th>操作</th>
    </tr>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?php echo (int)$u['id']; ?></td>
        <td><?php echo htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($u['name'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($u['role'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo htmlspecialchars($u['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
        <td>
          <!-- 編集ボタン -->
          <a href="edit.php?id=<?php echo (int)$u['id']; ?>">編集</a>
          <!-- 削除ボタン(確認ダイアログを出す) -->
          <a href="delete.php?id=<?php echo (int)$u['id']; ?>"
             onclick="return confirm('このユーザーを削除しますか？');">
             削除
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
</body>
</html>
