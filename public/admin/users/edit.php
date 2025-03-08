<?php
/**
 * edit.php (ユーザー編集画面)
 * -------------------------
 * 選択したユーザーの名前やロールを変更する(admin専用)
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    exit('権限がありません。');
}

require_once __DIR__ . '/../../../src/Database.php';
require_once __DIR__ . '/../../../src/UserController.php';

$db = new \TangoTraining\Database();
$userCtrl = new \TangoTraining\UserController($db);

$id = (int)($_GET['id'] ?? 0);
$user = $userCtrl->getUserById($id);
if (!$user) {
    // 対象ユーザーがいなければ一覧へ
    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ユーザー編集(管理者)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<div class="container">
  <h1>ユーザー編集 (ID: <?php echo (int)$user['id']; ?>)</h1>
  <form action="update.php" method="post">
    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
    <div>
      <label>メールアドレス:</label><br>
      <!-- メールアドレスはここで編集しない例 （必要なら別途実装） -->
      <input type="text" value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
    </div>
    <br>
    <div>
      <label>名前:</label><br>
      <input type="text" name="name"
             value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <br>
    <div>
      <label>ロール:</label><br>
      <select name="role">
        <!-- 一般ユーザー / 管理者 の2種を想定 -->
        <option value="user" <?php if($user['role']==='user') echo 'selected'; ?>>user</option>
        <option value="admin" <?php if($user['role']==='admin') echo 'selected'; ?>>admin</option>
      </select>
    </div>
    <br>
    <button type="submit">更新</button>
  </form>

  <p><a href="list.php">ユーザー管理一覧へ戻る</a></p>
</div>
</body>
</html>
