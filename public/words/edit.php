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
  <title>単語編集 - Multilingual Vocabulary App with DeepSeek</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="../css/style.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .translations {
      margin-top: 10px;
    }
    .translation-item {
      display: flex;
      margin-bottom: 5px;
      align-items: center;
    }
    .translation-item select,
    .translation-item input,
    .translation-item textarea {
      margin-right: 10px;
      padding: 5px;
      font-size: 1em;
    }
    .translation-item button {
      padding: 5px 10px;
      font-size: 0.9em;
      background-color: #ff4d4d;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
    }
    .translation-item button:hover {
      background-color: #ff1a1a;
    }
    #loadingIndicator {
      display: none;
      font-weight: bold;
      color: red;
      margin-bottom: 10px;
    }
    #example,
    #noExample,
    #noTranslations {
      font-style: italic;
      margin-top: 5px;
    }
    #noExample,
    #noTranslations {
      color: #555;
    }
    #addTranslationButton,
    .add-translation-button {
      padding: 7px 15px;
      font-size: 1em;
      background-color: #4caf50;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      margin-top: 10px;
    }
    #addTranslationButton:hover,
    .add-translation-button:hover {
      background-color: #45a049;
    }
    button[type="submit"] {
      padding: 10px 20px;
      font-size: 1em;
      background-color: #008cba;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      margin-top: 20px;
    }
    button[type="submit"]:hover {
      background-color: #007bb5;
    }
    a {
      color: #008cba;
      text-decoration: none;
    }
    a:hover {
      text-decoration: underline;
    }
    textarea {
      width: 100%;
      height: 100px;
      resize: vertical;
    }
    .container {
      max-width: 800px;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>単語編集フォーム</h1>

    <!-- ローディング状態を表示する要素 -->
    <div id="loadingIndicator">読み込み中...</div>

    <form action="update.php" method="post" id="editWordForm">
      <!-- 旧: value="<?php echo (int)$wordData['id']; ?>" → 新: word_id -->
      <input type="hidden" name="id" value="<?php echo (int)$wordData['word_id']; ?>">

      <div class="form-group">
        <label for="language_code">言語コード (ja/en/vi など):</label><br />
        <select id="language_code" name="language_code" required>
          <option value="ja" <?php echo $wordData['language_code'] === 'ja' ? 'selected' : ''; ?>>日本語</option>
          <option value="en" <?php echo $wordData['language_code'] === 'en' ? 'selected' : ''; ?>>英語</option>
          <option value="vi" <?php echo $wordData['language_code'] === 'vi' ? 'selected' : ''; ?>>ベトナム語</option>
          <option value="es" <?php echo $wordData['language_code'] === 'es' ? 'selected' : ''; ?>>スペイン語</option>
          <option value="fr" <?php echo $wordData['language_code'] === 'fr' ? 'selected' : ''; ?>>フランス語</option>
          <option value="de" <?php echo $wordData['language_code'] === 'de' ? 'selected' : ''; ?>>ドイツ語</option>
          <!-- 必要に応じて他の言語を追加 -->
        </select>
      </div>

      <div class="form-group">
        <label for="word">単語 / 文章:</label><br />
        <textarea
          id="word"
          name="word"
          placeholder="単語や文章を入力してください (例: りんご)"
          required
        ><?php echo htmlspecialchars($wordData['word'], ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <!-- 例文表示（有無で表示を切り替え） -->
      <div id="example" style="display: none">
        例文: <span id="exampleText"></span>
      </div>
      <div id="noExample" style="display: none">例文はありません。</div>

      <div class="form-group">
        <label>訳:</label>
        <div id="translations" class="translations">
          <?php if (!empty($translations)): ?>
            <?php foreach ($translations as $translation): ?>
              <div class="translation-item">
                <select name="word_translation_language[]" required>
                  <option value="ja" <?php echo ($translation['language_code'] === 'ja' ? 'selected' : ''); ?>>日本語</option>
                  <option value="en" <?php echo ($translation['language_code'] === 'en' ? 'selected' : ''); ?>>英語</option>
                  <option value="vi" <?php echo ($translation['language_code'] === 'vi' ? 'selected' : ''); ?>>ベトナム語</option>
                  <option value="es" <?php echo ($translation['language_code'] === 'es' ? 'selected' : ''); ?>>スペイン語</option>
                  <option value="fr" <?php echo ($translation['language_code'] === 'fr' ? 'selected' : ''); ?>>フランス語</option>
                  <option value="de" <?php echo ($translation['language_code'] === 'de' ? 'selected' : ''); ?>>ドイツ語</option>
                  <!-- 必要に応じて他の言語を追加 -->
                </select>
                <input
                  type="text"
                  name="word_translation[]"
                  placeholder="訳を入力してください"
                  value="<?php echo htmlspecialchars($translation['translation'], ENT_QUOTES, 'UTF-8'); ?>"
                  required
                />
                <button type="button" onclick="removeTranslation(this)">削除</button>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <!-- 翻訳が存在しない場合の初期行。必要なら複数行追加など調整してください -->
            <div class="translation-item">
              <select name="word_translation_language[]" required>
                <option value="en">英語</option>
                <option value="ja" selected>日本語</option>
                <option value="vi">ベトナム語</option>
                <option value="es">スペイン語</option>
                <option value="fr">フランス語</option>
                <option value="de">ドイツ語</option>
              </select>
              <input
                type="text"
                name="word_translation[]"
                placeholder="訳を入力してください"
                required
              />
              <button type="button" onclick="removeTranslation(this)">
                削除
              </button>
            </div>
            <div class="translation-item">
              <select name="word_translation_language[]" required>
                <option value="en">英語</option>
                <option value="ja" selected>日本語</option>
                <option value="vi">ベトナム語</option>
                <option value="es">スペイン語</option>
                <option value="fr">フランス語</option>
                <option value="de">ドイツ語</option>
              </select>
              <input
                type="text"
                name="word_translation[]"
                placeholder="訳を入力してください"
                required
              />
              <button type="button" onclick="removeTranslation(this)">
                削除
              </button>
            </div>
          <?php endif; ?>
        </div>
        <!-- ユーザーが手動で翻訳行を追加するためのボタン -->
        <button type="button" id="addTranslationButton">翻訳を追加</button>
        <!-- 翻訳が見つからない場合に表示 -->
        <div id="noTranslations" style="display: none">
          翻訳が見つかりませんでした。
        </div>
      </div>

      <div class="form-group">
        <label for="supplement">補足:</label><br />
        <textarea
          id="supplement"
          name="supplement"
          placeholder="補足情報を入力してください"
        ><?php echo htmlspecialchars($wordData['note'], ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>

      <!-- 更新ボタン -->
      <button type="submit">更新</button>
    </form>
    <p><a href="list.php">単語一覧へ戻る</a></p>
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
      translationItem.className = "translation-item";

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
      removeButton.textContent = "削除";
      removeButton.onclick = function () {
        translationItem.remove();
      };

      translationItem.appendChild(langSelect);
      translationItem.appendChild(wordInput);
      translationItem.appendChild(removeButton);

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
