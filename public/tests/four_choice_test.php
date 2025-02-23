<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>4択形式テスト</title>
  <link rel="stylesheet" href="../css/style.css">
  <script>
    let questions = [];
    let currentIndex = 0;
    let score = 0;

    window.onload = async () => {
      // fetchでデータ取得
      const res = await fetch('fetch_test_words.php');
      if (!res.ok) {
        document.getElementById('question').innerText = '単語取得に失敗しました。';
        return;
      }
      const data = await res.json();
      // dataの中に { id, language_code, word, correct_translation } がある想定

      // 全部をダミー選択肢に使うためにコピー
      const allTranslations = data.map(d => d.correct_translation);
      
      // 出題リストを作成
      // 今回は data をそのまま利用
      questions = data.map(item => {
        // 正解
        const correct = item.correct_translation || '(訳不明)';

        // ダミー3つ取得
        let dummyList = allTranslations.filter(t => t !== correct);
        shuffle(dummyList);
        const wrongs = dummyList.slice(0, 3);

        // 選択肢
        const choices = [correct, ...wrongs];
        shuffle(choices);

        return {
          word: item.word,
          language: item.language_code,
          correct: correct,
          choices: choices
        }
      });

      showQuestion();
    };

    function shuffle(arr) {
      for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
      }
    }

    function showQuestion() {
      if (currentIndex >= questions.length) {
        // テスト終了
        document.getElementById('quiz').style.display = 'none';
        document.getElementById('result').innerText =
          `テスト終了！スコア: ${score} / ${questions.length}`;
        return;
      }
      const q = questions[currentIndex];
      document.getElementById('question').innerText =
        `問題 ${currentIndex+1}: ${q.word} [${q.language}]`;
      const choicesDiv = document.getElementById('choices');
      choicesDiv.innerHTML = '';
      q.choices.forEach(choice => {
        const btn = document.createElement('button');
        btn.innerText = choice;
        btn.onclick = () => checkAnswer(choice);
        choicesDiv.appendChild(btn);
      });
    }

    function checkAnswer(answer) {
      if (answer === questions[currentIndex].correct) {
        score++;
      }
      currentIndex++;
      showQuestion();
    }
  </script>
</head>
<body>
<div class="container">
  <h1>4択形式テスト</h1>
  <div id="quiz">
    <p id="question">読み込み中...</p>
    <div id="choices"></div>
  </div>
  <p id="result"></p>
  <p><a href="../index.php">トップへ</a></p>
</div>
</body>
</html>

