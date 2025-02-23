<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>単語帳形式テスト - 再テスト対応</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/style.css">

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

        // (2) 「答えを見る」ボタン
        const revealBtn = document.createElement('button');
        revealBtn.innerText = '答えを見る';
        // 押すまで答えを隠すための要素
        const ansDiv = document.createElement('div');
        ansDiv.style.display = 'none';

        revealBtn.onclick = () => {
          ansDiv.style.display = 'block';
        };
        div.appendChild(revealBtn);

        // (3) 訳一覧を表示
        if (item.translations && item.translations.length > 0) {
          // 例: "訳: こんにちは [ja], hello [en]"
          ansDiv.innerText = '訳: ' + item.translations
            .map(t => `${t.translation} [${t.language_code}]`)
            .join(', ');
        } else {
          ansDiv.innerText = '訳: なし';
        }
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
