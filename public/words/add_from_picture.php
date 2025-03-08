<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>単語登録</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css" />
  <style>
    @keyframes highlight-new-item {
      0% { background-color: #e3f2fd; }
      50% { background-color: #bbdefb; }
      100% { background-color: #f0f9ff; }
    }
    
    .message-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
    }
    
    .feedback-message {
      background-color: #4caf50;
      color: white;
      padding: 10px 20px;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      margin-top: 10px;
      animation: fade-out 3s forwards;
    }
    
    @keyframes fade-out {
      0% { opacity: 1; }
      70% { opacity: 1; }
      100% { opacity: 0; }
    }

    /* タブのスタイル */
    .tabs {
      display: flex;
      list-style: none;
      padding: 0;
      margin: 0 0 20px 0;
      border-bottom: 1px solid #ddd;
    }
    
    .tabs li {
      padding: 10px 20px;
      cursor: pointer;
      border: 1px solid transparent;
      border-bottom: none;
      margin-bottom: -1px;
      background-color: #f8f9fa;
      border-radius: 5px 5px 0 0;
      margin-right: 5px;
    }
    
    .tabs li.active {
      background-color: #fff;
      border-color: #ddd;
      border-bottom-color: white;
      font-weight: bold;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }

    /* 共通設定欄のスタイル */
    .common-settings {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #f8f9fa;
      border-radius: 5px;
      border: 1px solid #eee;
    }

    .settings-title {
      font-size: 1.1rem;
      margin-bottom: 10px;
      font-weight: 500;
    }
    
    /* 編集モードのスタイル */
    .word-item .form-group {
      margin-bottom: 10px;
    }

    .word-item .form-control {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    .word-item .edit-mode {
      background-color: #f9f9f9;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
<?php
require_once __DIR__ . '/../../src/LanguageCode.php';
use TangoTraining\LanguageCode;
?>
  <div class="container">
    <div class="card">
      <h1>単語登録</h1>
      
      <div class="nav-links mb-3">
        <a href="list.php">単語一覧へ</a>
        <a href="../index.php">トップへ戻る</a>
      </div>

      <!-- 共通設定欄 -->
      <div class="common-settings">
        <div class="settings-title">共通設定</div>
        <div class="form-row">
          <div class="form-group col-md-6">
            <label for="sourceLanguage">登録する単語の言語:</label>
            <select id="sourceLanguage" class="form-control">
              <?php foreach (LanguageCode::getLanguageMap() as $code => $name): ?>
              <option value="<?= htmlspecialchars($name) ?>"<?= $name === '英語' ? ' selected' : '' ?>><?= htmlspecialchars($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label for="targetLanguage">翻訳の言語:</label>
            <select id="targetLanguage" class="form-control">
              <?php foreach (LanguageCode::getLanguageMap() as $code => $name): ?>
              <option value="<?= htmlspecialchars($name) ?>"<?= $name === '日本語' ? ' selected' : '' ?>><?= htmlspecialchars($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      
      <!-- タブナビゲーション -->
      <ul class="tabs">
        <li class="tab-link active" data-tab="tab-input">入力して登録</li>
        <li class="tab-link" data-tab="tab-image">画像から抽出</li>
      </ul>
      
      <!-- 入力して登録タブ -->
      <div id="tab-input" class="tab-content active">
        <!-- 抽出結果パート -->
        <div class="section card mb-3">
          <h2>1. テキスト入力</h2>
          <div class="form-group">
            <textarea id="directInputText" placeholder="登録したいテキストを入力してください" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label for="levelSelect">抽出レベル (1〜5):</label>
            <select id="levelSelect" class="form-control">
              <option value="1" selected>抽出レベル1(100%)</option>
              <option value="2">抽出レベル2(80%)</option>
              <option value="3">抽出レベル3(60%)</option>
              <option value="4">抽出レベル4(40%)</option>
              <option value="5">抽出レベル5(20%)</option>
            </select>
          </div>
          <button id="directTranslateBtn" class="btn btn-primary">単語を翻訳・抽出</button>
          <div id="directTranslatedTextArea" class="result-area mt-3" style="display: none;">
            <h3>翻訳結果</h3>
            <div id="directTranslatedText" class="p-3 bg-light border rounded"></div>
          </div>
          <div id="directResultArea" class="result-area mt-3" style="display: none;">
            <h3>翻訳・抽出された単語</h3>
            <div id="directExtractedWords" class="word-list"></div>
            <button id="directRegisterAllBtn" class="btn btn-success mt-3" style="display: none;">一括登録</button>
          </div>
        </div>

        <!-- 手動追加パート -->
        <div class="section card mb-3">
          <h2>2. 手動で単語を追加</h2>
          <div class="form-group">
            <label for="directNewWordInput">単語:</label>
            <input type="text" id="directNewWordInput" placeholder="単語を入力">
          </div>
          <div class="form-group">
            <label for="directNewPartOfSpeechInput">品詞:</label>
            <input type="text" id="directNewPartOfSpeechInput" placeholder="品詞を入力">
          </div>
          <div class="form-group">
            <label for="directNewMeaningInput">意味:</label>
            <input type="text" id="directNewMeaningInput" placeholder="意味を入力">
          </div>
          <div class="form-group">
            <label for="directNewNoteInput">補足:</label>
            <input type="text" id="directNewNoteInput" placeholder="補足を入力 (複合語の意味など)">
          </div>
          <button id="directAddAndRegisterBtn" class="btn btn-success">追加して登録</button>
        </div>
      </div>
      
      <!-- 画像から抽出タブ -->
      <div id="tab-image" class="tab-content">
        <!-- ファイル選択パート -->
        <div class="section card mb-3">
          <h2>1. ファイルを選択</h2>
          <div class="form-group">
            <label for="uploadImage">画像ファイル:</label>
            <input type="file" id="uploadImage" accept="image/*" class="file-input">
          </div>
          <p class="text-light">※ファイルは自動的にアップロードされます</p>
          <div id="previewArea" class="mt-2" style="display: none;">
            <h3>プレビュー</h3>
            <img id="preview" style="max-width: 100%; max-height: 300px;">
          </div>
          <button id="extractTextBtn" class="btn btn-primary mt-3">画像からテキスト抽出</button>
        </div>

        <!-- 抽出結果パート -->
        <div class="section card mb-3">
          <h2>2. 抽出結果</h2>
          <div class="form-group">
            <textarea id="extractedText" placeholder="画像から抽出されたテキストがここに表示されます" rows="4"></textarea>
          </div>
          <button id="translateBtn" class="btn btn-primary">単語を翻訳・抽出</button>
          <div id="translatedTextArea" class="result-area mt-3" style="display: none;">
            <h3>翻訳結果</h3>
            <div id="translatedText" class="p-3 bg-light border rounded"></div>
          </div>
          <div id="resultArea" class="result-area mt-3" style="display: none;">
            <h3>翻訳・抽出された単語</h3>
            <div id="extractedWords" class="word-list"></div>
            <button id="registerAllBtn" class="btn btn-success mt-3" style="display: none;">一括登録</button>
          </div>
        </div>

        <!-- 手動追加パート -->
        <div class="section card mb-3">
          <h2>3. 手動で単語を追加</h2>
          <div class="form-group">
            <label for="newWordInput">単語:</label>
            <input type="text" id="newWordInput" placeholder="単語を入力">
          </div>
          <div class="form-group">
            <label for="newPartOfSpeechInput">品詞:</label>
            <input type="text" id="newPartOfSpeechInput" placeholder="品詞を入力">
          </div>
          <div class="form-group">
            <label for="newMeaningInput">意味:</label>
            <input type="text" id="newMeaningInput" placeholder="意味を入力">
          </div>
          <div class="form-group">
            <label for="newNoteInput">補足:</label>
            <input type="text" id="newNoteInput" placeholder="補足を入力 (複合語の意味など)">
          </div>
          <button id="addAndRegisterBtn" class="btn btn-success">追加して登録</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // 言語コード定義
    <?= LanguageCode::getJavaScriptDefinition() ?>
    
    // タブ切り替え機能
    document.addEventListener('DOMContentLoaded', function() {
      const tabLinks = document.querySelectorAll('.tab-link');
      
      tabLinks.forEach(function(tab) {
        tab.addEventListener('click', function() {
          // すべてのタブからアクティブクラスを削除
          tabLinks.forEach(t => t.classList.remove('active'));
          
          // すべてのタブコンテンツを非表示に
          document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
          });
          
          // クリックされたタブにアクティブクラスを追加
          this.classList.add('active');
          
          // 関連するコンテンツを表示
          const tabId = this.getAttribute('data-tab');
          document.getElementById(tabId).classList.add('active');
        });
      });
      
      // 初期表示時にフォームを1つ追加
      const wordFormContainer = document.getElementById('word-form-container');
      wordFormContainer.innerHTML = createWordInputForm(1);
      
      // 言語が変更されたときにフリガナフィールドの表示/非表示を切り替える
      const sourceLanguageSelect = document.getElementById('sourceLanguage');
      function updateReadingFieldVisibility() {
        const isJapanese = sourceLanguageSelect.value === '日本語';
        document.querySelectorAll('.reading-field').forEach(field => {
          field.style.display = isJapanese ? 'block' : 'none';
        });
      }
      
      // 初期表示時と言語変更時に実行
      updateReadingFieldVisibility();
      sourceLanguageSelect.addEventListener('change', updateReadingFieldVisibility);
    });
    
    // 既存のJavaScriptコードはそのまま残す
    // ただし、UIの更新部分を以下のように変更
    
    // 抽出テキストを表示する部分
    function displayExtractedText(text) {
      document.getElementById('extractedText').value = text;
      document.getElementById('translateBtn').disabled = !text;
      
      // 結果エリアを非表示に
      document.getElementById('resultArea').style.display = 'none';
      document.getElementById('translatedTextArea').style.display = 'none';
    }
    
    // 抽出された単語リストを表示（共通関数に変更）
    function displayExtractedWords(words, containerId, registerBtnId) {
      const container = document.getElementById(containerId);
      container.innerHTML = '';
      
      // ソース言語が日本語かどうかを確認
      const isJapanese = document.getElementById('sourceLanguage').value === '日本語';
      
      if (words && words.length > 0) {
        // 一括登録ボタンを表示
        document.getElementById(registerBtnId).style.display = 'block';
        
        words.forEach(word => {
          // noteフィールドを使用
          const noteText = word.note || '';
          const partOfSpeech = word.part_of_speech || '';
          const reading = word.reading || '';
          
          const div = document.createElement('div');
          div.className = 'word-item';
          div.dataset.word = word.word;
          div.dataset.meaning = word.meaning || '';
          div.dataset.note = noteText;
          div.dataset.partOfSpeech = partOfSpeech;
          if (isJapanese && reading) {
            div.dataset.reading = reading;
          }
          
          div.innerHTML = `
            <div class="view-mode">
              <h3>${word.word}</h3>
              ${isJapanese && reading ? `<p><strong>フリガナ:</strong> ${reading}</p>` : ''}
              ${partOfSpeech ? `<p><strong>品詞:</strong> ${partOfSpeech}</p>` : ''}
              <p><strong>意味:</strong> ${word.meaning || '意味なし'}</p>
              ${noteText ? `<p class="note-text"><strong>補足:</strong> ${noteText}</p>` : ''}
              <div class="flex gap-2 mt-2">
                <button class="btn btn-secondary btn-sm edit-btn">編集</button>
                <button class="btn btn-primary btn-sm register-btn">登録</button>
                <button class="btn btn-danger btn-sm delete-btn">削除</button>
              </div>
            </div>
            <div class="edit-mode" style="display: none;">
              <div class="form-group">
                <label>単語:</label>
                <input type="text" class="form-control edit-word" value="${word.word}">
              </div>
              ${isJapanese ? `
              <div class="form-group">
                <label>フリガナ:</label>
                <input type="text" class="form-control edit-reading" value="${reading}">
              </div>` : ''}
              <div class="form-group">
                <label>品詞:</label>
                <input type="text" class="form-control edit-part-of-speech" value="${partOfSpeech}">
              </div>
              <div class="form-group">
                <label>意味:</label>
                <input type="text" class="form-control edit-meaning" value="${word.meaning || ''}">
              </div>
              <div class="form-group">
                <label>補足:</label>
                <input type="text" class="form-control edit-note" value="${noteText}">
              </div>
              <div class="flex gap-2 mt-2">
                <button class="btn btn-primary btn-sm save-btn">保存</button>
                <button class="btn btn-secondary btn-sm cancel-btn">キャンセル</button>
              </div>
            </div>
          `;
          
          container.appendChild(div);

          // 編集ボタンのイベント
          div.querySelector('.edit-btn').addEventListener('click', () => {
            div.querySelector('.view-mode').style.display = 'none';
            div.querySelector('.edit-mode').style.display = 'block';
          });

          // 保存ボタンのイベント
          div.querySelector('.save-btn').addEventListener('click', () => {
            // 編集した値を取得
            const newWord = div.querySelector('.edit-word').value.trim();
            const newPartOfSpeech = div.querySelector('.edit-part-of-speech').value.trim();
            const newMeaning = div.querySelector('.edit-meaning').value.trim();
            const newNote = div.querySelector('.edit-note').value.trim();
            // 日本語の場合はフリガナも取得
            let newReading = '';
            if (isJapanese) {
              const readingInput = div.querySelector('.edit-reading');
              if (readingInput) {
                newReading = readingInput.value.trim();
              }
            }
            
            if (!newWord) {
              alert('単語を入力してください');
              return;
            }
            
            // データ属性とHTMLを更新
            div.dataset.word = newWord;
            div.dataset.partOfSpeech = newPartOfSpeech;
            div.dataset.meaning = newMeaning;
            div.dataset.note = newNote;
            if (isJapanese) {
              div.dataset.reading = newReading;
            }
            
            // ビューモードの内容を更新
            div.querySelector('.view-mode h3').textContent = newWord;
            
            // フリガナの表示を更新（日本語の場合）
            if (isJapanese) {
              let readingElement = div.querySelector('.view-mode p:first-of-type');
              if (readingElement && readingElement.innerHTML.includes('フリガナ:')) {
                if (newReading) {
                  readingElement.innerHTML = `<strong>フリガナ:</strong> ${newReading}`;
                } else {
                  readingElement.remove();
                  readingElement = null;
                }
              } else if (newReading) {
                readingElement = document.createElement('p');
                readingElement.innerHTML = `<strong>フリガナ:</strong> ${newReading}`;
                div.querySelector('.view-mode h3').after(readingElement);
              }
            }
            
            // 品詞の表示を更新
            const startIndex = isJapanese ? (div.dataset.reading ? 2 : 1) : 1;
            let partOfSpeechElement = div.querySelector(`.view-mode p:nth-of-type(${startIndex})`);
            if (partOfSpeechElement && partOfSpeechElement.innerHTML.includes('品詞:')) {
              if (newPartOfSpeech) {
                partOfSpeechElement.innerHTML = `<strong>品詞:</strong> ${newPartOfSpeech}`;
              } else {
                partOfSpeechElement.remove();
                partOfSpeechElement = null;
              }
            } else if (newPartOfSpeech) {
              partOfSpeechElement = document.createElement('p');
              partOfSpeechElement.innerHTML = `<strong>品詞:</strong> ${newPartOfSpeech}`;
              if (isJapanese && div.dataset.reading) {
                div.querySelector('.view-mode p:first-of-type').after(partOfSpeechElement);
              } else {
                div.querySelector('.view-mode h3').after(partOfSpeechElement);
              }
            }
            
            // 意味の表示を更新
            const meaningIndex = isJapanese ? 
              (div.dataset.reading && partOfSpeechElement ? 3 : 
               div.dataset.reading || partOfSpeechElement ? 2 : 1) : 
              (partOfSpeechElement ? 2 : 1);
            
            let meaningElement = div.querySelector(`.view-mode p:nth-of-type(${meaningIndex})`);
            if (!meaningElement || !meaningElement.innerHTML.includes('意味:')) {
              meaningElement = document.createElement('p');
              if (partOfSpeechElement) {
                partOfSpeechElement.after(meaningElement);
              } else if (isJapanese && div.dataset.reading) {
                div.querySelector('.view-mode p:first-of-type').after(meaningElement);
              } else {
                div.querySelector('.view-mode h3').after(meaningElement);
              }
            }
            meaningElement.innerHTML = `<strong>意味:</strong> ${newMeaning || '意味なし'}`;
            
            // 補足の表示を更新
            const noteElement = div.querySelector('.note-text');
            if (newNote) {
              if (noteElement) {
                noteElement.innerHTML = `<strong>補足:</strong> ${newNote}`;
              } else {
                const newNoteElement = document.createElement('p');
                newNoteElement.className = 'note-text';
                newNoteElement.innerHTML = `<strong>補足:</strong> ${newNote}`;
                meaningElement.after(newNoteElement);
              }
            } else if (noteElement) {
              noteElement.remove();
            }
            
            // 表示モードに戻す
            div.querySelector('.view-mode').style.display = 'block';
            div.querySelector('.edit-mode').style.display = 'none';
          });

          // キャンセルボタンのイベント
          div.querySelector('.cancel-btn').addEventListener('click', () => {
            // 編集内容を破棄して表示モードに戻す
            div.querySelector('.view-mode').style.display = 'block';
            div.querySelector('.edit-mode').style.display = 'none';
          });

          // 登録ボタンのイベント - 直接DBに登録
          div.querySelector('.register-btn').addEventListener('click', () => {
            saveWordToDB(
              div.dataset.word, 
              div.dataset.meaning || '', 
              div.dataset.note, 
              div, 
              containerId, 
              registerBtnId, 
              div.dataset.partOfSpeech,
              isJapanese ? div.dataset.reading || '' : null
            );
          });

          // 削除ボタンのイベント
          div.querySelector('.delete-btn').addEventListener('click', () => {
            div.remove();
            
            // 残りの単語がなければ一括登録ボタンを非表示に
            if (document.querySelectorAll(`#${containerId} .word-item`).length === 0) {
              document.getElementById(registerBtnId).style.display = 'none';
            }
          });
        });
        
        document.getElementById(containerId === 'extractedWords' ? 'resultArea' : 'directResultArea').style.display = 'block';
      } else {
        container.innerHTML = '<p>単語が見つかりませんでした</p>';
        document.getElementById(containerId === 'extractedWords' ? 'resultArea' : 'directResultArea').style.display = 'block';
        document.getElementById(registerBtnId).style.display = 'none';
      }
    }

    // 「単語を翻訳・抽出」ボタンのイベントリスナー（画像タブ用）
    document.getElementById('translateBtn').addEventListener('click', function() {
      const text = document.getElementById('extractedText').value.trim();
      if (!text) {
        alert("テキストが入力されていません。");
        return;
      }
      
      // 選択された言語とレベルを取得
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      const targetLanguage = document.getElementById('targetLanguage').value;
      const level = document.getElementById('levelSelect').value;
      
      // フォームデータの作成
      let formData = new FormData();
      formData.append('text', text);
      formData.append('sourceLanguage', sourceLanguage);
      formData.append('targetLanguage', targetLanguage);
      formData.append('level', level);
      
      // 処理中の表示
      document.getElementById('translatedText').textContent = "翻訳中...";
      document.getElementById('translatedTextArea').style.display = 'block';
      document.getElementById('extractedWords').innerHTML = "<p>単語抽出中...</p>";
      document.getElementById('resultArea').style.display = 'block';
      
      // translate.phpにリクエストを送信
      fetch('translate.php', {
        method: 'POST',
        body: formData
      })
      .then(res => {
        // レスポンスがJSON形式かチェック
        const contentType = res.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
          return res.json();
        } else {
          throw new Error('レスポンスがJSON形式ではありません。サーバーエラーが発生している可能性があります。');
        }
      })
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }
        
        // 翻訳結果を表示
        const translatedTextArea = document.getElementById('translatedTextArea');
        const translatedTextElement = document.getElementById('translatedText');
        
        if (data.translated_text) {
          translatedTextElement.textContent = data.translated_text;
          translatedTextArea.style.display = 'block';
        } else {
          translatedTextElement.textContent = '(翻訳結果なし)';
          translatedTextArea.style.display = 'block';
        }
        
        // 抽出された単語リストを表示
        displayExtractedWords(data.extracted_words || [], 'extractedWords', 'registerAllBtn');
      })
      .catch(err => {
        console.error(err);
        alert("通信エラー: " + err.message);
      });
    });

    // 「単語を翻訳・抽出」ボタンのイベントリスナー（入力タブ用）
    document.getElementById('directTranslateBtn').addEventListener('click', function() {
      const text = document.getElementById('directInputText').value.trim();
      if (!text) {
        alert("テキストが入力されていません。");
        return;
      }
      
      // 選択された言語とレベルを取得
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      const targetLanguage = document.getElementById('targetLanguage').value;
      const level = document.getElementById('levelSelect').value;
      
      // フォームデータの作成
      let formData = new FormData();
      formData.append('text', text);
      formData.append('sourceLanguage', sourceLanguage);
      formData.append('targetLanguage', targetLanguage);
      formData.append('level', level);
      
      // 処理中の表示
      document.getElementById('directTranslatedText').textContent = "翻訳中...";
      document.getElementById('directTranslatedTextArea').style.display = 'block';
      document.getElementById('directExtractedWords').innerHTML = "<p>単語抽出中...</p>";
      document.getElementById('directResultArea').style.display = 'block';
      
      // translate.phpにリクエストを送信
      fetch('translate.php', {
        method: 'POST',
        body: formData
      })
      .then(res => {
        // レスポンスがJSON形式かチェック
        const contentType = res.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
          return res.json();
        } else {
          throw new Error('レスポンスがJSON形式ではありません。サーバーエラーが発生している可能性があります。');
        }
      })
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }
        
        // 翻訳結果を表示
        const translatedTextArea = document.getElementById('directTranslatedTextArea');
        const translatedTextElement = document.getElementById('directTranslatedText');
        
        if (data.translated_text) {
          translatedTextElement.textContent = data.translated_text;
          translatedTextArea.style.display = 'block';
        } else {
          translatedTextElement.textContent = '(翻訳結果なし)';
          translatedTextArea.style.display = 'block';
        }
        
        // 抽出された単語リストを表示
        displayExtractedWords(data.extracted_words || [], 'directExtractedWords', 'directRegisterAllBtn');
      })
      .catch(err => {
        console.error(err);
        alert("通信エラー: " + err.message);
      });
    });
    
    // 一括登録ボタンのイベントリスナー（画像タブ用）
    document.getElementById('registerAllBtn').addEventListener('click', function() {
      registerAllWords('extractedWords', 'registerAllBtn');
    });

    // 一括登録ボタンのイベントリスナー（入力タブ用）
    document.getElementById('directRegisterAllBtn').addEventListener('click', function() {
      registerAllWords('directExtractedWords', 'directRegisterAllBtn');
    });

    // 一括登録処理の共通関数
    function registerAllWords(containerId, registerBtnId) {
      const wordItems = document.querySelectorAll(`#${containerId} .word-item`);
      if (wordItems.length === 0) {
        alert("登録する単語がありません。");
        return;
      }
      
      // 確認メッセージ
      if (!confirm(`${wordItems.length}個の単語を一括登録します。よろしいですか？`)) {
        return;
      }
      
      let successCount = 0;
      let failCount = 0;
      
      // 進捗表示用
      const progressMsg = document.createElement('div');
      progressMsg.className = 'progress-message';
      progressMsg.textContent = `登録処理中... (0/${wordItems.length})`;
      document.body.appendChild(progressMsg);
      
      const promises = [];
      
      // 言語情報の取得
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      const targetLanguage = document.getElementById('targetLanguage').value;
      
      // 全ての単語アイテムをループ
      wordItems.forEach((item, index) => {
        const word = item.dataset.word;
        const meaning = item.dataset.meaning;
        const note = item.dataset.note;
        const partOfSpeech = item.dataset.partOfSpeech;
        const reading = item.dataset.reading || '';
        
        if (!word) return;
        
        const promise = new Promise((resolve) => {
          let formData = new FormData();
          formData.append('word', word);
          formData.append('meaning', meaning);
          formData.append('note', note);
          formData.append('part_of_speech', partOfSpeech);
          formData.append('sourceLanguage', sourceLanguage);
          formData.append('targetLanguage', targetLanguage);
          
          // 日本語の場合はフリガナも追加
          if (sourceLanguage === '日本語' && reading) {
            formData.append('reading', reading);
          }
          
          fetch('save_word.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.error) {
              failCount++;
              console.error(`登録エラー (${word}): ${data.error}`);
            } else {
              successCount++;
              item.remove();
            }
            
            // 進捗表示更新
            progressMsg.textContent = `登録処理中... (${successCount + failCount}/${wordItems.length})`;
            
            resolve();
          })
          .catch(err => {
            failCount++;
            console.error(`通信エラー (${word}): ${err.message}`);
            resolve();
          });
        });
        
        promises.push(promise);
      });
      
      // 全ての登録処理が完了したら
      Promise.all(promises).then(() => {
        // 進捗メッセージを削除
        progressMsg.remove();
        
        // 結果を表示
        if (failCount === 0) {
          alert(`${successCount}個の単語をすべて登録しました。`);
          // 一括登録ボタンを非表示に
          document.getElementById(registerBtnId).style.display = 'none';
        } else {
          alert(`登録結果: 成功=${successCount}個、失敗=${failCount}個`);
        }
      });
    }
    
    // 手動追加ボタンのイベントリスナー - 直接DBに登録（画像タブ用）
    document.getElementById('addAndRegisterBtn').addEventListener('click', function() {
      handleManualRegistration('newWordInput', 'newMeaningInput', 'newNoteInput', this, 'newPartOfSpeechInput');
    });

    // 手動追加ボタンのイベントリスナー - 直接DBに登録（入力タブ用）
    document.getElementById('directAddAndRegisterBtn').addEventListener('click', function() {
      handleManualRegistration('directNewWordInput', 'directNewMeaningInput', 'directNewNoteInput', this, 'directNewPartOfSpeechInput');
    });

    // 手動登録の共通関数
    function handleManualRegistration(wordInputId, meaningInputId, noteInputId, btnElement, partOfSpeechInputId) {
      const word = document.getElementById(wordInputId).value.trim();
      const meaning = document.getElementById(meaningInputId).value.trim();
      const note = document.getElementById(noteInputId).value.trim();
      const partOfSpeech = document.getElementById(partOfSpeechInputId).value.trim();
      
      if (!word) {
        alert("単語を入力してください");
        return;
      }
      
      // DB登録処理
      let formData = new FormData();
      formData.append('word', word);
      formData.append('meaning', meaning);
      formData.append('note', note);
      formData.append('part_of_speech', partOfSpeech);
      
      // 言語情報を追加
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      const targetLanguage = document.getElementById('targetLanguage').value;
      formData.append('sourceLanguage', sourceLanguage);
      formData.append('targetLanguage', targetLanguage);
      
      // 処理中表示
      const btn = btnElement;
      const originalText = btn.textContent;
      btn.textContent = "登録中...";
      btn.disabled = true;
      
      fetch('save_word.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          alert("登録エラー: " + data.error);
        } else {
          // 成功メッセージ
          showTemporaryMessage(`「${word}」を登録しました`);
          
          // 入力欄をクリア
          document.getElementById(wordInputId).value = '';
          document.getElementById(meaningInputId).value = '';
          document.getElementById(noteInputId).value = '';
          document.getElementById(partOfSpeechInputId).value = '';
        }
        
        // ボタンを元に戻す
        btn.textContent = originalText;
        btn.disabled = false;
      })
      .catch(err => {
        console.error(err);
        alert("通信エラー: " + err.message);
        
        // ボタンを元に戻す
        btn.textContent = originalText;
        btn.disabled = false;
      });
    }
    
    // ファイル選択時のプレビュー表示
    const uploadImage = document.getElementById('uploadImage');
    const previewArea = document.getElementById('previewArea');
    const preview = document.getElementById('preview');
    
    uploadImage.addEventListener('change', function(e) {
      if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          previewArea.style.display = 'block';
        }
        reader.readAsDataURL(this.files[0]);
      }
    });
    
    // 「画像からテキスト抽出」ボタンのイベントリスナー
    document.getElementById('extractTextBtn').addEventListener('click', function() {
      const fileInput = document.getElementById('uploadImage');
      if (!fileInput.files || !fileInput.files[0]) {
        alert("画像ファイルが選択されていません。");
        return;
      }
      
      // 選択された言語を取得
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      
      // 表示名から言語コードを取得
      const languageCode = LanguageCode.getCodeFromName(sourceLanguage);
      
      // FormDataオブジェクトを作成
      const formData = new FormData();
      formData.append('image', fileInput.files[0]);
      formData.append('language', languageCode);
      
      // プロセス開始を表示
      document.getElementById('extractedText').value = "テキスト抽出中...";
      
      // 画像処理用のPHPスクリプトにリクエストを送信
      fetch('process.php', {
        method: 'POST',
        body: formData
      })
      .then(res => {
        // レスポンスがJSON形式かチェック
        const contentType = res.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
          return res.json();
        } else {
          throw new Error('レスポンスがJSON形式ではありません。サーバーエラーが発生している可能性があります。');
        }
      })
      .then(data => {
        if (data.extractedText) {
          document.getElementById('extractedText').value = data.extractedText;
          // 翻訳ボタンを有効化
          document.getElementById('translateBtn').disabled = false;
        } else if (data.error) {
          document.getElementById('extractedText').value = "エラー: " + data.error;
        } else {
          document.getElementById('extractedText').value = "予期しないレスポンス形式です";
        }
      })
      .catch(err => {
        console.error(err);
        document.getElementById('extractedText').value = "通信エラー: " + err.message;
      });
    });
    
    // DB登録用関数
    function saveWordToDB(word, meaning, note, divElement, containerId, registerBtnId, partOfSpeech = '', reading = '') {
      let formData = new FormData();
      formData.append('word', word);
      formData.append('meaning', meaning);
      formData.append('note', note);
      formData.append('part_of_speech', partOfSpeech);
      
      // 言語情報を追加
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      const targetLanguage = document.getElementById('targetLanguage').value;
      formData.append('sourceLanguage', sourceLanguage);
      formData.append('targetLanguage', targetLanguage);
      
      // 日本語の場合はフリガナも追加
      if (sourceLanguage === '日本語' && reading) {
        formData.append('reading', reading);
      }
      
      fetch('save_word.php', {
        method: 'POST',
        body: formData
      })
      .then(res => {
        // レスポンスがJSON形式かチェック
        const contentType = res.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
          return res.json();
        } else {
          throw new Error('レスポンスがJSON形式ではありません。サーバーエラーが発生している可能性があります。');
        }
      })
      .then(data => {
        if (data.error) {
          alert("登録エラー: " + data.error);
        } else {
          // 登録完了メッセージ
          showTemporaryMessage(`「${word}」を登録しました`);
          
          // 表示から削除
          if (divElement) {
            divElement.remove();
            
            // 残りの単語がなければ一括登録ボタンを非表示に
            if (document.querySelectorAll(`#${containerId} .word-item`).length === 0) {
              document.getElementById(registerBtnId).style.display = 'none';
            }
          }
        }
      })
      .catch(err => {
        console.error(err);
        alert("通信エラー: " + err.message);
      });
    }
    
    // 一時的なメッセージを表示する関数
    function showTemporaryMessage(message) {
      // メッセージコンテナがなければ作成
      let container = document.querySelector('.message-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'message-container';
        document.body.appendChild(container);
      }
      
      // メッセージ要素を作成
      const messageEl = document.createElement('div');
      messageEl.className = 'feedback-message';
      messageEl.textContent = message;
      container.appendChild(messageEl);
      
      // 一定時間後に削除
      setTimeout(() => {
        messageEl.remove();
      }, 3000);
    }

    /* 単語入力フォームを生成する関数 */
    function createWordInputForm(id) {
      return `
        <div class="form-row" id="word-form-${id}">
          <div class="form-group col-md-3">
            <label for="word-${id}">単語:</label>
            <input type="text" class="form-control" id="word-${id}" placeholder="単語を入力">
          </div>
          <div class="form-group col-md-3 reading-field" style="display: none;">
            <label for="reading-${id}">フリガナ:</label>
            <input type="text" class="form-control" id="reading-${id}" placeholder="フリガナを入力">
          </div>
          <div class="form-group col-md-3">
            <label for="part_of_speech-${id}">品詞:</label>
            <input type="text" class="form-control" id="part_of_speech-${id}" placeholder="品詞">
          </div>
          <div class="form-group col-md-3">
            <label for="meaning-${id}">意味:</label>
            <input type="text" class="form-control" id="meaning-${id}" placeholder="意味を入力">
          </div>
          <div class="form-group col-md-2">
            <label for="note-${id}">備考:</label>
            <input type="text" class="form-control" id="note-${id}" placeholder="備考">
          </div>
          <div class="form-group col-md-1 d-flex align-items-end">
            <button class="btn btn-primary" onclick="registerWord('${id}', this)">登録</button>
          </div>
        </div>
      `;
    }

    function registerWord(id, btnElement) {
      const wordInputId = `word-${id}`;
      const meaningInputId = `meaning-${id}`;
      const noteInputId = `note-${id}`;
      const partOfSpeechInputId = `part_of_speech-${id}`;
      const readingInputId = `reading-${id}`;
      
      const word = document.getElementById(wordInputId).value.trim();
      const meaning = document.getElementById(meaningInputId).value.trim();
      const note = document.getElementById(noteInputId).value.trim();
      const partOfSpeech = document.getElementById(partOfSpeechInputId).value.trim();
      
      if (!word) {
        alert("単語を入力してください");
        return;
      }
      
      // DB登録処理
      let formData = new FormData();
      formData.append('word', word);
      formData.append('meaning', meaning);
      formData.append('note', note);
      formData.append('part_of_speech', partOfSpeech);
      
      // 言語情報を追加
      const sourceLanguage = document.getElementById('sourceLanguage').value;
      const targetLanguage = document.getElementById('targetLanguage').value;
      formData.append('sourceLanguage', sourceLanguage);
      formData.append('targetLanguage', targetLanguage);
      
      // 日本語の場合のみフリガナも追加
      if (sourceLanguage === '日本語') {
        const reading = document.getElementById(readingInputId).value.trim();
        formData.append('reading', reading);
      }
      
      // 処理中表示
      const btn = btnElement;
      const originalText = btn.textContent;
      btn.textContent = "登録中...";
      btn.disabled = true;
      
      fetch('save_word.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          alert("登録エラー: " + data.error);
        } else {
          // 成功メッセージ
          showTemporaryMessage(`「${word}」を登録しました`);
          
          // 入力欄をクリア
          document.getElementById(wordInputId).value = '';
          document.getElementById(meaningInputId).value = '';
          document.getElementById(noteInputId).value = '';
          document.getElementById(partOfSpeechInputId).value = '';
          if (sourceLanguage === '日本語') {
            document.getElementById(readingInputId).value = '';
          }
        }
        
        // ボタンを元に戻す
        btn.textContent = originalText;
        btn.disabled = false;
      })
      .catch(err => {
        console.error(err);
        
        // エラー時もボタンを元に戻す
        btn.textContent = originalText;
        btn.disabled = false;
        alert("エラーが発生しました。詳細はコンソールを確認してください。");
      });
    }
  </script>
  
  <!-- メッセージ表示用コンテナ -->
  <div class="message-container"></div>
</body>
</html>
