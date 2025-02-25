<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>単語帳形式テスト - 再テスト対応</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    body {
      font-family: "Roboto", sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f0f2f5;
      color: #333;
    }

    .container {
      width: 90%;
      max-width: 800px;
      margin: 40px auto;
      padding: 30px;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    header,
    footer {
      text-align: center;
      margin-bottom: 1.5em;
    }

    nav a {
      margin: 0 1em;
      text-decoration: none;
      color: #007bff;
    }

    nav a:hover {
      text-decoration: underline;
    }

    h1 {
      font-size: 28px;
      color: #333;
      text-align: center;
      margin-bottom: 20px;
    }

    h2 {
      text-align: center;
      margin-top: 0.5em;
    }

    button {
      display: inline-block;
      padding: 12px 24px;
      margin: 10px;
      font-size: 16px;
      color: #fff;
      background-color: #007bff;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    button:disabled {
      background-color: #ccc;
    }

    button:hover:not(:disabled) {
      background-color: #0056b3;
    }

    #wordContainer .word-item {
      margin-bottom: 20px;
      padding: 15px;
      background-color: #f9f9f9;
      border: 1px solid #ddd;
      border-radius: 5px;
    }

    .answer-placeholder {
      color: #888;
      cursor: pointer;
      padding: 12px;
      background-color: #e9e9e9;
      border-radius: 5px;
      text-align: center;
      transition: background-color 0.3s ease;
    }

    .answer-placeholder:hover {
      background-color: #d9d9d9;
    }

    @media (max-width: 600px) {
      .container {
        padding: 20px;
      }

      button {
        width: 100%;
        margin: 10px 0;
      }
    }
  </style>
  <script>
    let words = [];       // サーバーから取得した問題一覧
    let userChecks = [];  // 各問題に対するユーザの解答(正解=true/不正解=false/未回答=null)
    let currentPage = 0;  // ページネーション用の現在ページ
    const pageSize = 5;   // 1ページに表示する問題数

    // テスト開始時刻
    const startTime = Date.now();

    window.onload = async () => {
      await loadQuestions();
    };


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
          document.getElementById('wordContainer').innerText = errorMsg;
          return;
        }
        // JSONをパース
        words = await res.json();

        // userChecks 配列を問題数に合わせて初期化 (null=未回答)
        userChecks = new Array(words.length).fill(null);

        // もし問題が0件なら終了
        if (words.length === 0) {
          document.getElementById('wordContainer').innerText = '問題がありません。';
          document.getElementById('scoreDisplay').innerText = '';
          // 「summaryBtn」などを表示するか判断
          return;
        }

        // 最初のページを表示
        renderPage();
      } catch (err) {
        console.error(err);
        document.getElementById('wordContainer').innerText = '問題取得エラー: ' + err.message;
      }
    }

    /**
     * 現在ページの問題を表示
     */
    function renderPage() {
      const container = document.getElementById('wordContainer');
      container.innerHTML = '';

      const startIndex = currentPage * pageSize;
      const endIndex   = Math.min(startIndex + pageSize, words.length);

      if (startIndex >= words.length) {
        container.innerText = '問題がありません。';
        document.getElementById('scoreDisplay').innerText = '';
        return;
      }

      for (let i = startIndex; i < endIndex; i++) {
        const item = words[i];
        const div = document.createElement('div');
        div.className = 'word-item';

        // (1) 単語表示
        const q = document.createElement('div');
        q.innerText = `${i+1}. ${item.word} [${item.language_code}]`;
        div.appendChild(q);

        // (2)'正解欄'を作成（初めはプレースホルダーを表示）
        const ansDiv = document.createElement('div');
        // プレースホルダー用のクラスを追加（CSSで見た目調整も可能）
        ansDiv.classList.add('answer-placeholder');
        ansDiv.innerText = '正解欄（タップして表示）';

        // タップ時に答えを表示／隠す処理
        ansDiv.onclick = () => {
          if (ansDiv.classList.contains('answer-placeholder')) {
            // 表示状態に切替：実際の訳を表示
            if (item.translations && item.translations.length > 0) {
              ansDiv.innerText = '訳: ' + item.translations
                .map(t => `${t.translation} [${t.language_code}]`)
                .join(', ');
            } else {
              ansDiv.innerText = '訳: なし';
            }
            ansDiv.classList.remove('answer-placeholder');
          } else {
            // 再度隠す（プレースホルダーに戻す）
            ansDiv.innerText = '正解欄（タップして表示）';
            ansDiv.classList.add('answer-placeholder');
          }
        };
        div.appendChild(ansDiv);

        // (4) 正解/不正解ボタン
        const correctBtn = document.createElement('button');
        correctBtn.innerText = '正解';
        correctBtn.onclick = () => {
          userChecks[i] = true;
          updateScore();
        };

        const wrongBtn = document.createElement('button');
        wrongBtn.innerText = '不正解';
        wrongBtn.onclick = () => {
          userChecks[i] = false;
          updateScore();
        };

        div.appendChild(correctBtn);
        div.appendChild(wrongBtn);

        // 要素をコンテナに追加
        container.appendChild(div);
      }

      // スコア表示を更新 & ページ切替ボタンの状態更新
      updateScore();
      updatePaginationButtons();
    }

    /**
     * スコア(正解数など)を更新
     */
    function updateScore() {
      const totalAnswered = userChecks.filter(x => x !== null).length;
      const totalCorrect = userChecks.filter(x => x === true).length;
      document.getElementById('scoreDisplay').innerText =
        `正解数: ${totalCorrect} / ${totalAnswered} (全${words.length}問)`;
    }

    /**
     * ページングボタンのON/OFF
     */
    function updatePaginationButtons() {
      document.getElementById('prevBtn').disabled = (currentPage === 0);
      document.getElementById('nextBtn').disabled = ((currentPage + 1) * pageSize >= words.length);
    }

    // 前ページ
    function prevPage() {
      if (currentPage > 0) {
        currentPage--;
        renderPage();
      }
    }
    // 次ページ
    function nextPage() {
      if ((currentPage + 1) * pageSize < words.length) {
        currentPage++;
        renderPage();
      }
    }

    /**
     * テスト完了→結果送信
     */
    async function submitTest() {
      const endTime = Date.now();
      const timeSpent = Math.floor((endTime - startTime) / 1000);

      try {
        // デバッグ用: 送信前のデータをコンソールに表示
        console.log('Sending data:', JSON.stringify({
          words,
          userChecks,
          timeSpent
        }));

        const res = await fetch('submit_test_results.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            words,
            userChecks,
            timeSpent
          })
        });

        // デバッグ用: レスポンスのステータスコードを確認
        console.log('Response status:', res.status);

        if (!res.ok) {
          // サーバー側でエラーが発生した場合
          let errorMsg = '送信エラー';
          try {
            const errorData = await res.json();
            errorMsg += `: ${errorData.error}`;
          } catch(e) {
            // JSONデコード失敗時
          }
          alert(errorMsg);
          return;
        }

        const data = await res.json();
        console.log('Received data:', data); // デバッグ用

        if (data.success) {
          // 成功メッセージ
          alert('テスト結果を保存しました。');

          // 間違いがあるかどうか
          if (data.has_wrong_words) {
            // 間違い再テストボタン表示
            document.getElementById('retryBtn').style.display = 'inline-block';
          } else {
            // サマリを見るボタン
            document.getElementById('summaryBtn').style.display = 'inline-block';
          }
        } else {
          // サーバーが success=false を返した場合
          alert(`保存失敗: ${data.error || '原因不明'}`);
        }
      } catch (err) {
        console.error(err);
        alert('通信エラー: ' + err.message);
      }
    }

    /**
     * 間違い再テスト実行
     * test_retry_branch_up.php を呼んで branch_id を+1 → この画面リロード
     */
    async function doRetryTest() {
      try {
        const res = await fetch('test_retry_branch_up.php');
        const data = await res.json();
        if (!res.ok || !data.success) {
          alert(`branch更新失敗: ${data.error || res.status}`);
          return;
        }
        alert('再テストを開始します');
        // 現在画面をリロードすると fetch_test_words.php が retry_incorrectモードに切り替わる等
        location.reload();
      } catch (err) {
        console.error(err);
        alert('通信エラー: ' + err.message);
      }
    }

    // テストサマリ画面に移動
    function showSummary() {
      window.location.href = 'test_summary.php';
    }
  </script>
</head>
<body>
<div class="container">
  <h1>単語帳形式テスト (再テスト対応)</h1>
  <p id="scoreDisplay">読み込み中...</p>

  <div id="wordContainer">読み込み中...</div>

  <div>
    <button id="prevBtn" onclick="prevPage()">前へ</button>
    <button id="nextBtn" onclick="nextPage()">次へ</button>
  </div>

  <div style="margin-top:10px;">
    <button onclick="submitTest()">テストを終了して結果を保存</button>
<!-- テスト結果保存後に表示するボタン -->
    <button id="retryBtn" style="display:none;" onclick="doRetryTest()">間違い再テスト</button>
    <button id="summaryBtn" style="display:none;" onclick="showSummary()">最終結果を見る</button>
  </div>

  <p><a href="../index.php">トップへ戻る</a></p>
</div>
</body>
</html>
