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
      --error-color: #f44336;
    }
    
    body {
      font-family: 'Noto Sans JP', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      margin: 0;
      padding: 0;
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .container {
      width: 100%;
      max-width: 500px;
      padding: 20px;
    }
    
    .login-card {
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 30px;
      margin-bottom: 20px;
    }
    
    .app-logo {
      text-align: center;
      margin-bottom: 25px;
    }
    
    .app-logo img {
      height: 60px;
      width: auto;
    }
    
    .login-title {
      color: var(--primary-color);
      font-size: 24px;
      text-align: center;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .login-title i {
      margin-right: 10px;
      color: var(--primary-color);
    }
    
    .form-group {
      margin-bottom: 20px;
      position: relative;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: var(--text-color);
    }
    
    .form-group .input-with-icon {
      position: relative;
    }
    
    .form-group .input-with-icon i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #999;
    }
    
    .form-control {
      width: 100%;
      padding: 12px 12px 12px 40px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-size: 16px;
      box-sizing: border-box;
      transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary-color);
      outline: none;
      box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.25);
    }
    
    .error-message {
      background-color: rgba(244, 67, 54, 0.1);
      color: var(--error-color);
      padding: 10px 15px;
      border-radius: var(--border-radius);
      margin-bottom: 20px;
      border-left: 4px solid var(--error-color);
      font-size: 14px;
    }
    
    .btn {
      display: block;
      width: 100%;
      padding: 12px 20px;
      background-color: var(--primary-color);
      color: white;
      border: none;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: background-color 0.3s;
      text-align: center;
    }
    
    .btn:hover {
      background-color: var(--secondary-color);
    }
    
    .register-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
    }
    
    .register-link a {
      color: var(--primary-color);
      text-decoration: none;
      transition: color 0.3s;
    }
    
    .register-link a:hover {
      color: var(--secondary-color);
      text-decoration: underline;
    }
    
    .home-link {
      text-align: center;
      margin-top: 10px;
      font-size: 14px;
    }
    
    .home-link a {
      color: #666;
      text-decoration: none;
      transition: color 0.3s;
      display: inline-flex;
      align-items: center;
    }
    
    .home-link a i {
      margin-right: 5px;
    }
    
    .home-link a:hover {
      color: var(--primary-color);
    }
    
    @media (max-width: 576px) {
      .container {
        padding: 15px;
      }
      
      .login-card {
        padding: 20px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <div class="login-card">
    <div class="app-logo">
      <i class="fas fa-language" style="font-size: 60px; color: var(--primary-color);"></i>
    </div>
    <h1 class="login-title">
      <i class="fas fa-sign-in-alt"></i>
      ログイン
    </h1>

    <?php if ($error): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group">
        <label for="email">メールアドレス</label>
        <div class="input-with-icon">
          <i class="fas fa-envelope"></i>
          <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" required placeholder="メールアドレスを入力">
        </div>
      </div>
      
      <div class="form-group">
        <label for="password">パスワード</label>
        <div class="input-with-icon">
          <i class="fas fa-lock"></i>
          <input type="password" class="form-control" id="password" name="password" required placeholder="パスワードを入力">
        </div>
      </div>
      
      <button type="submit" class="btn">
        <i class="fas fa-sign-in-alt"></i> ログイン
      </button>
    </form>
  </div>
  
  <div class="register-link">
    アカウントをお持ちでないですか？ <a href="register.php">新規登録</a>
  </div>
  
  <div class="home-link">
    <a href="index.php">
      <i class="fas fa-home"></i> トップページへ戻る
    </a>
  </div>
</div>

</body>
</html>
