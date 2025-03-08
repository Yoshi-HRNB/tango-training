<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>フラッシュカード式テスト</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .card-container {
      perspective: 1000px;
      margin: 20px auto;
      max-width: 500px;
    }
    .card-flip {
      position: relative;
      width: 100%;
      height: 200px;
      transition: transform 0.6s;
      transform-style: preserve-3d;
      cursor: pointer;
    }
    .card-flip.is-flipped {
      transform: rotateY(180deg);
    }
    .card-face {
      position: absolute;
      width: 100%;
      height: 100%;
      backface-visibility: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      padding: 20px;
    }
    .card-front {
      background-color: #f8f9fa;
    }
    .card-back {
      background-color: #e9ecef;
      transform: rotateY(180deg);
    }
    .progress-bar {
      background-color: #e9ecef;
      height: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    .progress-fill {
      background-color: #4CAF50;
      height: 100%;
      border-radius: 5px;
      transition: width 0.3s;
    }
    .button-container {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-top: 15px;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>フラッシュカード式テスト</h1>

    <div class="card">
      <div id="progress-container">
        <div class="progress-bar">
          <div id="progress-fill" class="progress-fill" style="width: 0%"></div>
        </div>
        <p id="progress-text" class="text-center mb-3">0 / 0 問</p>
      </div>
      
      <p class="text-center mb-3">
        カードをクリックすると裏面(訳)を表示できます。<br>
        「正解」または「不正解」ボタンを押して次へ進んでください。
      </p>

      <div id="cards-area"></div>
      <div id="message" class="alert mt-2" style="display: none;"></div>
      
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
      
      <div id="post-submit-area" style="display: none;">
        <div class="flex justify-center gap-2 mt-3">
          <button id="retryBtn" style="display: none;" class="btn btn-warning" onclick="doRetryTest()">間違えた単語を再テスト</button>
          <button id="summaryBtn" style="display: none;" class="btn btn-info" onclick="showSummary()">サマリを見る</button>
          <a href="../index.php" class="btn btn-outline">トップへ戻る</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    let words = [];       // サーバーから取得した問題一覧
    let userChecks = [];  // 各問題に対するユーザの解答(正解=true/不正解=false/未回答=null)
    let currentIndex = 0; // 現在表示中のカードインデックス

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
          return;
        }

        // プログレスバーの初期化
        updateProgress();

        // 最初のカードを表示
        renderCard(currentIndex);
      } catch (err) {
        console.error(err);
        document.getElementById('cards-area').innerText = '問題取得エラー: ' + err.message;
      }
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
                <h2>${item.word}</h2>
                <p class="mb-1">[${item.language_code}]</p>
              </div>
            </div>
            <div class="card-face card-back">
              <div>
                ${item.translations && item.translations.length > 0 
                  ? item.translations.map(t => `<p>${t.translation} [${t.language_code}]</p>`).join('')
                  : '<p>訳なし</p>'
                }
              </div>
            </div>
          </div>
        </div>
        <div class="button-container">
          <button onclick="markCorrect(${index})" class="btn btn-success">正解</button>
          <button onclick="markWrong(${index})" class="btn btn-danger">不正解</button>
        </div>
      `;
      document.getElementById('cards-area').innerHTML = cardHtml;
      document.getElementById('message').style.display = 'none'; // メッセージ消去

      // カードクリックで裏表を反転
      const cardEl = document.getElementById('flashCard');
      cardEl.addEventListener('click', () => {
        cardEl.classList.toggle('is-flipped');
      });

      // プログレスバーを更新
      updateProgress();
    }

    /**
     * 「正解」を押したときの処理
     */
    function markCorrect(idx) {
      userChecks[idx] = true;
      currentIndex++;
      // 次のカードを表示する前にプログレスバーを更新
      updateProgress(true);
      renderCard(currentIndex);
    }

    /**
     * 「不正解」を押したときの処理
     */
    function markWrong(idx) {
      userChecks[idx] = false;
      currentIndex++;
      // 次のカードを表示する前にプログレスバーを更新
      updateProgress(true);
      renderCard(currentIndex);
    }

    /**
     * プログレスバーを更新
     * @param {boolean} includeCurrentCard 現在のカードを含めるかどうか
     */
    function updateProgress(includeCurrentCard = false) {
      const total = words.length;
      // includeCurrentCardがtrueの場合、currentIndexをそのまま使用
      // falseの場合は、まだ答えていないので-1する
      const current = includeCurrentCard ? currentIndex : (currentIndex > 0 ? currentIndex : 0);
      const percentage = total > 0 ? (current / total) * 100 : 0;
      
      document.getElementById('progress-fill').style.width = `${percentage}%`;
      document.getElementById('progress-text').textContent = `${current} / ${total} 問`;
    }

    /**
     * テスト結果を表示
     */
    function showResult() {
      document.getElementById('cards-area').innerHTML = '';
      document.getElementById('result-area').style.display = 'block';
      
      const totalAnswered = userChecks.filter(x => x !== null).length;
      const totalCorrect = userChecks.filter(x => x === true).length;
      document.getElementById('final-score').innerText = 
        `正解: ${totalCorrect} / ${totalAnswered} 問 (${Math.round(totalCorrect/totalAnswered*100)}%)`;
      
      // 送信ボタンのイベントリスナー
      document.getElementById('submitBtn').addEventListener('click', submitTest);
    }

    /**
     * テスト完了→結果送信
     */
    async function submitTest() {
      const endTime = Date.now();
      const timeSpent = Math.floor((endTime - startTime) / 1000);

      try {
        const res = await fetch('submit_test_results.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            words,
            userChecks,
            timeSpent
          })
        });

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

        if (data.success) {
          // 結果エリアを隠し、送信後エリアを表示
          document.getElementById('result-area').style.display = 'none';
          document.getElementById('post-submit-area').style.display = 'block';
          
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
</body>
</html>
