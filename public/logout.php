<?php
/**
 * logout.php
 * ログアウト処理。セッションを破棄して確認メッセージを表示。
 */

// init.php を読み込む (セッション設定などを含む)
require_once __DIR__ . '/../src/init.php';

// // エラー表示 // init.php で設定済みのため不要
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// session_start(); // init.php で開始されるため不要

// AuthクラスとDatabaseクラスを読み込む (init.php で読み込み済みの場合があるが念のため)
// require_once __DIR__ . '/../src/Database.php'; // init.phpで読み込み済み
// require_once __DIR__ . '/../src/Auth.php'; // init.phpで読み込み済み

// ログアウト処理（Authクラスを利用）
$wasLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'ユーザー';

// DB接続を取得
// $db 変数は init.php で生成されているか確認が必要。
// Auth::logout は static メソッドなので、DB接続は logout メソッド内で生成される。
// そのため、ここではDB接続の取得は不要。

try {
    // $db = new \TangoTraining\Database(); // Auth::logout 内で new するので不要
    
    // Auth::logout を呼び出してセッションとRememberMeトークンを処理
    // Auth クラスは init.php で読み込まれているはず
    \TangoTraining\Auth::logout(new \TangoTraining\Database()); // logout 内で DB 接続が再度 new される
} catch (Exception $e) {
    // エラーの場合は少なくともセッションをクリア
    error_log("ログアウト処理エラー: " . $e->getMessage());
    $_SESSION = [];
    session_destroy();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>ログアウト - Tango Training</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #4CAF50;
      --secondary-color: #2E7D32;
      --accent-color: #FFC107;
      --text-color: #333;
      --bg-color: #f5f5f5;
      --card-bg: #fff;
      --border-radius: 8px;
      --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --success-color: #4CAF50;
    }
    
    body {
      font-family: 'Noto Sans JP', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      margin: 0;
      padding: 0;
      line-height: 1.6;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .container {
      width: 100%;
      max-width: 500px;
      padding: 20px;
    }
    
    .logout-card {
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 30px;
      text-align: center;
    }
    
    .logout-icon {
      font-size: 60px;
      color: var(--success-color);
      margin-bottom: 20px;
    }
    
    .logout-title {
      color: var(--primary-color);
      font-size: 24px;
      margin-bottom: 16px;
    }
    
    .logout-message {
      color: #666;
      font-size: 16px;
      margin-bottom: 30px;
    }
    
    .btn {
      display: inline-block;
      padding: 12px 24px;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
      text-decoration: none;
    }
    
    .btn:hover {
      background-color: var(--secondary-color);
    }
    
    .countdown {
      font-size: 14px;
      color: #666;
      margin-top: 20px;
    }
  </style>
</head>
<body>

<div class="container">
  <div class="logout-card">
    <div class="logout-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    
    <h1 class="logout-title">ログアウトしました</h1>
    <p class="logout-message">
      <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>さん、ご利用ありがとうございました。<br>
      またのご利用をお待ちしております。
    </p>
    
    <a href="index.php" class="btn">
      <i class="fas fa-home"></i> トップページへ戻る
    </a>
    
    <div class="countdown" id="redirect-countdown">
      <span id="countdown-seconds">5</span>秒後に自動的にトップページへ移動します...
    </div>
  </div>
</div>

<script>
  // 5秒後に自動的にトップページへリダイレクト
  let seconds = 5;
  const countdownElement = document.getElementById('countdown-seconds');
  
  const countdown = setInterval(() => {
    seconds--;
    countdownElement.textContent = seconds;
    
    if (seconds <= 0) {
      clearInterval(countdown);
      window.location.href = 'index.php';
    }
  }, 1000);
</script>

</body>
</html>
