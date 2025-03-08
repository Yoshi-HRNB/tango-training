<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8" />
  <title>単語抽出・辞書登録</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css" />
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>写真から単語追加</h1>
      
      <div class="nav-links mb-3">
        <a href="add.php">通常の単語登録へ</a>
        <a href="list.php">単語一覧へ</a>
        <a href="../index.php">トップへ戻る</a>
      </div>
      
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
      </div>

      <!-- 抽出結果パート -->
      <div class="section card mb-3">
        <h2>2. 抽出結果</h2>
        <div class="form-group">
          <textarea id="extractedText" placeholder="画像から抽出されたテキストがここに表示されます" rows="4"></textarea>
        </div>
        <button id="translateBtn" class="btn btn-primary">単語を翻訳・抽出</button>
        <div id="resultArea" class="result-area mt-3" style="display: none;">
          <h3>翻訳・抽出された単語</h3>
          <div id="extractedWords" class="word-list"></div>
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
          <label for="newMeaningInput">意味:</label>
          <input type="text" id="newMeaningInput" placeholder="意味を入力">
        </div>
        <div class="form-group">
          <label for="newNoteInput">メモ:</label>
          <input type="text" id="newNoteInput" placeholder="メモを入力 (任意)">
        </div>
        <button id="addWordBtn" class="btn btn-success">単語を追加</button>
      </div>

      <!-- 登録待ちリスト -->
      <div class="section card">
        <h2>4. 登録待ちリスト</h2>
        <p>下記の単語を「登録」ボタンでデータベースに保存します</p>
        <ul id="wordsToRegister" class="mt-2"></ul>
      </div>
    </div>
  </div>

  <script>
    // 既存のJavaScriptコードはそのまま残す
    // ただし、UIの更新部分を以下のように変更
    
    // 抽出テキストを表示する部分
    function displayExtractedText(text) {
      document.getElementById('extractedText').value = text;
      document.getElementById('translateBtn').disabled = !text;
      
      // 結果エリアを非表示に
      document.getElementById('resultArea').style.display = 'none';
    }
    
    // 抽出された単語リストを表示
    function displayExtractedWords(words) {
      const container = document.getElementById('extractedWords');
      container.innerHTML = '';
      
      if (words && words.length > 0) {
        words.forEach(word => {
          const div = document.createElement('div');
          div.className = 'word-item';
          div.innerHTML = `
            <h3>${word.word}</h3>
            <p>${word.meaning || '意味なし'}</p>
            ${word.note ? `<p class="text-light">${word.note}</p>` : ''}
            <div class="flex gap-2 mt-2">
              <button class="btn btn-primary btn-sm add-word-btn">追加</button>
              <button class="btn btn-danger btn-sm delete-btn">削除</button>
            </div>
          `;
          
          // 追加ボタンのイベント
          div.querySelector('.add-word-btn').addEventListener('click', () => {
            addExtractedWordToPendingList(word.word, word.meaning || '', word.note || '');
          });
          
          // 削除ボタンのイベント
          div.querySelector('.delete-btn').addEventListener('click', () => {
            div.remove();
          });
          
          container.appendChild(div);
        });
        
        document.getElementById('resultArea').style.display = 'block';
      } else {
        container.innerHTML = '<p>単語が見つかりませんでした</p>';
        document.getElementById('resultArea').style.display = 'block';
      }
    }
    
    // 登録待ちリストに単語を追加
    function addExtractedWordToPendingList(word, meaning, note) {
      const li = document.createElement('li');
      li.className = 'word-item';
      li.innerHTML = `
        <div class="form-group mb-2">
          <label>単語:</label>
          <input type="text" class="word-input" value="${word}" />
        </div>
        <div class="form-group mb-2">
          <label>訳:</label>
          <input type="text" class="meaning-input" value="${meaning}" placeholder="訳" />
        </div>
        <div class="form-group mb-2">
          <label>補足:</label>
          <input type="text" class="note-input" value="${note}" placeholder="補足 (任意)" />
        </div>
        <button class="register-btn btn btn-primary">登録</button>
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
  </script>
</body>
</html>
