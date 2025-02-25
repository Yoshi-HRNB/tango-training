<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>単語抽出・辞書登録アプリ デモ (カメラ削除版)</title>

  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background-color: #f4f4f4;
    }
    h1, h2, h3 {
      color: #333;
      text-align: center;
    }
    .section {
      background: #fff;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 6px;
      box-shadow: 0 0 5px rgba(0,0,0,0.1);
    }
    label, select, input[type="file"] {
      display: block;
      margin-bottom: 10px;
    }
    textarea, input[type="text"] {
      width: 100%;
      padding: 10px;
      font-size: 16px;
      border: 1px solid #ccc;
      margin-bottom: 10px;
      border-radius: 4px;
    }
    button {
      padding: 10px 20px;
      font-size: 16px;
      margin-right: 5px;
      cursor: pointer;
    }
    .result-area {
      background: #e9ecef;
      padding: 15px;
      margin-top: 10px;
      border-radius: 4px;
    }
    .word-list {
      margin-top: 20px;
      background: #fff;
      padding: 15px;
      border-radius: 4px;
    }
    ul {
      list-style-type: none;
      padding-left: 0;
    }
    li {
      margin-bottom: 10px;
    }
    .word-item {
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid #eee;
      padding: 8px 0;
      flex-wrap: wrap;
    }
    .word-item:last-child {
      border-bottom: none;
    }
    .delete-button {
      background-color: #dc3545;
      color: white;
    }
    .register-btn {
      background-color: #007bff;
      color: white;
    }
    .meaning-input,
    .note-input {
      flex: 1;
    }
    .back-link {
      display: block;
      margin-bottom: 20px;
      text-align: center;
      font-size: 16px;
      color: #007bff;
      text-decoration: none;
    }

    /* スマホ用レイアウト */
    @media (max-width: 600px) {
      body {
        margin: 10px;
      }
      .section {
        padding: 15px;
      }
      label, select, input[type="file"], textarea, input[type="text"], button {
        font-size: 18px;
        padding: 14px;
      }
      h1, h2, h3 {
        font-size: 22px;
      }
      .word-item {
        flex-direction: column;
        align-items: flex-start;
      }
      .word-item input {
        width: 100%;
        margin-bottom: 10px;
      }
      .register-btn, .delete-button {
        width: 100%;
        margin-top: 10px;
        padding: 14px;
        font-size: 18px;
      }
      .back-link {
        font-size: 20px;
      }
    }
  </style>
</head>
<body>

<h1>単語抽出・辞書登録アプリ デモ (カメラ削除版)</h1>

<!-- トップ画面に戻るリンク -->
<a href="#" id="backLink" class="back-link">トップ画面に戻る</a>

<!-- 1) 画像アップロード or テキスト入力 -->
<div class="section">
  <h2>画像からテキスト抽出 (または手動入力)</h2>
  
  <!-- 画像アップロードフォーム -->
  <form id="imageForm" enctype="multipart/form-data">
    <label for="imageFile">画像ファイルを選択:</label>
    <input type="file" id="imageFile" name="image" accept="image/*" />
    
    <label for="langHint">Vision API用 言語ヒント:</label>
    <select id="langHint" name="language">
      <option value="en">英語</option>
      <option value="es">スペイン語</option>
      <option value="fr">フランス語</option>
      <option value="de">ドイツ語</option>
      <option value="zh">中国語</option>
      <option value="ja">日本語</option>
      <option value="vi">ベトナム語</option>
    </select>
    <button type="submit">画像からテキスト抽出</button>
  </form>
  
  <p>▼ または手動で文章を入力</p>
  <textarea id="inputText" rows="4" placeholder="ここに文章を入力 (抽出結果が自動で入ります)"></textarea>
  
  <div class="result-area" id="imageResult" style="display:none;">
    <h3>抽出結果</h3>
    <p id="extractedText"></p>
  </div>
</div>

<!-- 2) 翻訳＆単語抽出フォーム -->
<div class="section">
  <h2>翻訳と単語抽出</h2>
  <form id="translateForm">
    <label for="sourceLanguage">入力テキストの言語:</label>
    <select id="sourceLanguage" name="sourceLanguage">
      <option value="英語" selected>英語</option>
      <option value="ベトナム語">ベトナム語</option>
      <option value="フランス語">フランス語</option>
      <option value="ドイツ語">ドイツ語</option>
      <option value="スペイン語">スペイン語</option>
    </select>
    <label for="levelSelect">単語抽出レベル (1〜10):</label>
    <select id="levelSelect" name="level">
      <!-- PHP で動的に生成できなければ、HTMLベタ書きでもOK -->
      <?php for($i=1;$i<=10;$i++): ?>
        <option value="<?= $i ?>">レベル <?= $i ?></option>
      <?php endfor; ?>
    </select>
    <button type="submit">翻訳＆抽出</button>
  </form>
  
  <div id="translateResult" class="result-area" style="display:none;">
    <h3>翻訳結果</h3>
    <div id="translatedText"></div>
    <h3>抽出された単語リスト</h3>
    <ul id="extractedWords"></ul>
  </div>
</div>

<!-- 3) 単語リスト（手動追加＋登録） -->
<div class="section">
  <h2>単語の追加・登録</h2>
  
  <!-- 手動追加 -->
  <div>
    <input type="text" id="newWordInput" placeholder="追加する単語" />
    <input type="text" id="newMeaningInput" placeholder="訳 / 意味" />
    <input type="text" id="newNoteInput" placeholder="補足 (任意)" />
    <button id="addWordBtn" class="register-btn">リストに追加</button>
  </div>

  <!-- 登録待ちリスト -->
  <div class="word-list">
    <h3>▼ 登録待ちの単語</h3>
    <ul id="wordsToRegister"></ul>
  </div>
