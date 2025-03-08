<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * list.php
 * 単語一覧を表示するページ。
 */
session_start();

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

// サニタイズ
$searchSanitized = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
$selectedLanguagesSanitized = array_map(function($lang) {
    return htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
}, $selectedLanguages);

// セッションからユーザーIDを取得
$user_id = $_SESSION['user_id'];

// フィルター条件を指定しつつ単語一覧を取得
$filterLanguages = !empty($selectedLanguages) ? $selectedLanguages : null;
$testFilter = []; // テスト関連のフィルタは別途必要であれば追加

$words = $wordController->getWords($user_id, $search, $filterLanguages);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>単語一覧 - Multilingual Vocabulary App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>単語一覧</h1>
      
      <div class="nav-links mb-3">
        <a href="add_from_picture.php">単語登録へ</a>
        <a href="../index.php">トップへ戻る</a>
      </div>
      
      <form action="" method="get" class="filter-form">
        <div class="filter-section">
          <div class="form-group">
            <label for="search">検索:</label>
            <input type="text" name="search" id="search" value="<?php echo $searchSanitized; ?>" placeholder="単語を検索...">
          </div>
          
          <div class="form-group">
            <label for="translation_languages">翻訳言語:</label>
            <select name="translation_languages[]" id="translation_languages" multiple size="5">
              <?php foreach (LanguageCode::getLanguageMap() as $code => $name): ?>
              <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($code, $selectedLanguages) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <button type="submit" class="btn btn-primary">フィルター</button>
          <a href="list.php" class="btn btn-secondary">リセット</a>
        </div>
      </form>
      
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>単語</th>
              <th>品詞</th>
              <th>言語</th>
              <th>翻訳</th>
              <th>補足</th>
              <th>学習状況</th>
              <th>アクション</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($words) > 0): ?>
              <?php foreach ($words as $w): ?>
                <tr>
                  <td data-label="ID"><?php echo (int)$w['word_id']; ?></td>
                  <td data-label="単語"><?php echo htmlspecialchars($w['word'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="品詞"><?php echo htmlspecialchars($w['part_of_speech'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  <td data-label="言語"><?php echo htmlspecialchars(LanguageCode::getNameFromCode($w['language_code']), ENT_QUOTES, 'UTF-8'); ?></td>
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
                  <td data-label="補足"><?php echo !empty($w['note']) ? htmlspecialchars($w['note'], ENT_QUOTES, 'UTF-8') : ''; ?></td>
                  <td data-label="学習状況" class="learning-status">
                    <?php if (isset($w['test_count']) && $w['test_count'] > 0): ?>
                      <div class="accuracy-bar">
                        <div class="accuracy-value" style="width: <?php echo (int)$w['accuracy_rate']; ?>%;">
                          <?php echo (int)$w['accuracy_rate']; ?>%
                        </div>
                      </div>
                      <div class="test-details">
                        <span>テスト: <?php echo (int)$w['test_count']; ?>回</span>
                        <span>正解: <?php echo (int)$w['correct_count']; ?>回</span>
                        <span>不正解: <?php echo (int)$w['wrong_count']; ?>回</span>
                      </div>
                    <?php else: ?>
                      未テスト
                    <?php endif; ?>
                  </td>
                  <td data-label="アクション" class="actions-cell">
                    <a href="edit.php?id=<?php echo (int)$w['word_id']; ?>" class="btn btn-primary btn-sm">編集</a>
                    <button class="btn btn-danger btn-sm delete-word" data-word-id="<?php echo (int)$w['word_id']; ?>" data-word-text="<?php echo htmlspecialchars($w['word'], ENT_QUOTES, 'UTF-8'); ?>">削除</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="8" class="text-center">単語が見つかりません。</td>
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
      
      // 削除ボタンのイベントハンドラを設定
      document.querySelectorAll('.delete-word').forEach(function(button) {
        button.addEventListener('click', function() {
          const wordId = this.getAttribute('data-word-id');
          const wordText = this.getAttribute('data-word-text');
          
          if (confirm(`「${wordText}」を削除してもよろしいですか？`)) {
            // 削除リクエスト送信
            fetch(`delete.php?id=${wordId}`, {
              method: 'GET'
            })
            .then(response => {
              if (response.ok) {
                // 削除成功したら行を非表示にする
                this.closest('tr').style.display = 'none';
                
                // メッセージ表示
                alert(`「${wordText}」を削除しました。`);
              } else {
                throw new Error('削除処理に失敗しました');
              }
            })
            .catch(error => {
              console.error('エラー:', error);
              alert('エラーが発生しました: ' + error.message);
            });
          }
        });
      });
    });
  </script>
</body>
</html>



