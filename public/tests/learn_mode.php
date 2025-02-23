<?php
session_start();
// 未ログインならリダイレクト
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../../src/Database.php';
$db = new \TangoTraining\Database();
$pdo = $db->getConnection();

// -------------------------------
// 2回目(再学習)かどうかを判定
// -------------------------------
$isRetry = (isset($_GET['retry']) && $_GET['retry'] == 1);

// 学習結果をセッションに保存する配列（なければ初期化）
if (!isset($_SESSION['learn_results'])) {
    $_SESSION['learn_results'] = [];
}


// もし再学習モードなら、1回目で「覚えてない」とマークされた単語のみ抽出
if ($isRetry) {
    // セッションから「覚えてない」だけ取り出してIDリスト化
    $unknownIds = [];
    foreach ($_SESSION['learn_results'] as $wordId => $result) {
        if ($result === 'unknown') {
            $unknownIds[] = (int)$wordId;
        }
    }

    if (!empty($unknownIds)) {
        // DBから該当の単語を抽出
        $placeholders = implode(',', array_fill(0, count($unknownIds), '?'));
        $sql = "SELECT w.word_id AS id,
                       w.language_code,
                       w.word,
                       w.note,
                       IFNULL(t.translation, '') AS translation
                  FROM words w
             LEFT JOIN translations t ON w.word_id = t.word_id
                 WHERE w.word_id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        foreach ($unknownIds as $i => $val) {
            $stmt->bindValue($i + 1, $val, PDO::PARAM_INT);
        }
        $stmt->execute();
        $words = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // unknownがない場合
        $words = [];
    }
} else {
    // -------------------------------
    // 1回目：通常のランダム取得
    // -------------------------------
    $limit    = (int)($_SESSION['test_limit'] ?? 5);
    $language = $_SESSION['test_language'] ?? '';

    $sql = "SELECT w.word_id AS id,
                   w.language_code,
                   w.word,
                   w.note,
                   IFNULL(t.translation, '') AS translation
              FROM words w
         LEFT JOIN translations t ON w.word_id = t.word_id
             WHERE 1=1";
    $params = [];

    // 言語フィルタ
    if ($language !== '') {
        $sql .= " AND w.language_code = :lang";
        $params[':lang'] = $language;
    }

    // ランダムソート＆件数制限
    $sql .= " ORDER BY RAND() LIMIT :limit";
    $stmt = $pdo->prepare($sql);

    // バインド
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    $stmt->execute();
    $words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 1回目開始時に、前回の学習結果をリセットする場合はここでクリア
    // $_SESSION['learn_results'] = [];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>覚えるモード</title>
  <link rel="stylesheet" href="../css/style.css">
  <style>
    .card-container {
      width: 300px;
      margin: 40px auto;
      perspective: 1000px;
    }
    .card {
      width: 100%;
      height: 200px;
      text-align: center;
      position: relative;
      transition: transform 0.6s;
      transform-style: preserve-3d;
      cursor: pointer;
      border: 2px solid #ccc;
      border-radius: 10px;
    }
    .card.is-flipped {
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
      font-size: 1.2rem;
      padding: 10px;
    }
    .card-front {
      background: #fafafa;
    }
    .card-back {
      background: #fce4ec;
      transform: rotateY(180deg);
    }
    .button-group {
      text-align: center;
      margin-top: 10px;
    }
    .button-group button {
      margin: 0 5px;
      padding: 5px 10px;
      cursor: pointer;
    }
    #message {
      text-align: center;
      margin-top: 20px;
      font-size: 1.1rem;
      color: green;
    }
  </style>
