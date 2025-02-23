<?php
/**
 * index.php
 * トップページ。
 * ログイン状態やメニューへのリンクなどを表示。
 */

session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Tango Training - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
  <header>
    <h1>Welcome to Tango Training</h1>
    <nav>
      <!-- ログイン状態によってメニューの表示を切り替える例 -->
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="words/list.php">単語一覧</a>
        <a href="words/add.php">単語を登録</a>
        <a href="tests/config.php">テスト設定</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <!-- admin用メニュー -->
          <a href="admin/users/list.php">ユーザー管理</a> |
        <a href="logout.php">ログアウト</a>
        <?php endif; ?>
        <?php else: ?>
        <a href="login.php">ログイン</a> |
        <a href="register.php">新規登録</a>
      <?php endif; ?>
    </nav>
  </header>

  <main>
    <h2>トップページ</h2>
    <?php if (isset($_SESSION['user_id'])): ?>
      <p>ログイン中です。学習を始めましょう！</p>
    <?php else: ?>
      <p>ログインすると単語の登録やテスト機能が利用できます。</p>
    <?php endif; ?>
  </main>

  <footer>
    <hr>
    <small>&copy; 2023 Tango Training</small>
  </footer>
</div>

</body>
</html>
