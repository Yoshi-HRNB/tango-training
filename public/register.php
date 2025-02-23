<?php
/**
 * register.php
 * ユーザー登録画面（任意機能）。
 * 成功するとログイン画面へ飛ばす例。
 */
session_start();

// 既にログインしている場合はトップへ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$email = '';
$name = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');

    if ($email === '' || $name === '' || $password === '' || $password2 === '') {
        $message = 'すべての項目を入力してください。';
    } elseif ($password !== $password2) {
        $message = 'パスワードが一致しません。';
    } else {
        require_once __DIR__ . '/../src/Database.php';
        require_once __DIR__ . '/../src/Auth.php';

        $db = new \TangoTraining\Database();
        $auth = new \TangoTraining\Auth($db);

        $result = $auth->registerUser($email, $password, $name);
        if ($result) {
            // 登録成功
            header('Location: login.php');
            exit;
        } else {
            $message = '登録に失敗しました。すでに同じメールアドレスが存在する可能性があります。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ユーザー登録</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
  <h1>ユーザー登録</h1>

  <?php if ($message): ?>
    <p style="color:red;"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php endif; ?>

  <form method="post">
    <div>
      <label>メールアドレス:</label><br>
      <input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <div>
      <label>名前:</label><br>
      <input type="text" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <div>
      <label>パスワード:</label><br>
      <input type="password" name="password" required>
    </div>
    <div>
      <label>パスワード再入力:</label><br>
      <input type="password" name="password2" required>
    </div>
    <br>
    <button type="submit">登録</button>
  </form>

  <p><a href="login.php">→ ログイン画面に戻る</a></p>
</div>
</body>
</html>