</head>
<body>

  <?php if ($isRetry): ?>
    <h1 style="text-align:center;">覚えるモード (再学習: 覚えていない単語のみ)</h1>
  <?php else: ?>
    <h1 style="text-align:center;">覚えるモード (初回)</h1>
  <?php endif; ?>

  <?php if ($isRetry && empty($words)): ?>
    <p style="text-align:center;">再学習する単語がありません。全ての単語を覚えています！</p>
    <p style="text-align:center;"><a href="../index.php">トップへ戻る</a></p>
    <?php exit; ?>
  <?php endif; ?>

  <p style="text-align:center;">
    カードをクリックすると裏面(訳)を表示できます。<br>
    ボタンで「覚えた」または「覚えてない」を記録してください。
  </p>

  <div id="cards-area" style="text-align:center;"></div>
  <div id="message"></div>

  <script>
    /**
     * wordsData: PHPから取得した単語一覧をJSON形式で埋め込む。
     * 各単語オブジェクトに isUnknown プロパティを追加して初期化。
     */
    const wordsData = <?php echo json_encode($words, JSON_UNESCAPED_UNICODE); ?>.map(word => ({
        ...word,
        isUnknown: false // 初期状態は false
    }));

    // 再学習対象の単語リスト
    let currentWords = [...wordsData];
    let currentIndex = 0;

    /**
     * カードを表示する関数
     */
    function renderCard(index) {
      if (index >= currentWords.length) {
        // すべてのカードを学習し終えたら
        endRound();
        return;
      }

      const item = currentWords[index];
      const cardHtml = `
        <div class="card-container">
          <div class="card" id="flashCard">
            <div class="card-face card-front">
              <div>
                <strong>${item.word}</strong><br>
                [${item.language_code}]<br>
                <small style="color:gray;">${item.note || ''}</small>
              </div>
            </div>
            <div class="card-face card-back">
              <strong>${item.translation}</strong>
            </div>
          </div>
        </div>
        <div class="button-group">
          <button onclick="markKnown(${index})">覚えた</button>
          <button onclick="markUnknown(${index})">覚えてない</button>
        </div>
      `;
      document.getElementById('cards-area').innerHTML = cardHtml;
      document.getElementById('message').textContent = ''; // メッセージ消去

      // カードクリックで裏表を反転
      const cardEl = document.getElementById('flashCard');
      cardEl.addEventListener('click', () => {
        cardEl.classList.toggle('is-flipped');
      });
    }

    /**
     * 「覚えた(known)」を押したときの処理
     */
    async function markKnown(idx) {
      // 単語を覚えたので isUnknown を false に設定
      currentWords[idx].isUnknown = false;
      // セッションに記録 (サーバーへPOST)
      await recordResult(currentWords[idx].id, 'known');
      // 次のカードへ
      currentIndex++;
      renderCard(currentIndex);
    }

    /**
     * 「覚えてない(unknown)」を押したときの処理
     */
    async function markUnknown(idx) {
      // 単語を覚えていないので isUnknown を true に設定
      currentWords[idx].isUnknown = true;
      // セッションに記録 (サーバーへPOST)
      await recordResult(currentWords[idx].id, 'unknown');
      // 次のカードへ
      currentIndex++;
      renderCard(currentIndex);
    }

    /**
     * 学習結果をサーバーに送信してセッションに保存する関数
     */
    async function recordResult(wordId, resultType) {
      try {
        const formData = new FormData();
        formData.append('word_id', wordId);
        formData.append('result', resultType);

        const response = await fetch('record_learn_result.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        if (!response.ok || data.error) {
          alert('エラー: ' + (data.error || '不明なエラー'));
        }
      } catch (error) {
        console.error('通信エラー:', error);
        alert('通信エラーが発生しました。');
      }
    }

    /**
     * 全てのカードを学習し終えた後の処理
     */
    async function endRound() {
      // 再学習対象の単語が存在するか確認
      // 既にセッションに記録されているため、再学習を開始する
      // リダイレクト前に全てのAJAXが完了していることを保証する
      // 今の実装ではAJAXが全て完了しているため、リロードして再学習
      window.location.href = '?retry=1';
    }

    /**
     * 初期表示
     */
    function init() {
      renderCard(currentIndex);
    }

    // 初期化
    init();
  </script>

  <?php if (!$isRetry): ?>
    <!-- 初回の学習後に覚えてない単語だけ再学習したい場合 -->
    <p style="text-align:center; margin-top:20px;">
      1周目の学習が終わったら、
      <strong>「覚えてない」単語</strong>だけを再学習できます。<br>
      → <a href="?retry=1">覚えてない単語だけ再学習する</a>
    </p>
  <?php endif; ?>

</body>
</html>
