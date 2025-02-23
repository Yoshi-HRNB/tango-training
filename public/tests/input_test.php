<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>入力形式テスト</title>
  <link rel="stylesheet" href="../css/style.css">
  <script>
    let questions = [];
    let currentIndex = 0;
    let score = 0;

    window.onload = async () => {
      const res = await fetch('fetch_test_words.php');
      if (!res.ok) {
        document.getElementById('question').innerText = '単語取得に失敗しました。';
        return;
      }
      questions = await res.json();
      showQuestion();
    };

    function showQuestion() {
      if (currentIndex >= questions.length) {
        document.getElementById('quizArea').style.display = 'none';
        document.getElementById('result').innerText =
          `テスト終了！スコア: ${score} / ${questions.length}`;
        return;
      }
      const q = questions[currentIndex];
      document.getElementById('question').innerText =
        `問題 ${currentIndex + 1}: ${q.word} [${q.language_code}]`;
      document.getElementById('answer').value = '';
    }

    function checkAnswer() {
      const userAnswer = document.getElementById('answer').value.trim();

      // sessionにある答えの表示言語
      const sessionAnswerLang = "<?php echo $_SESSION['answer_lang'] ?? ''; ?>";
      // 全翻訳を一つの配列にまとめて存在チェック
      const allTrans = [];
      if (questions[currentIndex].translations) {
        questions[currentIndex].translations.forEach(t => {
          // 「特定言語だけが正解」とするなら if(t.language_code===sessionAnswerLang) ...
          allTrans.push(t.translation.toLowerCase());
        });
      }
      if (allTrans.includes(userAnswer.toLowerCase())) {
        score++;
        alert("正解！");
      } else {
        alert(`不正解… 正解候補: ${allTrans.join(' / ')}`);
      }

      currentIndex++;
      showQuestion();
    }
  </script>
</head>
<body>
<div class="container">
  <h1>入力形式テスト</h1>
  <div id="quizArea">
    <p id="question">読み込み中...</p>
    <input type="text" id="answer" placeholder="訳を入力">
    <button onclick="checkAnswer()">送信</button>
  </div>
  <p id="result"></p>
  <p><a href="../index.php">トップへ戻る</a></p>
</div>
</body>
</html>