</div>

<script>
/********************************************************************
 * トップ画面に戻るリンクのイベントリスナー
 ********************************************************************/
const backLink = document.getElementById('backLink');
backLink.addEventListener('click', function(e) {
  e.preventDefault();
  window.location.href = '../index.php'; // トップ画面のURLに変更してください
});

/********************************************************************
 * 1) 画像アップロード → テキスト抽出
 ********************************************************************/
const imageForm = document.getElementById('imageForm');
const imageFile = document.getElementById('imageFile');
const langHint = document.getElementById('langHint');
const imageResult = document.getElementById('imageResult');
const extractedText = document.getElementById('extractedText');
const inputTextArea = document.getElementById('inputText');

imageForm.addEventListener('submit', function(e) {
  e.preventDefault();
  // if (!imageFile.files[0]) {
  //   alert("画像ファイルが選択されていません。");
  //   return;
  // }
  let formData = new FormData();
  formData.append('image', imageFile.files[0]);
  formData.append('language', langHint.value);
  
  fetch('process.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    imageResult.style.display = 'block';
    if (data.extractedText) {
      extractedText.textContent = data.extractedText;
      inputTextArea.value = data.extractedText;
    } else if(data.error) {
      extractedText.textContent = 'エラー: ' + data.error;
    }
  })
  .catch(err => {
    imageResult.style.display = 'block';
    extractedText.textContent = 'エラー: ' + err;
  });
});


/********************************************************************
 * 2) 翻訳＆単語抽出 → translate.php
 ********************************************************************/
const translateForm    = document.getElementById('translateForm');
const sourceLanguage   = document.getElementById('sourceLanguage');
const levelSelect      = document.getElementById('levelSelect');
const translateResult  = document.getElementById('translateResult');
const translatedText   = document.getElementById('translatedText');
const extractedWords   = document.getElementById('extractedWords');

translateForm.addEventListener('submit', function(e) {
  e.preventDefault();
  const text = inputTextArea.value.trim();
  if (!text) {
    alert("文章が入力されていません。");
    return;
  }
  let formData = new FormData();
  formData.append('text', text);
  formData.append('sourceLanguage', sourceLanguage.value);
  formData.append('level', levelSelect.value);

  fetch('translate.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.error) {
      alert(data.error);
      return;
    }
    translateResult.style.display = 'block';
    translatedText.textContent = data.translated_text || '(翻訳結果なし)';

    extractedWords.innerHTML = '';
    if (data.extracted_words && data.extracted_words.length > 0) {
      data.extracted_words.forEach(wordObj => {
        // 表示上はわかりやすいよう
        const li = document.createElement('li');
        li.textContent = wordObj.word + " : " + wordObj.meaning;
        extractedWords.appendChild(li);
        
        // 「登録待ちリスト」にも追加
        addExtractedWordToPendingList(wordObj.word, wordObj.meaning, "");
      });
    } else {
      extractedWords.innerHTML = '<li>抽出された単語はありません。</li>';
    }
  })
  .catch(err => {
    alert("通信エラー: " + err);
  });
});


/********************************************************************
 * 3) 単語リスト（登録待ち → DB保存）
 ********************************************************************/

// 登録待ちリスト <ul> の要素
const wordsToRegisterUl = document.getElementById('wordsToRegister');

// 「翻訳抽出」や「手動追加」で得た単語を登録待ちリストに追加
function addExtractedWordToPendingList(word, meaning, note) {
  const li = document.createElement('li');
  li.className = 'word-item';
  li.innerHTML = `
    <input type="text" class="word-input" value="${word}" />
    <input type="text" class="meaning-input" value="${meaning}" placeholder="訳" />
    <input type="text" class="note-input" value="${note}" placeholder="補足 (任意)" />
    <button class="register-btn">登録</button>
  `;
  wordsToRegisterUl.appendChild(li);

  // 「登録」ボタン押下時、DBへ保存
  li.querySelector('.register-btn').addEventListener('click', () => {
    const w = li.querySelector('.word-input').value.trim();
    const m = li.querySelector('.meaning-input').value.trim();
    const n = li.querySelector('.note-input').value.trim();

    if (!w) {
      alert("単語が空です。");
      return;
    }
    saveWordToDB(w, m, n, li);
  });
}

// DB登録用
function saveWordToDB(word, meaning, note, liElement) {
  let formData = new FormData();
  formData.append('word', word);
  formData.append('meaning', meaning);
  formData.append('note', note);
  formData.append('language_code', 'vi'); // 例: 英語を想定（必要に応じて変更）
  // user_id はサーバー側でセッション等から取得するか、ここで送るか検討

  fetch('save_word.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.error) {
      alert("登録エラー: " + data.error);
    } else {
      alert("登録しました: word_id=" + data.word_id);
      // 登録待ちリストから削除
      liElement.remove();
    }
  })
  .catch(err => {
    alert("通信エラー: " + err);
  });
}

// 手動追加の入力欄
const newWordInput   = document.getElementById('newWordInput');
const newMeaningInput= document.getElementById('newMeaningInput');
const newNoteInput   = document.getElementById('newNoteInput');
const addWordBtn     = document.getElementById('addWordBtn');

addWordBtn.addEventListener('click', () => {
  const w = newWordInput.value.trim();
  const m = newMeaningInput.value.trim();
  const n = newNoteInput.value.trim();
  if (!w) {
    alert("単語を入力してください。");
    return;
  }
  addExtractedWordToPendingList(w, m, n);
  newWordInput.value = "";
  newMeaningInput.value = "";
  newNoteInput.value = "";
});
</script>

</body>
</html>
