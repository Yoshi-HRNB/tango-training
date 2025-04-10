<?php
/**
 * edit.php
 * 単語を編集するフォームを表示。
 */
// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$wordData = $wordController->getWordById($id);

if (!$wordData) {
    // 該当単語がなければ一覧へ
    header('Location: list.php');
    exit;
}

// 既存の翻訳データを取得
$translations = $wordController->getTranslationsByWordId($id);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>単語編集 - Multilingual Vocabulary App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>単語編集</h1>

      <form action="update.php" method="post">
        <input type="hidden" name="word_id" value="<?php echo (int)$wordData['word_id']; ?>">
        
        <div class="form-group">
          <label for="language_code">言語コード:</label>
          <select id="language_code" name="language_code" required>
            <?php foreach (LanguageCode::getLanguageMap() as $code => $name): ?>
              <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" 
                <?php echo $wordData['language_code'] === $code ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="word">単語:</label>
          <input type="text" id="word" name="word" value="<?php echo htmlspecialchars($wordData['word'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>

        <div class="form-group reading-field" style="display: <?php echo $wordData['language_code'] === 'ja' ? 'block' : 'none'; ?>">
          <label for="reading">フリガナ:</label>
          <input type="text" id="reading" name="reading" value="<?php echo htmlspecialchars($wordData['reading'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label for="part_of_speech">品詞:</label>
          <input type="text" id="part_of_speech" name="part_of_speech" value="<?php echo htmlspecialchars($wordData['part_of_speech'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
          <label for="supplement">補足:</label>
          <textarea id="supplement" name="supplement"><?php echo htmlspecialchars($wordData['note'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <h2>翻訳</h2>
        <div id="translations-container">
          <?php foreach ($translations as $index => $translation): ?>
            <div class="translation-row">
              <div class="form-inline">
                <div class="form-group">
                  <label>言語:</label>
                  <select name="word_translation_language[]">
                    <?php foreach (LanguageCode::getLanguageMap() as $code => $name): ?>
                      <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" 
                        <?php echo $translation['language_code'] === $code ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label>翻訳:</label>
                  <input type="text" name="word_translation[]" value="<?php echo htmlspecialchars($translation['translation'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <button type="button" class="btn btn-danger remove-translation">削除</button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <button type="button" id="add-translation" class="btn">翻訳を追加</button>
        
        <h2>学習状況</h2>
        <div class="learning-stats-section">
          <?php if (isset($wordData['test_count']) && $wordData['test_count'] > 0): 
            $accuracyClass = '';
            $accuracyRate = (int)$wordData['accuracy_rate'];
            if ($accuracyRate >= 80) {
              $accuracyClass = 'accuracy-high';
            } elseif ($accuracyRate >= 50) {
              $accuracyClass = 'accuracy-medium';
            } else {
              $accuracyClass = 'accuracy-low';
            }
          ?>
            <div class="accuracy-bar">
              <div class="accuracy-value <?php echo $accuracyClass; ?>" style="width: <?php echo $accuracyRate; ?>%;">
                <?php echo $accuracyRate; ?>%
              </div>
            </div>
            <div class="test-details">
              <span>テスト回数: <?php echo (int)$wordData['test_count']; ?>回</span>
              <span>正解数: <?php echo (int)$wordData['correct_count']; ?>回</span>
              <span>不正解数: <?php echo (int)$wordData['wrong_count']; ?>回</span>
              <?php if (!empty($wordData['last_test_date'])): ?>
                <span>最終テスト日: <?php echo htmlspecialchars(date('Y/m/d H:i', strtotime($wordData['last_test_date'])), ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <p>まだテストされていません。</p>
          <?php endif; ?>
        </div>
        
        <div class="form-actions">
          <a href="list.php" class="btn btn-secondary">キャンセル</a>
          <button type="submit" class="btn btn-primary">更新</button>
          <button type="button" id="delete-word" class="btn btn-danger">削除</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // 言語コード定義
      <?= LanguageCode::getJavaScriptDefinition() ?>
      
      // 言語選択が変更されたときにフリガナフィールドの表示/非表示を切り替える
      const languageSelect = document.getElementById('language_code');
      const readingField = document.querySelector('.reading-field');
      
      languageSelect.addEventListener('change', function() {
        // 日本語の場合のみフリガナフィールドを表示
        readingField.style.display = (this.value === 'ja') ? 'block' : 'none';
      });
      
      // 翻訳追加ボタン
      document.getElementById('add-translation').addEventListener('click', function() {
        addTranslationRow();
      });
      
      // 既存の翻訳の削除ボタン
      document.querySelectorAll('.remove-translation').forEach(function(button) {
        button.addEventListener('click', function() {
          this.closest('.translation-row').remove();
        });
      });
      
      // 翻訳行を追加する関数
      function addTranslationRow() {
        const container = document.getElementById('translations-container');
        const newRow = document.createElement('div');
        newRow.className = 'translation-row';
        
        let languageOptions = '';
        for (const code in LanguageCode.codeToName) {
          languageOptions += `<option value="${code}">${LanguageCode.codeToName[code]}</option>`;
        }
        
        newRow.innerHTML = `
          <div class="form-inline">
            <div class="form-group">
              <label>言語:</label>
              <select name="word_translation_language[]">
                ${languageOptions}
              </select>
            </div>
            <div class="form-group">
              <label>翻訳:</label>
              <input type="text" name="word_translation[]" value="">
            </div>
            <button type="button" class="btn btn-danger remove-translation">削除</button>
          </div>
        `;
        
        container.appendChild(newRow);
        
        // 新しく追加した削除ボタンにイベントリスナーを設定
        newRow.querySelector('.remove-translation').addEventListener('click', function() {
          newRow.remove();
        });
      }
      
      // 削除ボタンの処理
      const deleteButton = document.getElementById('delete-word');
      if (deleteButton) {
        deleteButton.addEventListener('click', function() {
          const wordId = document.querySelector('input[name="word_id"]').value;
          const wordText = document.getElementById('word').value;
          
          if (confirm(`「${wordText}」を削除してもよろしいですか？\nこの操作は元に戻せません。`)) {
            // 削除処理用のURLにリダイレクト
            window.location.href = `delete.php?id=${wordId}`;
          }
        });
      }
    });
  </script>
</body>
</html>
