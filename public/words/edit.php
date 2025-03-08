<?php
/**
 * edit.php
 * 単語を編集するフォームを表示。
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/WordController.php';

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
            <option value="ja" <?php echo $wordData['language_code'] === 'ja' ? 'selected' : ''; ?>>日本語</option>
            <option value="en" <?php echo $wordData['language_code'] === 'en' ? 'selected' : ''; ?>>英語</option>
            <option value="vi" <?php echo $wordData['language_code'] === 'vi' ? 'selected' : ''; ?>>ベトナム語</option>
            <option value="es" <?php echo $wordData['language_code'] === 'es' ? 'selected' : ''; ?>>スペイン語</option>
            <option value="fr" <?php echo $wordData['language_code'] === 'fr' ? 'selected' : ''; ?>>フランス語</option>
            <option value="de" <?php echo $wordData['language_code'] === 'de' ? 'selected' : ''; ?>>ドイツ語</option>
          </select>
        </div>

        <div class="form-group">
          <label for="word">単語 / 文章:</label>
          <textarea id="word" name="word" placeholder="単語や文章を入力してください" required><?php echo htmlspecialchars($wordData['word'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <div class="form-group">
          <label>訳:</label>
          <div id="translations">
            <?php if (!empty($translations)): ?>
              <?php foreach ($translations as $t): ?>
                <div class="translation-item mb-2">
                  <div class="flex gap-2 flex-wrap">
                    <select name="word_translation_language[]" required>
                      <option value="en" <?php echo $t['language_code'] === 'en' ? 'selected' : ''; ?>>英語</option>
                      <option value="ja" <?php echo $t['language_code'] === 'ja' ? 'selected' : ''; ?>>日本語</option>
                      <option value="vi" <?php echo $t['language_code'] === 'vi' ? 'selected' : ''; ?>>ベトナム語</option>
                      <option value="es" <?php echo $t['language_code'] === 'es' ? 'selected' : ''; ?>>スペイン語</option>
                      <option value="fr" <?php echo $t['language_code'] === 'fr' ? 'selected' : ''; ?>>フランス語</option>
                      <option value="de" <?php echo $t['language_code'] === 'de' ? 'selected' : ''; ?>>ドイツ語</option>
                    </select>
                    <input type="text" name="word_translation[]" value="<?php echo htmlspecialchars($t['translation'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="訳を入力してください" required>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeTranslation(this)">削除</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <button type="button" id="addTranslationButton" class="btn btn-outline mt-2">翻訳を追加</button>
        </div>

        <div class="form-group">
          <label for="supplement">補足:</label>
          <textarea id="supplement" name="supplement" placeholder="補足情報を入力してください"><?php echo htmlspecialchars($wordData['note'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">更新</button>
      </form>
      
      <div class="nav-links mt-3">
        <a href="list.php">単語一覧へ戻る</a>
        <a href="../index.php">トップへ戻る</a>
      </div>
    </div>
  </div>

  <script>
    /**
     * 言語リストを定数として管理
     */
    const LANGUAGES = [
      { code: "ja", name: "日本語" },
      { code: "en", name: "英語" },
      { code: "vi", name: "ベトナム語" },
      { code: "es", name: "スペイン語" },
      { code: "fr", name: "フランス語" },
      { code: "de", name: "ドイツ語" },
    ];

    /**
     * 翻訳行をUIに追加
     */
    function addTranslationRow(language = "", word = "") {
      const translationsDiv = document.getElementById("translations");

      const translationItem = document.createElement("div");
      translationItem.className = "translation-item mb-2";

      const flexContainer = document.createElement("div");
      flexContainer.className = "flex gap-2 flex-wrap";

      // 言語セレクトボックス
      const langSelect = document.createElement("select");
      langSelect.name = "word_translation_language[]";
      langSelect.required = true;
      LANGUAGES.forEach((lang) => {
        const option = document.createElement("option");
        option.value = lang.code;
        option.textContent = lang.name;
        if (lang.code === language) option.selected = true;
        langSelect.appendChild(option);
      });

      // 単語入力欄
      const wordInput = document.createElement("input");
      wordInput.type = "text";
      wordInput.name = "word_translation[]";
      wordInput.placeholder = "訳を入力してください";
      wordInput.value = word;
      wordInput.required = true;

      // 削除ボタン
      const removeButton = document.createElement("button");
      removeButton.type = "button";
      removeButton.className = "btn btn-danger btn-sm";
      removeButton.textContent = "削除";
      removeButton.onclick = function () {
        translationItem.remove();
      };

      flexContainer.appendChild(langSelect);
      flexContainer.appendChild(wordInput);
      flexContainer.appendChild(removeButton);
      translationItem.appendChild(flexContainer);

      translationsDiv.appendChild(translationItem);
    }

    /**
     * 翻訳行を削除
     */
    function removeTranslation(button) {
      const translationItem = button.parentElement;
      translationItem.remove();
    }

    /**
     * 「翻訳を追加」ボタン
     */
    document.getElementById("addTranslationButton").addEventListener("click", () => {
      addTranslationRow();
    });
  </script>
</body>
</html>
