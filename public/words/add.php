<?php
/**
 * add.php
 * 新しく単語を登録するフォーム。
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <title>単語登録 - Multilingual Vocabulary App with DeepSeek</title>
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
    </style>
  </head>
  <body>
    <div class="container">
      <h1>単語登録フォーム</h1>

      <!-- ローディング状態を表示する要素 -->
      <div id="loadingIndicator">読み込み中...</div>

      <form action="create.php" method="post">
        <div class="form-group">
          <label for="language_code">言語コード (ja/en/vi など):</label><br />
          <select id="language_code" name="language_code" required>
            <option value="ja">日本語</option>
            <option value="en">英語</option>
            <option value="vi" selected>ベトナム語</option>
            <option value="es">スペイン語</option>
            <option value="fr">フランス語</option>
            <option value="de">ドイツ語</option>
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
          ></textarea>
        </div>

        <!-- 例文表示（有無で表示を切り替え） -->
        <div id="example" style="display: none">
          例文: <span id="exampleText"></span>
        </div>
        <div id="noExample" style="display: none">例文はありません。</div>

        <div class="form-group">
          <label>訳:</label>
          <div id="translations" class="translations">
            <!-- 初期翻訳行を2つ表示（例: 英語、日本語） -->
            <div class="translation-item">
              <select name="word_translation_language[]" required>
                <option value="en" selected>英語</option>
                <option value="ja">日本語</option>
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
          ></textarea>
        </div>

        <!-- 登録ボタン -->
        <button type="submit">登録</button>
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

      let debounceTimer = null;

      /**
       * ローディングインジケータの表示/非表示を制御
       */
      function showLoading(isLoading) {
        const loader = document.getElementById("loadingIndicator");
        loader.style.display = isLoading ? "block" : "none";
      }

      /**
       * DeepSeek「R1」APIを呼び出して翻訳データを取得する (サンプル)
       * 実際には適宜エンドポイントを変更してください
       */
      async function fetchTranslation(word) {
        showLoading(true);
        try {
          // 実際のAPIエンドポイントに置き換えてください
          const response = await fetch(
            `https://api.deepseek.com/r1/translate?word=${encodeURIComponent(word)}`
          );

          if (!response.ok) {
            throw new Error(
              `翻訳の取得に失敗しました: ${response.status} ${response.statusText}`
            );
          }

          const data = await response.json();

          if (!data || !Array.isArray(data.translations)) {
            throw new Error("レスポンス形式が無効です");
          }

          return data;
        } catch (error) {
          throw error;
        } finally {
          showLoading(false);
        }
      }

      /**
       * 入力欄が変わったタイミングでdebounceをかける
       */
      function handleInputDebounce() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
          handleWordInput();
        }, 500);
      }

      /**
       * 入力された単語を使って翻訳を取得、UIを更新する
       */
      async function handleWordInput() {
        const wordInput = document.getElementById("word");
        const word = wordInput.value.trim();
        if (!word) {
          updateExample(null);
          clearTranslations();
          return;
        }

        try {
          const data = await fetchTranslation(word);

          updateExample(data.example);

          clearTranslations();
          if (data.translations.length === 0) {
            document.getElementById("noTranslations").style.display = "block";
          } else {
            document.getElementById("noTranslations").style.display = "none";
            // デフォルトの2つの訳を追加
            addTranslationRow(
              "en",
              data.translations.find((t) => t.language === "en")?.word || ""
            );
            addTranslationRow(
              "ja",
              data.translations.find((t) => t.language === "ja")?.word || ""
            );
          }
        } catch (error) {
          alert(`翻訳取得中にエラーが発生しました: ${error.message}`);
        }
      }

      /**
       * 例文がある場合は表示し、ない場合は"例文はありません。"メッセージを出す
       */
      function updateExample(example) {
        const exampleDiv = document.getElementById("example");
        const exampleText = document.getElementById("exampleText");
        const noExampleDiv = document.getElementById("noExample");

        if (example) {
          exampleDiv.style.display = "block";
          exampleText.textContent = example;
          noExampleDiv.style.display = "none";
        } else {
          exampleDiv.style.display = "none";
          noExampleDiv.style.display = "block";
        }
      }

      /**
       * 翻訳リストをクリアし、デフォルトの2つの訳を追加
       */
      function clearTranslations() {
        const translationsDiv = document.getElementById("translations");
        translationsDiv.innerHTML = "";

        addTranslationRow("en", "");
        addTranslationRow("ja", "");

        document.getElementById("noTranslations").style.display = "none";
      }

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
       * 翻訳行を削除する関数
       */
      function removeTranslation(button) {
        const translationItem = button.parentElement;
        translationItem.remove();
      }

      /**
       * 入力欄にDebounce付きのイベントを設定し、翻訳行の追加ボタンを設定
       */
      window.onload = function () {
        // 入力欄にDebounce付きのイベントを設定
        document
          .getElementById("word")
          .addEventListener("input", handleInputDebounce);

        // 「翻訳を追加」ボタンで翻訳行を追加
        document
          .getElementById("addTranslationButton")
          .addEventListener("click", () => {
            addTranslationRow();
          });
      };
    </script>
  </body>
</html>
