<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * list.php
 * 単語一覧を表示するページ。
 */
// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

// ログイン必須
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/WordController.php';
require_once __DIR__ . '/../../src/LanguageCode.php';

use TangoTraining\LanguageCode;

$db = new \TangoTraining\Database();
$wordController = new \TangoTraining\WordController($db);

// 利用可能な翻訳言語を取得
$availableLanguages = $wordController->getAvailableTranslationLanguages();

// 検索パラメータ取得
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedLanguages = isset($_GET['translation_languages']) ? $_GET['translation_languages'] : [];

// 新しいフィルターパラメータを取得
$testStatus = isset($_GET['test_status']) ? $_GET['test_status'] : '';
$accuracyRange = isset($_GET['accuracy_range']) ? $_GET['accuracy_range'] : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$partOfSpeech = isset($_GET['part_of_speech']) ? $_GET['part_of_speech'] : '';

// サニタイズ
$searchSanitized = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
$selectedLanguagesSanitized = array_map(function($lang) {
    return htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
}, $selectedLanguages);

// セッションからユーザーIDを取得
$user_id = $_SESSION['user_id'];

// テストフィルター条件を構築
$testFilter = [];

// 正解率フィルター
if ($accuracyRange === 'high') {
    $testFilter['min_accuracy_rate'] = 80;
} elseif ($accuracyRange === 'medium') {
    $testFilter['min_accuracy_rate'] = 50;
    $testFilter['max_accuracy_rate'] = 79;
} elseif ($accuracyRange === 'low') {
    $testFilter['max_accuracy_rate'] = 49;
}

// テスト状況フィルター
if ($testStatus === 'tested') {
    $testFilter['has_been_tested'] = true;
} elseif ($testStatus === 'not_tested') {
    $testFilter['not_tested'] = true;
}

// 日付範囲フィルター
if ($dateRange === 'last_week') {
    $testFilter['registration_start_date'] = date('Y-m-d', strtotime('-7 days'));
} elseif ($dateRange === 'last_month') {
    $testFilter['registration_start_date'] = date('Y-m-d', strtotime('-30 days'));
} elseif ($dateRange === 'last_3months') {
    $testFilter['registration_start_date'] = date('Y-m-d', strtotime('-90 days'));
}

// ソート順フィルター
if (!empty($sortBy)) {
    $testFilter['sort_by'] = $sortBy;
}

// 言語フィルター
$filterLanguages = !empty($selectedLanguages) ? $selectedLanguages : null;

// フィルター条件を指定しつつ単語一覧を取得
$words = $wordController->getWords($user_id, $search, $filterLanguages, $testFilter);

// 単語の統計情報
$totalWords = count($words);
$testedWords = 0;
$notTestedWords = 0;
$highAccuracyWords = 0; // 正解率80%以上
$mediumAccuracyWords = 0; // 正解率50%〜79%
$lowAccuracyWords = 0; // 正解率50%未満

