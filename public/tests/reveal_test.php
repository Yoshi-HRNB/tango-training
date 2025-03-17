<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>フラッシュカード式テスト</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    :root {
      --primary-color: #4a6da7;
      --primary-light: #f0f4f8;
      --primary-dark: #3a5d97;
      --success-color: #4caf50;
      --success-dark: #43a047;
      --danger-color: #f44336;
      --danger-dark: #e53935;
      --gray-light: #f9fafc;
      --gray-medium: #e4e8f0;
      --text-color: #333;
      --text-secondary: #666;
      --card-shadow: 0 4px 15px rgba(0,0,0,0.08);
      --header-shadow: 0 2px 8px rgba(0,0,0,0.1);
      --spacing-small: 8px;
      --spacing-medium: 15px;
      --spacing-large: 30px;
      --border-radius: 8px;
      --card-radius: 12px;
    }

    body {
      font-family: 'Noto Sans JP', sans-serif;
      background-color: #f8f9fa;
      color: var(--text-color);
      line-height: 1.6;
      padding: 0;
      margin: 0;
    }
    
    /* コンテナのスタイル */
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 10px 15px 30px;
      position: relative;
    }
    
    /* プログレスコンテナのスタイル */
    #progress-container {
      width: 100%;
      max-width: 600px;
      margin: 10px auto 25px; /* マージンを調整 */
      padding: 10px 15px;
      background-color: #fff;
      border-radius: var(--border-radius);
      box-shadow: var(--header-shadow);
      position: relative;
      z-index: 95; /* カードより上のレイヤーに */
    }
    
    .progress-bar {
      width: 100%;
      background-color: #e9ecef;
      height: 8px;
      border-radius: 4px;
      margin-bottom: 8px;
    }
    
    .progress-fill {
      background-color: var(--primary-color);
      height: 100%;
      border-radius: 4px;
      width: 0;
      transition: width 0.3s ease;
    }
    
    #progress-text {
      font-size: 0.9rem;
      margin: 0;
      color: var(--text-secondary);
      text-align: center;
    }
    
    /* ヘルプボタンのスタイル */
    .help-button {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: var(--primary-light);
      color: var(--primary-color);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      cursor: pointer;
      box-shadow: var(--header-shadow);
      z-index: 100;
      border: 1px solid #e0e4e8;
      transition: all 0.2s ease;
    }
    
    .help-button:hover {
      background-color: #e8eef4;
      transform: scale(1.05);
    }
    
    /* カードエリアのスタイル */
    #cards-area {
      display: block; /* PC表示ではブロック表示に */
      width: 100%;
      margin: 30px auto; /* マージンを増やす */
      position: relative;
      z-index: 5;
    }
    
    /* カードコンテナのスタイル */
    .card-container {
      perspective: 1000px;
      margin: 10px auto 30px; /* マージンを調整 */
      max-width: 600px;
      width: 100%;
      overflow: visible;
      display: block; /* PC表示ではブロック表示に */
      padding: 10px 0;
      position: relative;
      z-index: 10;
    }
    
    .card-flip {
      position: relative;
      width: 100%;
      min-height: 250px;
      transition: transform 0.6s;
      transform-style: preserve-3d;
      cursor: pointer;
      transform-origin: center center;
      margin: 0 auto;
    }
    
    .card-flip.is-flipped {
      transform: rotateY(180deg);
    }
    
    /* カードフェイス共通設定 */
    .card-face {
      position: absolute;
      width: 100%;
      height: auto;
      min-height: 210px; /* 少し小さめに調整 */
      max-height: 400px; /* 最大高さを制限 */
      backface-visibility: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      border-radius: var(--card-radius);
      box-shadow: var(--card-shadow);
      padding: 20px; /* パディングを少し小さめに */
      overflow: visible;
      box-sizing: border-box;
    }
    
    .card-face > div {
      width: 100%;
      overflow-wrap: break-word;
      word-break: break-all;
    }
    
    .card-front {
      background-color: var(--gray-light);
      border: 1px solid var(--gray-medium);
    }
    
    .card-back {
      background-color: var(--primary-light);
      transform: rotateY(180deg);
      border: 1px solid var(--gray-medium);
    }
    
    /* ボタンコンテナのスタイル */
    .button-container {
      display: flex;
      justify-content: center;
      gap: 20px;
      width: 100%;
      max-width: 600px;
      margin: 10px auto 20px; /* マージンを調整 */
      padding: 15px 0;
      background-color: #fff;
      border-radius: var(--border-radius);
      box-shadow: var(--card-shadow);
    }
    
    .answer-button {
      padding: 12px 30px;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      min-width: 120px;
    }
    
    .correct-button {
      background-color: var(--success-color);
      color: white;
    }
    
    .correct-button:hover {
      background-color: var(--success-dark);
      transform: translateY(-2px);
    }
    
    .wrong-button {
      background-color: var(--danger-color);
      color: white;
    }
    
    .wrong-button:hover {
      background-color: var(--danger-dark);
      transform: translateY(-2px);
    }
    
    /* メッセージのスタイル */
    #message {
      margin: 15px auto;
      max-width: 600px;
      text-align: center;
      border-radius: var(--border-radius);
      padding: 15px;
      font-weight: 600;
      font-size: 1.1rem;
      position: fixed; /* 位置を固定 */
      top: 50%; /* 上から50% */
      left: 50%; /* 左から50% */
      transform: translate(-50%, -50%); /* 中央に配置 */
      z-index: 110; /* 最上位レイヤーに */
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); /* 影を追加 */
      min-width: 250px; /* 最小幅を設定 */
      display: none; /* 初期状態では非表示 */
    }
    
    #message.show {
      display: block; /* 表示する */
      animation: fadeInScale 0.3s ease forwards;
    }
    
    @keyframes fadeInScale {
      0% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.9);
      }
      100% {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
      }
    }
    
    /* 結果表示エリアのスタイル */
    #result-area {
      margin: 30px auto;
      max-width: 600px;
      text-align: center;
      padding: 30px 20px;
      background-color: #fff;
      border-radius: var(--card-radius);
      box-shadow: var(--card-shadow);
    }
    
    /* 送信後エリアのスタイル */
    #post-submit-area {
      margin: 30px auto;
      max-width: 600px;
      text-align: center;
    }
    
    /* ヘルプモーダルのスタイル */
    .help-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 200;
      align-items: center;
      justify-content: center;
    }
    
    .help-modal.active {
      display: flex;
    }
    
    .help-content {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      max-width: 500px;
      width: 90%;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .help-content h3 {
      color: #4a6da7;
      margin-top: 0;
      margin-bottom: 15px;
      border-bottom: 1px solid #eee;
      padding-bottom: 8px;
    }
    
    .help-section {
      margin-bottom: 15px;
    }
    
    .help-section h4 {
      margin-bottom: 8px;
      color: #333;
    }
    
    .close-help {
      background-color: #f0f0f0;
      border: none;
      padding: 8px 15px;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 10px;
      color: #333;
    }
    
    .close-help:hover {
      background-color: #e0e0e0;
    }
    
    /* 共通テキストスタイル */
    .translation-item, .note-section {
      width: 100%;
      display: block;
      margin-bottom: 15px;
      word-wrap: break-word;
      overflow-wrap: break-word;
      word-break: break-all;
      text-align: left;
    }
    
    .translation-text {
      font-size: 1.6rem;
      font-weight: 600;
      margin-bottom: 12px;
      word-wrap: break-word;
      overflow-wrap: break-word;
      word-break: break-all;
      text-align: center;
      width: 100%;
      display: block;
      color: var(--text-color);
    }
    
    .reading-text {
      color: var(--text-secondary);
      font-size: 1rem;
      margin-left: 0;
      margin-bottom: 10px;
      word-wrap: break-word;
      overflow-wrap: break-word;
      word-break: break-all;
      text-align: center;
      width: 100%;
      display: block;
      background-color: var(--gray-light);
      padding: 8px 12px;
      border-radius: 6px;
    }
    
    .note-section {
      border-top: 1px solid #dee2e6;
      padding-top: 15px;
      margin-top: 15px;
    }
    
    .note-header {
      display: flex;
      align-items: center;
      margin-bottom: 8px;
    }
    
    .note-title {
      font-weight: bold;
      color: var(--primary-color);
      margin-bottom: 0;
      margin-right: 10px;
      font-size: 1rem;
    }
    
    .part-of-speech {
      color: #8a9aaf;
      font-size: 0.85rem;
      background-color: #f5f7fa;
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      margin: 0;
      max-width: 100%;
      word-wrap: break-word;
      overflow-wrap: break-word;
      word-break: break-all;
      text-align: left;
      opacity: 0.8;
    }
    
    .note-content {
      background-color: var(--gray-light);
      padding: 12px;
      border-radius: 6px;
      font-size: 0.95rem;
      line-height: 1.5;
    }
    
    .keyboard-shortcuts {
      margin-top: 10px;
      font-size: 0.85rem;
      color: #555;
      background-color: #f5f5f5;
      padding: 5px 8px;
      border-radius: 3px;
      display: inline-block;
    }
    
    .keyboard-shortcuts kbd {
      background-color: #f8f9fa;
      border: 1px solid #d1d5da;
      border-radius: 3px;
      box-shadow: 0 1px 0 rgba(0,0,0,0.2);
      color: #444d56;
      display: inline-block;
      font-family: monospace;
      font-size: 0.8rem;
      line-height: 1;
      padding: 3px 5px;
      margin: 0 2px;
    }
    
    /* ボタンフィードバック効果 */
    .feedback-effect {
      transform: scale(1.1);
      transition: transform 0.2s ease;
    }
    
    /* 結果エリアのスタイル調整 */
    .flex {
      display: flex;
      justify-content: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    
    .btn {
      padding: 10px 20px;
      border-radius: 6px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      min-width: 130px;
      display: inline-block;
      text-align: center;
      text-decoration: none;
    }
    
    .btn-primary {
      background-color: var(--primary-color);
      color: white;
      border: none;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
    }
    
    .btn-warning {
      background-color: #ff9800;
      color: white;
      border: none;
    }
    
    .btn-warning:hover {
      background-color: #f08c00;
      transform: translateY(-2px);
    }
    
    .btn-info {
      background-color: #2196f3;
      color: white;
      border: none;
    }
    
    .btn-info:hover {
      background-color: #0b86e3;
      transform: translateY(-2px);
    }
    
    .btn-outline {
      background-color: transparent;
      color: var(--primary-color);
      border: 1px solid var(--primary-color);
    }
    
    .btn-outline:hover {
      background-color: var(--primary-light);
      transform: translateY(-2px);
    }
    
    .alert {
      padding: 12px 15px;
      border-radius: 6px;
    }
    
    .alert-success {
      background-color: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
    }
    
    .alert-danger {
      background-color: #ffebee;
      color: #c62828;
      border: 1px solid #ffcdd2;
    }
    
    .mt-3 {
      margin-top: 20px;
    }
    
    .text-center {
      text-align: center;
    }
    
    /* レスポンシブ対応 */
    @media (max-width: 768px) {
      .help-button {
        display: none;
      }
      
      .container {
        padding: 70px 10px 100px; /* 上部と下部のパディングを調整 */
      }
      
      #progress-container {
        padding: 10px;
        margin: 0;
        position: fixed; /* 固定配置に変更 */
        top: 0; /* 画面上部に固定 */
        left: 0;
        right: 0;
        background-color: rgba(255, 255, 255, 0.98); /* 半透明背景 */
        box-shadow: var(--header-shadow);
        border-radius: 0; /* 角丸をなくす */
        z-index: 100; /* より高いz-indexを設定 */
        height: auto; /* 高さを自動調整 */
      }
      
      #cards-area {
        display: block; /* スマホでもブロック表示に変更 */
        margin-top: 60px; /* プログレスバーの下に十分なスペースを確保 */
      }
      
      .card-container {
        display: block; /* スマホでもブロック表示に変更 */
        padding: 5px 0;
        margin: 0 auto 80px; /* 下側マージンをさらに増やす */
      }
      
      .card-face {
        padding: 15px; /* パディングを小さく */
        min-height: 180px; /* 最小高さをさらに小さく */
      }
      
      .button-container {
        position: fixed; /* スマホでのみ固定位置に */
        bottom: 0;
        left: 0;
        right: 0;
        max-width: 100%; /* 幅を100%に */
        margin: 0; /* マージンをリセット */
        background-color: rgba(255, 255, 255, 0.98);
        padding: 10px 0;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        z-index: 100; /* より高いz-indexを設定 */
        border-radius: 0; /* 角丸をなくす */
      }
      
      .answer-button {
        padding: 12px 0;
        min-width: 120px;
        font-size: 0.95rem;
      }
      
      #message {
        width: 85%; /* スマホでの幅を調整 */
        min-width: auto; /* 最小幅をリセット */
        padding: 12px;
        z-index: 105; /* 他の要素より上に表示 */
      }
    }
    
    @media (max-width: 480px) {
      .card-face {
        padding: 15px 12px;
      }
      
      .answer-button {
        min-width: 110px;
        padding: 10px 0;
      }
      
      .translation-text {
        font-size: 1.1rem;
      }
      
      .reading-text, .part-of-speech, .note-content {
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- ヘルプボタン -->
    <button class="help-button" id="helpButton">?</button>
    
    <!-- プログレスバー -->
    <div id="progress-container" style="display: none;">
      <div class="progress-bar">
        <div id="progress-fill" class="progress-fill" style="width: 0%"></div>
      </div>
      <p id="progress-text">0 / 0 問</p>
    </div>
    
    <!-- カードエリア -->
    <div id="cards-area"></div>
    
    <!-- メッセージ表示エリア -->
    <div id="message" class="alert" style="display: none;"></div>
    
    <!-- 結果表示エリア -->
    <div id="result-area" style="display: none;">
      <div class="alert alert-success">
        <h3 class="text-center">テスト完了！</h3>
        <p class="text-center" id="final-score">正解: 0 / 0 問</p>
      </div>
      <div class="flex justify-center gap-2 mt-3">
        <button id="submitBtn" class="btn btn-primary">結果を保存</button>
        <a href="../index.php" class="btn btn-outline">トップへ戻る</a>
      </div>
    </div>
    
    <!-- テスト後のオプションエリア -->
    <div id="post-submit-area" style="display: none;">
      <div class="flex justify-center gap-2 mt-3">
        <button id="retryBtn" style="display: none;" class="btn btn-warning" onclick="doRetryTest()">間違えた単語を再テスト</button>
        <button id="summaryBtn" style="display: none;" class="btn btn-info" onclick="showSummary()">サマリを見る</button>
        <a href="../index.php" class="btn btn-outline">トップへ戻る</a>
      </div>
    </div>
  </div>
  
  <!-- ヘルプモーダル -->
  <div class="help-modal" id="helpModal">
    <div class="help-content">
      <h3>操作説明</h3>
      
      <div class="help-section">
        <h4>基本操作</h4>
        <p>カードをクリックすると裏面(訳)を表示できます。</p>
        <p>「正解」または「不正解」ボタンを押して次へ進んでください。</p>
      </div>
      
      <div class="help-section">
        <h4>キーボード操作</h4>
        <div class="keyboard-shortcuts">
          <span><kbd>←</kbd><kbd>→</kbd> カードを裏返す</span>
        </div>
        <div class="keyboard-shortcuts">
          <span><kbd>1</kbd> 正解　<kbd>2</kbd> 不正解</span>
        </div>
      </div>
      
      <button class="close-help" id="closeHelp">閉じる</button>
    </div>
  </div>

  <!-- 正解・不正解ボタン -->
  <div class="button-container" id="answerButtonsContainer" style="display: none;">
    <button onclick="markCorrect(currentIndex)" class="answer-button correct-button">正解</button>
    <button onclick="markWrong(currentIndex)" class="answer-button wrong-button">不正解</button>
  </div>

  <script>
    let words = [];       // サーバーから取得した問題一覧
    let userChecks = [];  // 各問題に対するユーザの解答(正解=true/不正解=false/未回答=null)
    let currentIndex = 0; // 現在表示中のカードインデックス

    // テスト開始時刻
    const startTime = Date.now();

    window.onload = async () => {
      await loadQuestions();
      setupHelpModal();
    };
    
    /**
     * ヘルプモーダルの設定
     */
    function setupHelpModal() {
      const helpButton = document.getElementById('helpButton');
      const helpModal = document.getElementById('helpModal');
      const closeHelp = document.getElementById('closeHelp');
      
      // ヘルプボタンクリックでモーダル表示
      helpButton.addEventListener('click', function() {
        helpModal.classList.add('active');
      });
      
      // 閉じるボタンクリックでモーダル非表示
      closeHelp.addEventListener('click', function() {
        helpModal.classList.remove('active');
      });
      
      // モーダルの外側クリックでも閉じる
      helpModal.addEventListener('click', function(e) {
        if (e.target === helpModal) {
          helpModal.classList.remove('active');
        }
      });
      
      // ESCキーでも閉じる
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && helpModal.classList.contains('active')) {
          helpModal.classList.remove('active');
        }
      });
    }

    /**
     * 問題をfetchで取得する
     */
    async function loadQuestions() {
      try {
        // fetch_test_words.php にアクセスし、問題一覧を取得
        const res = await fetch('fetch_test_words.php');
        if (!res.ok) {
          // エラーの場合
          let errorMsg = '問題取得失敗';
          try {
            const errorData = await res.json();
            errorMsg += `: ${errorData.error}`;
          } catch(e) {
            // JSONデコード失敗時
          }
          document.getElementById('cards-area').innerText = errorMsg;
          return;
        }
        // JSONをパース
        words = await res.json();

        // userChecks 配列を問題数に合わせて初期化 (null=未回答)
        userChecks = new Array(words.length).fill(null);

        // もし問題が0件なら終了
        if (words.length === 0) {
          document.getElementById('cards-area').innerText = '問題がありません。';
          document.getElementById('progress-container').style.display = 'none';
          document.getElementById('answerButtonsContainer').style.display = 'none';
          return;
        }

        // UI要素を表示
        document.getElementById('progress-container').style.display = 'block';
        document.getElementById('answerButtonsContainer').style.display = 'flex';

        // プログレスバーの初期化
        updateProgress();

        // 最初のカードを表示
        renderCard(currentIndex);
        
        // キーボード操作のイベントリスナーを追加
        setupKeyboardControls();
      } catch (err) {
        console.error(err);
        document.getElementById('cards-area').innerText = '問題取得エラー: ' + err.message;
      }
    }
    
    /**
     * キーボード操作の設定
     */
    function setupKeyboardControls() {
      document.addEventListener('keydown', function(event) {
        // テスト完了後やモーダル表示中は反応しないようにする
        if (currentIndex >= words.length || 
            document.getElementById('result-area').style.display === 'block' ||
            document.getElementById('post-submit-area').style.display === 'block') {
          return;
        }
        
        const flashCard = document.getElementById('flashCard');
        if (!flashCard) return;
        
        switch (event.key) {
          case 'ArrowLeft':
          case 'ArrowRight':
            // 左右矢印キーでカードを裏返す
            flashCard.classList.toggle('is-flipped');
            setTimeout(() => {
              adjustCardHeight();
            }, 300);
            break;
            
          case '1':
            // 1キーで「正解」
            markCorrect(currentIndex);
            break;
            
          case '2':
            // 2キーで「不正解」
            markWrong(currentIndex);
            break;
        }
      });
    }
    
    /**
     * カードの高さを内容に合わせて調整
     */
    function adjustCardHeight() {
      const flashCard = document.getElementById('flashCard');
      if (!flashCard) return;
      
      const cardFaces = flashCard.querySelectorAll('.card-face');
      if (cardFaces.length < 2) return;
      
      const frontHeight = cardFaces[0].scrollHeight;
      const backHeight = cardFaces[1].scrollHeight;
      
      // 画面サイズに応じて最大高さを調整
      const isMobile = window.innerWidth <= 768;
      const minHeight = isMobile ? 180 : 210;
      const maxHeight = isMobile ? 350 : 400;
      
      // 大きい方の高さに合わせる（最小/最大高さの制限あり）
      const adjustedHeight = Math.min(Math.max(frontHeight, backHeight, minHeight), maxHeight);
      
      flashCard.style.height = adjustedHeight + 'px';
      flashCard.style.minHeight = adjustedHeight + 'px';
      flashCard.style.maxHeight = maxHeight + 'px';
      
      cardFaces.forEach(face => {
        face.style.height = adjustedHeight + 'px';
        face.style.minHeight = adjustedHeight + 'px';
        face.style.maxHeight = maxHeight + 'px';
        face.style.position = 'absolute';
        face.style.overflow = 'visible';
      });
      
      // 裏面の内容が多い場合にカードの高さを再調整（少し遅延を入れる）
      setTimeout(() => {
        const newBackHeight = cardFaces[1].scrollHeight;
        if (newBackHeight > adjustedHeight && newBackHeight <= maxHeight) {
          flashCard.style.height = newBackHeight + 'px';
          flashCard.style.minHeight = newBackHeight + 'px';
          cardFaces.forEach(face => {
            face.style.height = newBackHeight + 'px';
            face.style.minHeight = newBackHeight + 'px';
          });
        }
      }, 100);
    }

    /**
     * 現在のカードを表示
     */
    function renderCard(index) {
      // すべてのカードが終了した場合
      if (index >= words.length) {
        showResult();
        return;
      }

      const item = words[index];
      const cardHtml = `
        <div class="card-container">
          <div class="card-flip" id="flashCard">
            <div class="card-face card-front">
              <div class="text-center">
                <h2 style="font-size: 1.8rem; margin-bottom: 15px;">${item.word}</h2>
                <p style="color: #666; font-size: 1rem;">[${item.language_code}]</p>
              </div>
            </div>
            <div class="card-face card-back">
              <div>
                ${item.translations && item.translations.length > 0 
                  ? item.translations.map(t => `
                      <div class="translation-item">
                        <p class="translation-text">${t.translation} <span style="color: #888; font-size: 0.9rem;">[${t.language_code}]</span></p>
                      </div>
                    `).join('')
                  : '<p class="translation-text">訳なし</p>'
                }
                ${item.language_code === 'ja' && item.reading ? 
                  `<p class="reading-text">フリガナ: ${item.reading}</p>` : ''}
                ${(item.note && item.note.trim()) || item.part_of_speech ? 
                  `<div class="note-section">
                     <div class="note-header">
                       <p class="note-title">補足:</p>
                       ${item.part_of_speech ? `<span class="part-of-speech">${item.part_of_speech}</span>` : ''}
                     </div>
                     ${item.note && item.note.trim() ? `<p class="note-content">${item.note}</p>` : ''}
                   </div>` 
                  : ''
                }
              </div>
            </div>
          </div>
        </div>
      `;
      document.getElementById('cards-area').innerHTML = cardHtml;
      
      // メッセージを非表示に
      const messageEl = document.getElementById('message');
      messageEl.style.display = 'none'; // 明示的に非表示
      
      // カードクリックで裏表を反転
      const flashCard = document.getElementById('flashCard');
      if (flashCard) {
        flashCard.addEventListener('click', function() {
          this.classList.toggle('is-flipped');
          
          // カードが裏返されるたびに高さを調整
          setTimeout(() => {
            adjustCardHeight();
          }, 300); // トランジションが完了した後に実行
        });
        
        // 最初に高さを調整 - 複数回呼び出して確実に調整
        setTimeout(() => {
          adjustCardHeight();
        }, 100);
        
        setTimeout(() => {
          adjustCardHeight();
        }, 300);
        
        setTimeout(() => {
          adjustCardHeight();
        }, 500);
      }

      // プログレスバーを更新
      updateProgress();
    }

    /**
     * プログレスバーと進捗テキストを更新
     */
    function updateProgress() {
      const totalQuestions = words.length;
      const answeredQuestions = userChecks.filter(x => x !== null).length;
      const progressPercent = (answeredQuestions / totalQuestions) * 100;
      
      document.getElementById('progress-fill').style.width = `${progressPercent}%`;
      document.getElementById('progress-text').innerText = `${answeredQuestions} / ${totalQuestions} 問`;
    }

    /**
     * 正解としてマーク
     */
    function markCorrect(index) {
      if (index >= words.length) return;
      
      userChecks[index] = true; // true = 正解
      const messageEl = document.getElementById('message');
      messageEl.innerHTML = '正解！';
      messageEl.className = 'alert alert-success show';
      messageEl.style.display = 'block'; // 表示を確実にする
      
      // ボタンのフィードバック効果
      const correctBtn = document.querySelector('.correct-button');
      correctBtn.classList.add('feedback-effect');
      
      // 固定ボタンを一時的に無効化（連打防止）
      disableAnswerButtons();
      
      // 次のカードに進む
      setTimeout(() => {
        messageEl.style.display = 'none'; // 明示的に非表示にする
        
        setTimeout(() => {
          currentIndex++;
          correctBtn.classList.remove('feedback-effect');
          renderCard(currentIndex);
        }, 100);
      }, 800);
    }

    /**
     * 不正解としてマーク
     */
    function markWrong(index) {
      if (index >= words.length) return;
      
      userChecks[index] = false; // false = 不正解
      const messageEl = document.getElementById('message');
      messageEl.innerHTML = '不正解';
      messageEl.className = 'alert alert-danger show';
      messageEl.style.display = 'block'; // 表示を確実にする
      
      // ボタンのフィードバック効果
      const wrongBtn = document.querySelector('.wrong-button');
      wrongBtn.classList.add('feedback-effect');
      
      // 固定ボタンを一時的に無効化（連打防止）
      disableAnswerButtons();
      
      // 次のカードに進む
      setTimeout(() => {
        messageEl.style.display = 'none'; // 明示的に非表示にする
        
        setTimeout(() => {
          currentIndex++;
          wrongBtn.classList.remove('feedback-effect');
          renderCard(currentIndex);
        }, 100);
      }, 800);
    }
    
    /**
     * 回答ボタンを一時的に無効化する（連打防止）
     */
    function disableAnswerButtons() {
      const buttons = document.querySelectorAll('.answer-button');
      
      buttons.forEach(button => {
        button.disabled = true;
        button.style.opacity = '0.6';
        button.style.cursor = 'not-allowed';
      });
      
      // 1秒後に再有効化（次のカードが表示される直前）
      setTimeout(() => {
        buttons.forEach(button => {
          button.disabled = false;
          button.style.opacity = '1';
          button.style.cursor = 'pointer';
        });
      }, 950);
    }

    /**
     * テスト結果を表示
     */
    function showResult() {
      // カードを非表示にし、結果を表示
      document.getElementById('cards-area').innerHTML = '';
      document.getElementById('result-area').style.display = 'block';
      
      // 固定要素を非表示
      document.getElementById('progress-container').style.display = 'none';
      document.getElementById('answerButtonsContainer').style.display = 'none';
      
      // 結果を集計
      const totalAnswered = userChecks.filter(x => x !== null).length;
      const totalCorrect = userChecks.filter(x => x === true).length;
      
      // 結果メッセージを更新
      document.getElementById('final-score').innerHTML = 
        `正解: ${totalCorrect} / ${totalAnswered} 問 (${Math.round(totalCorrect / totalAnswered * 100)}%)`;
    }

    /**
     * 結果をサーバに送信
     */
    document.getElementById('submitBtn').addEventListener('click', async function() {
      this.disabled = true;
      this.innerHTML = '保存中...';
      
      // 経過時間を計算 (ms)
      const elapsedTime = Date.now() - startTime;
      const elapsedSeconds = Math.floor(elapsedTime / 1000);
      
      // まとめたデータを作成
      const testResults = words.map((word, index) => ({
        word_id: word.word_id,
        is_correct: userChecks[index] === true ? 1 : 0
      }));
      
      try {
        // 結果を送信
        const res = await fetch('submit_test_results.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            test_results: testResults,
            elapsed_seconds: elapsedSeconds
          })
        });
        
        if (!res.ok) {
          throw new Error('サーバーエラー');
        }
        
        const result = await res.json();
        
        if (result.error) {
          throw new Error(result.error);
        }
        
        // 保存成功
        this.innerHTML = '保存完了！';
        document.getElementById('result-area').style.display = 'none';
        document.getElementById('post-submit-area').style.display = 'block';
        
        // 間違いがあれば再テストボタンを表示
        const hasWrongs = userChecks.includes(false);
        document.getElementById('retryBtn').style.display = hasWrongs ? 'inline-block' : 'none';
        
        // サマリボタンを表示
        document.getElementById('summaryBtn').style.display = 'inline-block';
        
      } catch (err) {
        console.error(err);
        this.innerHTML = '保存に失敗しました';
        this.disabled = false;
      }
    });

    /**
     * 間違えた単語を再テスト
     */
    function doRetryTest() {
      // サーバ側で再テストを行うように設定
      fetch('test_retry_branch_up.php')
        .then(res => {
          window.location.href = 'reveal_test.php'; 
        })
        .catch(err => {
          alert('エラーが発生しました: ' + err.message);
        });
    }

    /**
     * サマリ表示（結果確認ページへ）
     */
    function showSummary() {
      window.location.href = 'test_summary.php';
    }
  </script>
</body>
</html>
