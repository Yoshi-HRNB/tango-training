<?php
/**
 * login.php
 * ユーザーがログインする画面。
 * 成功すればセッションに user_id を格納してトップへ遷移。
 */

session_start();

// エラーログを表示するための設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// すでにログイン済みならトップへリダイレクト
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームから送信されたメールアドレスとパスワードを取得
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'メールアドレスとパスワードを入力してください。';
    } else {
        // Authクラスで認証を行う
        require_once __DIR__ . '/../src/Database.php';
        require_once __DIR__ . '/../src/Auth.php';

        $db = new \TangoTraining\Database();
        $auth = new \TangoTraining\Auth($db);

        // ログイン試行
        $user = $auth->login($email, $password);

        if ($user !== false) {
            // 認証成功 → セッションにユーザー情報を格納
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['name']; // 参考: 表示用
            $_SESSION['user_role'] = $user['role']; // 参考: 権限管理用
            header('Location: index.php');
            exit;
        } else {
            $error = 'メールアドレスかパスワードが違います。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログイン - Tango Training</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
  <h1>ログイン</h1>

  <?php if ($error): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php endif; ?>

  <form method="post">
    <div>
      <label>メールアドレス:</label><br>
      <input type="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required>
    </div>
    <br>
    <div>
      <label>パスワード:</label><br>
      <input type="password" name="password" required>
    </div>
    <br>
    <button type="submit">ログイン</button>
  </form>

  <p><a href="index.php">トップに戻る</a></p>
</div>

</body>
</html>