foreach ($words as $word) {
    if (isset($word['test_count']) && $word['test_count'] > 0) {
        $testedWords++;
        
        $accuracyRate = (int)$word['accuracy_rate'];
        if ($accuracyRate >= 80) {
            $highAccuracyWords++;
        } elseif ($accuracyRate >= 50) {
            $mediumAccuracyWords++;
        } else {
            $lowAccuracyWords++;
        }
    } else {
        $notTestedWords++;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>単語一覧 - Multilingual Vocabulary App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <style>
    /* 追加スタイル */
    .dashboard {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .stat-card {
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      text-align: center;
    }
    .stat-card h3 {
      margin-top: 0;
      font-size: 14px;
      color: #555;
    }
    .stat-card .number {
      font-size: 24px;
      font-weight: bold;
      color: #2c3e50;
      margin: 10px 0;
    }
    .stat-card.high-accuracy { border-left: 4px solid #27ae60; }
    .stat-card.medium-accuracy { border-left: 4px solid #f39c12; }
    .stat-card.low-accuracy { border-left: 4px solid #e74c3c; }
    .stat-card.not-tested { border-left: 4px solid #3498db; }
    
    .filter-container {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1rem;
    }
    @media (min-width: 768px) {
      .filter-container {
        grid-template-columns: 1fr 1fr;
      }
    }

    .filter-card {
      background-color: #fff;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .accuracy-bar {
      height: 20px;
      background-color: #f1f1f1;
      border-radius: 10px;
      overflow: hidden;
      margin-bottom: 5px;
    }
    
    .accuracy-value {
      height: 100%;
      text-align: center;
      color: white;
      line-height: 20px;
      font-size: 12px;
      font-weight: bold;
      border-radius: 10px;
    }
    
    .accuracy-high { background-color: #27ae60; }
    .accuracy-medium { background-color: #f39c12; }
    .accuracy-low { background-color: #e74c3c; }
    
    .test-details {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      font-size: 12px;
    }

    .test-details span {
      padding: 2px 5px;
      border-radius: 3px;
      background-color: #f8f9fa;
    }
    
    .translation-list {
      margin: 0;
      padding: 0;
      list-style: none;
    }
    
    .translation-list li {
      margin-bottom: 3px;
    }
    
    .lang-label {
      font-weight: bold;
      color: #3498db;
    }
    
    .tab-container {
      margin-bottom: 1rem;
    }
    
    .tabs {
      display: flex;
      border-bottom: 1px solid #ddd;
      margin-bottom: 1rem;
    }
    
    .tab {
      padding: 8px 16px;
      cursor: pointer;
      border: 1px solid transparent;
      margin-bottom: -1px;
    }
    
    .tab.active {
      border: 1px solid #ddd;
      border-bottom-color: white;
      border-radius: 4px 4px 0 0;
      font-weight: bold;
    }
    
    .table td.notes {
      max-width: none; /* Remove max-width restriction */
      white-space: normal; /* Allow text to wrap */
      overflow: visible; /* Ensure content is not hidden */
      text-overflow: clip; /* Prevent ellipsis */
    }
    
    .filter-section {
      margin-bottom: 1rem;
    }
    
    .filter-section h3 {
      font-size: 16px;
      margin-top: 0;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }
    
    .radio-group, .checkbox-group {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 0.5rem;
    }
    
    .radio-item, .checkbox-item {
      display: flex;
      align-items: center;
      margin-right: 1rem;
    }
    
    .filter-badge {
      display: inline-block;
      padding: 3px 8px;
      margin-right: 5px;
      margin-bottom: 5px;
      background-color: #e9ecef;
      border-radius: 16px;
      font-size: 12px;
    }
    
    .filter-summary {
      margin-bottom: 1rem;
      padding: 0.5rem;
      background-color: #f8f9fa;
      border-radius: 4px;
    }
    
    /* モバイル最適化 */
    @media (max-width: 768px) {
      .filter-section {
        flex-direction: column;
      }

      .form-group {
        width: 100%;
        margin-bottom: 1rem;
      }

      .btn {
        width: 100%;
        margin-bottom: 0.5rem;
      }

      /* ページタイトルと戻るボタンのみ非表示 */
      .page-header h1,
      .page-header .nav-links {
        display: none;
      }

      /* テーブルヘッダー行を非表示 */
      .table thead {
        display: none; /* !important は不要な場合が多い */
        /* visibility: hidden; height: 0; overflow: hidden; も試す価値あり */
      }

      /* レスポンシブテーブルスタイル */
      .table tbody, .table tr, .table td {
        display: block;
        width: 100% !important; /* !importantで上書き */
        box-sizing: border-box; /* パディングを含めた幅計算 */
      }

      .table tr {
        margin-bottom: 1rem; /* 各単語間にスペース */
        border: 1px solid #eee; /* 単語ごとの境界線を明確に */
        border-radius: 4px;
        padding: 0.5rem; /* 内側の余白 */
      }

      .table td {
        text-align: right; /* 値を右寄せ */
        position: relative;
        padding-left: 50%; /* ラベル表示スペース確保 */
        border: none; /* 個々のセルの境界線は不要に */
        padding-top: 5px;
        padding-bottom: 5px;
      }

      .table td::before {
        content: attr(data-label); /* data-label属性の内容を表示 */
        position: absolute;
        left: 6px; /* 左端からの位置 */
        width: 45%; /* ラベル表示幅 */
        padding-right: 10px; /* 値との間隔 */
        white-space: nowrap; /* ラベルは折り返さない */
        text-align: left; /* ラベルを左寄せ */
        font-weight: bold;
        color: #333; /* ラベルの色 */
      }

      /* 単語と翻訳の折り返し */
      .word-cell,
      .word-cell a,
      .translation-list li,
      .notes {
        word-wrap: break-word; /* 古いブラウザ用 */
        overflow-wrap: break-word; /* 標準 */
        white-space: normal; /* 折り返しを許可 */
        text-align: left; /* 単語セルとノートは左寄せに戻す */
        padding-left: 0; /* 左パディングをリセット */
      }

      .word-cell {
        display: flex; /* 音声ボタンと単語を横並び */
        align-items: center;
      }
      
      /* 単語セルのラベル調整 */
      .table td[data-label="単語"]::before,
      .table td[data-label="翻訳"]::before,
      .table td[data-label="補足"]::before,
      .table td[data-label="品詞"]::before {
        width: auto; /* 幅を自動に */
        position: static; /* 通常の配置に */
        display: block; /* ブロック要素にして改行 */
        margin-bottom: 3px; /* 値との間隔 */
        font-weight: bold;
        text-align: left;
      }
      
      .table td[data-label="単語"],
      .table td[data-label="翻訳"],
      .table td[data-label="補足"],
      .table td[data-label="品詞"] {
        padding-left: 6px; /* 左パディングを戻す */
        text-align: left;
      }
      
      /* 品詞セルは削除（上の共通スタイルに統合） */
    }
    
    /* 音声再生ボタンのスタイル */
    .speak-button {
      background-color: #4a6da7;
      color: white;
      border: none;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      margin-right: 8px;
    }
    
    .speak-button:hover {
      background-color: #3a5d97;
      transform: scale(1.1);
    }
    
    .speak-button i {
      font-size: 16px;
    }
    
    .word-cell {
      display: flex;
      align-items: center;
    }
    
    .word-cell i {
      font-size: 16px;
    }
    
    /* 単語リンクのスタイル強調 */
    .word-cell a {
      font-size: 1.5rem;
      font-weight: 600;
      color: #2c3e50;
      text-decoration: none;
      transition: color 0.2s;
    }
    
    .word-cell a:hover {
      color: #3498db;
      text-decoration: underline;
    }
    
    /* テーブルヘッダーの強調 */
    .table th:first-child {
      font-size: 1.1rem;
      background-color: #f1f7fc;
      color: #2c3e50;
    }
    
    /* 単語列のセル強調 */
    .table td:first-child {
      background-color: #f8fbff;
      border-left: 3px solid #3498db;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="page-header">
        <h1>単語一覧</h1>
        
        <div class="nav-links mb-3">
          <a href="add_from_picture.php" class="btn btn-success">単語登録へ</a>
          <a href="../index.php" class="btn btn-secondary">トップへ戻る</a>
        </div>
      </div>
      
      <!-- 単語統計ダッシュボード -->
      <div class="dashboard">
        <div class="stat-card">
          <h3>登録単語数</h3>
          <div class="number"><?php echo $totalWords; ?></div>
        </div>
        <div class="stat-card not-tested">
          <h3>未テスト</h3>
          <div class="number"><?php echo $notTestedWords; ?></div>
        </div>
        <div class="stat-card high-accuracy">
          <h3>高正解率（80%以上）</h3>
          <div class="number"><?php echo $highAccuracyWords; ?></div>
        </div>
        <div class="stat-card medium-accuracy">
          <h3>中正解率（50-79%）</h3>
          <div class="number"><?php echo $mediumAccuracyWords; ?></div>
        </div>
        <div class="stat-card low-accuracy">
          <h3>低正解率（50%未満）</h3>
          <div class="number"><?php echo $lowAccuracyWords; ?></div>
        </div>
      </div>
      
      <!-- 適用中のフィルター表示 -->
      <?php if (!empty($search) || !empty($selectedLanguages) || !empty($testStatus) || !empty($accuracyRange) || !empty($dateRange) || !empty($partOfSpeech)): ?>
      <div class="filter-summary">
        <strong>適用中のフィルター：</strong>
        <?php if (!empty($search)): ?>
          <span class="filter-badge">検索: <?php echo $searchSanitized; ?></span>
        <?php endif; ?>
        
        <?php if (!empty($selectedLanguages)): ?>
          <span class="filter-badge">言語:
            <?php 
              $langNames = [];
              foreach ($selectedLanguages as $code) {
                $langNames[] = LanguageCode::getNameFromCode($code);
              }
              echo implode(', ', $langNames);
            ?>
          </span>
        <?php endif; ?>
        
        <?php if (!empty($testStatus)): ?>
          <span class="filter-badge">テスト状況: 
            <?php echo $testStatus === 'tested' ? 'テスト済み' : '未テスト'; ?>
          </span>
        <?php endif; ?>
        
        <?php if (!empty($accuracyRange)): ?>
          <span class="filter-badge">正解率: 
            <?php 
              if ($accuracyRange === 'high') echo '高（80%以上）';
              elseif ($accuracyRange === 'medium') echo '中（50-79%）';
              elseif ($accuracyRange === 'low') echo '低（50%未満）';
            ?>
          </span>
        <?php endif; ?>
        
        <?php if (!empty($dateRange)): ?>
          <span class="filter-badge">登録期間: 
            <?php 
              if ($dateRange === 'last_week') echo '過去1週間';
              elseif ($dateRange === 'last_month') echo '過去1ヶ月';
              elseif ($dateRange === 'last_3months') echo '過去3ヶ月';
            ?>
          </span>
        <?php endif; ?>
        
        <?php if (!empty($partOfSpeech)): ?>
          <span class="filter-badge">品詞: <?php echo htmlspecialchars($partOfSpeech); ?></span>
        <?php endif; ?>
        
        <a href="list.php" class="btn btn-sm btn-secondary">フィルターをクリア</a>
      </div>
      <?php endif; ?>
      
      <!-- フィルター部分 -->
      <div class="tab-container">
        <div class="tabs">
          <div class="tab active" id="tab-list">単語一覧</div>
          <div class="tab" id="tab-filter">検索・フィルター</div>
        </div>
        
        <div id="filter-panel" style="display: none;">
          <form action="" method="get" class="filter-form">
            <div class="filter-container">
              <!-- 基本検索 -->
              <div class="filter-card">
                <div class="filter-section">
                  <h3>基本検索</h3>
                  <div class="form-group">
                    <label for="search">単語・翻訳検索:</label>
                    <input type="text" name="search" id="search" value="<?php echo $searchSanitized; ?>" placeholder="単語や意味を検索...">
                  </div>
                </div>
                
                <div class="filter-section">
                  <h3>テスト状況</h3>
                  <div class="radio-group">
                    <div class="radio-item">
                      <input type="radio" id="test_status_all" name="test_status" value="" <?php echo $testStatus === '' ? 'checked' : ''; ?>>
                      <label for="test_status_all">すべて</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="test_status_tested" name="test_status" value="tested" <?php echo $testStatus === 'tested' ? 'checked' : ''; ?>>
                      <label for="test_status_tested">テスト済み</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="test_status_not_tested" name="test_status" value="not_tested" <?php echo $testStatus === 'not_tested' ? 'checked' : ''; ?>>
                      <label for="test_status_not_tested">未テスト</label>
                    </div>
                  </div>
                </div>
                
                <div class="filter-section">
                  <h3>正解率</h3>
                  <div class="radio-group">
                    <div class="radio-item">
                      <input type="radio" id="accuracy_all" name="accuracy_range" value="" <?php echo $accuracyRange === '' ? 'checked' : ''; ?>>
                      <label for="accuracy_all">すべて</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="accuracy_high" name="accuracy_range" value="high" <?php echo $accuracyRange === 'high' ? 'checked' : ''; ?>>
                      <label for="accuracy_high">高（80%以上）</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="accuracy_medium" name="accuracy_range" value="medium" <?php echo $accuracyRange === 'medium' ? 'checked' : ''; ?>>
                      <label for="accuracy_medium">中（50-79%）</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="accuracy_low" name="accuracy_range" value="low" <?php echo $accuracyRange === 'low' ? 'checked' : ''; ?>>
                      <label for="accuracy_low">低（50%未満）</label>
                    </div>
                  </div>
                </div>
                
                <div class="filter-section">
                  <h3>登録期間</h3>
                  <div class="radio-group">
                    <div class="radio-item">
                      <input type="radio" id="date_all" name="date_range" value="" <?php echo $dateRange === '' ? 'checked' : ''; ?>>
                      <label for="date_all">すべて</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="date_last_week" name="date_range" value="last_week" <?php echo $dateRange === 'last_week' ? 'checked' : ''; ?>>
                      <label for="date_last_week">過去1週間</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="date_last_month" name="date_range" value="last_month" <?php echo $dateRange === 'last_month' ? 'checked' : ''; ?>>
                      <label for="date_last_month">過去1ヶ月</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="date_last_3months" name="date_range" value="last_3months" <?php echo $dateRange === 'last_3months' ? 'checked' : ''; ?>>
                      <label for="date_last_3months">過去3ヶ月</label>
                    </div>
                  </div>
                </div>
              </div>
            
              <!-- 詳細フィルター -->
              <div class="filter-card">
                <div class="filter-section">
                  <h3>翻訳言語</h3>
                  <div class="form-group">
                    <select name="translation_languages[]" id="translation_languages" multiple size="6">
                      <?php foreach (LanguageCode::getLanguageMap() as $code => $name): ?>
                      <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($code, $selectedLanguages) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                    <small>複数選択：Ctrlキーを押しながらクリック</small>
                  </div>
                </div>
                
                <div class="filter-section">
                  <h3>並び替え</h3>
                  <div class="radio-group">
                    <div class="radio-item">
                      <input type="radio" id="sort_newest" name="sort_by" value="newest" <?php echo $sortBy === 'newest' ? 'checked' : ''; ?>>
                      <label for="sort_newest">新しい順</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="sort_oldest" name="sort_by" value="oldest" <?php echo $sortBy === 'oldest' ? 'checked' : ''; ?>>
                      <label for="sort_oldest">古い順</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="sort_accuracy_asc" name="sort_by" value="accuracy_asc" <?php echo $sortBy === 'accuracy_asc' ? 'checked' : ''; ?>>
                      <label for="sort_accuracy_asc">正解率（低い順）</label>
                    </div>
                    <div class="radio-item">
                      <input type="radio" id="sort_accuracy_desc" name="sort_by" value="accuracy_desc" <?php echo $sortBy === 'accuracy_desc' ? 'checked' : ''; ?>>
                      <label for="sort_accuracy_desc">正解率（高い順）</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="form-actions" style="margin-top: 1rem;">
              <button type="submit" class="btn btn-primary">フィルターを適用</button>
              <a href="list.php" class="btn btn-secondary">リセット</a>
            </div>
          </form>
        </div>
      </div>
      
      <!-- テーブル部分 -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>単語</th>
              <th>品詞</th>
              <th>翻訳</th>
              <th>補足</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($words) > 0): ?>
              <?php foreach ($words as $w): ?>
                <tr>
                  <td data-label="単語">
                    <div class="word-cell">
                      <button class="speak-button" onclick="playPronunciation('<?php echo htmlspecialchars($w['word'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($w['language_code'], ENT_QUOTES, 'UTF-8'); ?>')">
                        <i class="fas fa-volume-up"></i>
                      </button>
                      <div>
                        <a href="edit.php?id=<?php echo (int)$w['word_id']; ?>">
                          <?php echo htmlspecialchars($w['word'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <?php if ($w['language_code'] === 'ja' && !empty($w['reading'])): ?>
                          <br><small class="text-muted"><?php echo htmlspecialchars($w['reading'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <?php endif; ?>
                      </div>
                    </div>
                  </td>
                  <td data-label="品詞"><?php echo htmlspecialchars($w['part_of_speech'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="翻訳" class="translations-cell">
                    <?php 
                    if (!empty($w['translations'])) {
                      $translationParts = explode(', ', $w['translations']);
                      echo '<ul class="translation-list">';
                      foreach ($translationParts as $part) {
                        list($langCode, $translation) = explode(': ', $part, 2);
                        $langName = LanguageCode::getNameFromCode($langCode);
                        echo '<li><span class="lang-label">' . htmlspecialchars($langName, ENT_QUOTES, 'UTF-8') . ':</span> ' . 
                             htmlspecialchars($translation, ENT_QUOTES, 'UTF-8') . '</li>';
                      }
                      echo '</ul>';
                    } else {
                      echo '翻訳なし';
                    }
                    ?>
                  </td>
                  <td data-label="補足" class="notes">
                    <?php if (!empty($w['note'])): ?>
                      <?php echo nl2br(htmlspecialchars($w['note'], ENT_QUOTES, 'UTF-8')); ?>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="4" class="text-center">単語が見つかりません。</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // 言語コード定義
      <?= LanguageCode::getJavaScriptDefinition() ?>
      
      // タブ切り替え
      const tabList = document.getElementById('tab-list');
      const tabFilter = document.getElementById('tab-filter');
      const filterPanel = document.getElementById('filter-panel');
      
      tabList.addEventListener('click', function() {
        tabList.classList.add('active');
        tabFilter.classList.remove('active');
        filterPanel.style.display = 'none';
      });
      
      tabFilter.addEventListener('click', function() {
        tabFilter.classList.add('active');
        tabList.classList.remove('active');
        filterPanel.style.display = 'block';
      });
      
      // フィルターパネルを自動的に表示（URLにフィルターパラメータがある場合）
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('search') || urlParams.has('translation_languages') || 
          urlParams.has('test_status') || urlParams.has('accuracy_range') ||
          urlParams.has('date_range') || urlParams.has('sort_by')) {
        tabFilter.click();
      }
    });
    
    // 音声再生機能の追加
    function playPronunciation(word, languageCode) {
      // 言語コードをGoogle Translate用のコードに変換
      const googleLangCode = languageCode === 'ja' ? 'ja' : 'en';
      
      // サーバーサイドプロキシを利用して音声データを取得
      const audioUrl = `../tts_proxy.php?tl=${googleLangCode}&q=${encodeURIComponent(word)}`;
      
      // デバッグ出力（開発時のみ）
      console.log('音声URL:', audioUrl);
      
      try {
        // 音声を再生
        const audio = new Audio(audioUrl);
        
        // 音声再生前にロードイベントを確認
        audio.addEventListener('canplaythrough', () => {
          console.log('音声ロード完了、再生開始');
        });
        
        // エラーハンドリング
        audio.onerror = function(e) {
          console.error('音声読み込みエラー:', e);
          alert(`音声の再生に失敗しました。`);
        };
        
        // 再生開始
        audio.play().then(() => {
          console.log('音声再生開始');
        }).catch(error => {
          console.error('音声再生APIエラー:', error);
          alert('音声の再生に失敗しました。ブラウザの設定を確認してください。');
        });
      } catch (e) {
        console.error('予期せぬエラー:', e);
        alert('音声再生中に予期せぬエラーが発生しました。');
      }
    }
  </script>
</body>
</html>



