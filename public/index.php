<?php
/**
 * index.php
 * トップページ。
 * ログイン状態やメニューへのリンクなどを表示。
 */

// エラー表示を有効化（デバッグ用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 初期化処理を try-catch で囲む
try {
    require_once __DIR__ . '/../src/init.php';
} catch (Exception $e) {
    echo '<pre>エラーが発生しました: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . '</pre>';
    // エラーを表示しながらも処理は続行
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>Tango Training - Home</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
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
    }
    
    body {
      font-family: 'Noto Sans JP', sans-serif;
      background-color: var(--bg-color);
      color: var(--text-color);
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }
    
    header {
      padding: 20px 0;
      text-align: center;
    }
    
    .site-title {
      color: var(--primary-color);
      font-size: 2.5rem;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .site-description {
      color: var(--text-color);
      font-size: 1.2rem;
      margin-bottom: 20px;
    }
    
    .navbar {
      background-color: var(--primary-color);
      border-radius: var(--border-radius);
      padding: 10px;
      margin-bottom: 30px;
      box-shadow: var(--box-shadow);
    }
    
    .nav-links {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 10px;
    }
    
    .nav-links a {
      color: white;
      text-decoration: none;
      padding: 8px 16px;
      border-radius: var(--border-radius);
      transition: background-color 0.3s;
      font-weight: 500;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
      background-color: var(--secondary-color);
    }
    
    .nav-links a:hover {
      background-color: #1b5e20;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .card {
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 20px;
      box-shadow: var(--box-shadow);
      transition: transform 0.3s;
    }
    
    .card:hover {
      transform: translateY(-5px);
    }
    
    .card-title {
      color: var(--primary-color);
      font-size: 1.5rem;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .card-icon {
      font-size: 2rem;
      color: var(--primary-color);
      margin-bottom: 15px;
    }
    
    .btn {
      display: inline-block;
      background-color: var(--primary-color);
      color: white;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: var(--border-radius);
      transition: background-color 0.3s;
      border: none;
      cursor: pointer;
      font-weight: 500;
      text-align: center;
      margin-top: 15px;
    }
    
    .btn:hover {
      background-color: var(--secondary-color);
    }
    
    .btn-outline {
      background-color: transparent;
      border: 2px solid var(--primary-color);
      color: var(--primary-color);
    }
    
    .btn-outline:hover {
      background-color: var(--primary-color);
      color: white;
    }
    
    .welcome-section {
      background-color: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: var(--box-shadow);
      text-align: center;
    }
    
    .welcome-title {
      color: var(--primary-color);
      font-size: 2rem;
      margin-bottom: 15px;
    }
    
    .stats-box {
      background-color: #e8f5e9;
      border-radius: var(--border-radius);
      padding: 15px;
      margin-top: 20px;
      text-align: center;
    }
    
    .stats-number {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary-color);
    }
    
    footer {
      text-align: center;
      padding: 30px 0;
      margin-top: 30px;
      color: #666;
    }
    
    @media (max-width: 768px) {
      .nav-links {
        flex-direction: column;
      }
      
      .nav-links a {
        display: block;
        text-align: center;
      }
      
      .card-grid {
        grid-template-columns: 1fr;
      }
    }
    
    /* アイコンのスタイル (Font Awesome CDN を使用) */
    .fa {
      margin-right: 5px;
    }
  </style>
  <!-- Font Awesome のCDNでアイコンを使用 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container">
  <header>
    <h1 class="site-title">Tango Training</h1>
    <p class="site-description">効率的な単語学習をサポートするアプリケーション</p>
  </header>
  
  <nav class="navbar">
    <div class="nav-links">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="words/list.php"><i class="fas fa-list"></i> 単語一覧</a>
        <a href="words/add_from_picture.php"><i class="fas fa-plus"></i> 単語を登録</a>
        <a href="tests/test_config.php"><i class="fas fa-tasks"></i> テスト設定</a>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <a href="admin/users/list.php"><i class="fas fa-users-cog"></i> ユーザー管理</a>
        <?php endif; ?>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> ログアウト</a>
      <?php else: ?>
        <a href="login.php"><i class="fas fa-sign-in-alt"></i> ログイン</a>
        <a href="register.php"><i class="fas fa-user-plus"></i> 新規登録</a>
      <?php endif; ?>
    </div>
  </nav>

  <main>
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- ログイン済みユーザー向けコンテンツ -->
      <section class="welcome-section">
        <h2 class="welcome-title">ようこそ、<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'ユーザー', ENT_QUOTES, 'UTF-8'); ?>さん</h2>
        <p>あなたの学習をサポートします。今日も単語学習を始めましょう！</p>
        
        <!-- 仮の統計情報 - 実際はデータベースから取得 -->
        <div class="stats-box">
          <div class="stats-number">0</div>
          <p>登録済み単語数</p>
        </div>
      </section>
      
      <div class="card-grid">
        <div class="card">
          <div class="card-icon"><i class="fas fa-book"></i></div>
          <h3 class="card-title">単語学習</h3>
          <p>新しい単語を追加して、効率的に学習しましょう。</p>
          <a href="words/add_from_picture.php" class="btn">単語を追加</a>
        </div>
        
        <div class="card">
          <div class="card-icon"><i class="fas fa-tasks"></i></div>
          <h3 class="card-title">テスト・復習</h3>
          <p>登録した単語をテストして、記憶を定着させましょう。</p>
          <a href="tests/test_config.php" class="btn">テストを開始</a>
        </div>
        
        <div class="card">
          <div class="card-icon"><i class="fas fa-chart-line"></i></div>
          <h3 class="card-title">学習状況</h3>
          <p>あなたの学習の進捗を確認しましょう。</p>
          <a href="tests/summary.php" class="btn">学習履歴を見る</a>
        </div>
      </div>
      
    <?php else: ?>
      <!-- 未ログインユーザー向けコンテンツ -->
      <section class="welcome-section">
        <h2 class="welcome-title">Tango Trainingへようこそ</h2>
        <p>単語学習を効率的にサポートするアプリケーションです。<br>ログインして、あなたの学習をスタートしましょう。</p>
        <div style="margin-top: 20px;">
          <a href="login.php" class="btn">ログイン</a>
          <a href="register.php" class="btn btn-outline" style="margin-left: 10px;">新規登録</a>
        </div>
      </section>
      
      <div class="card-grid">
        <div class="card">
          <div class="card-icon"><i class="fas fa-book"></i></div>
          <h3 class="card-title">効率的な学習</h3>
          <p>自分だけの単語帳を作って、効率よく語彙を増やしましょう。</p>
        </div>
        
        <div class="card">
          <div class="card-icon"><i class="fas fa-sync-alt"></i></div>
          <h3 class="card-title">復習機能</h3>
          <p>間違えた単語を優先的に復習できるので、効果的に記憶が定着します。</p>
        </div>
        
        <div class="card">
          <div class="card-icon"><i class="fas fa-mobile-alt"></i></div>
          <h3 class="card-title">いつでもどこでも</h3>
          <p>スマートフォンにも対応しているので、通勤中や移動中にも学習できます。</p>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <footer>
    <p>&copy; 2024 Tango Training All Rights Reserved.</p>
  </footer>
</div>

</body>
</html>
