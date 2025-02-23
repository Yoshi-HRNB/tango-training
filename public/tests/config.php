<?php

/**
 * config.php
 */

session_start();

// ログイン必須
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// 初期化
ini_set('display_errors', 1);
error_reporting(E_ALL);

$errors = [];

// フォーム送信時の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームから取得
    $testType   = $_POST['test_type']         ?? '';
    $limit      = (int)($_POST['limit']       ?? 5);
    $language   = trim($_POST['language_code'] ?? '');
    $answerLang = trim($_POST['answer_lang']   ?? '');
    $filterLowAccuracy = isset($_POST['filter_low_accuracy']);
    $filterUnseenDays  = (int)($_POST['filter_unseen_days'] ?? 0);

    if ($testType === '') {
        $errors[] = 'テストモードを選択してください。';
    }
    if ($limit <= 0) {
        $errors[] = '出題数は1以上の整数を入力してください。';
    }

    if (empty($errors)) {
        // セッションに各値を保存
        $_SESSION['test_type']     = $testType;
        $_SESSION['test_limit']    = $limit;
        $_SESSION['test_language'] = $language;
        $_SESSION['answer_lang']   = $answerLang;

        // テストフィルタの例
        $_SESSION['test_filter'] = [
            'low_accuracy' => $filterLowAccuracy,
            'unseen_days'  => $filterUnseenDays > 0 ? $filterUnseenDays : null,
        ];

        // フォーム送信直後に start_test.php へ飛ばし、DBのtestsにINSERTする
        header('Location: start_test.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>テスト設定</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>テスト設定</h1>

    <!-- エラーメッセージ表示 -->
    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- テスト設定フォーム -->
    <form method="POST" action="">
        <h2>テストモードを選択してください</h2>
        <div>
            <label>
                <input type="radio" name="test_type" value="2"
                    <?php echo ($testType ?? '') === '2' ? 'checked' : ''; ?>>
                単語帳形式
            </label>
        </div>
        <div>
            <label>
                <input type="radio" name="test_type" value="4"
                    <?php echo ($testType ?? '') === '4' ? 'checked' : ''; ?>>
                Learn Mode (覚えるモード)
            </label>
        </div>
        <!-- <div>
        </div>
            <label>
                <input type="radio" name="test_type" value="5"
                <?php echo ($testType ?? '') === '5' ? 'checked' : ''; ?>>
                練習モード(集計に反映しない)
            </label>
        <div>
            <label>
                <input type="radio" name="test_type" value="1"
                    <?php echo ($testType ?? '') === '1' ? 'checked' : ''; ?>>
                入力形式
            </label>
        </div>
        <div>
            <label>
                <input type="radio" name="test_type" value="3"
                    <?php echo ($testType ?? '') === '3' ? 'checked' : ''; ?>>
                4択形式
            </label>
        </div> -->

        <br>
        <!-- 出題数 -->
        <div>
            <label for="limit">出題数:</label><br>
            <input type="number" id="limit" name="limit"
                   value="<?php echo isset($_POST['limit']) ? (int)$_POST['limit'] : 5; ?>"
                   min="1" max="100">
        </div>

        <br>
        <!-- 対象言語 (問題の出題言語) -->
        <div>
            <label for="language_code">対象言語:</label><br>
            <select id="language_code" name="language_code">
                <option value="">すべて</option>
                <option value="en" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'en') ? 'selected' : ''; ?>>英語</option>
                <option value="ja" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'ja') ? 'selected' : ''; ?>>日本語</option>
                <option value="vi" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'vi') ? 'selected' : ''; ?>>ベトナム語</option>
                <!-- 必要に応じて追加 -->
            </select>
        </div>

        <br>
        <!-- 答えの表示言語(日本語 / 英語 / ベトナム語 など) -->
        <div>
            <label for="answer_lang">答えの表示言語:</label><br>
            <select id="answer_lang" name="answer_lang">
                <option value="">すべて表示</option>
                <option value="ja" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'ja') ? 'selected' : ''; ?>>日本語</option>
                <option value="en" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'en') ? 'selected' : ''; ?>>英語</option>
                <option value="vi" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'vi') ? 'selected' : ''; ?>>ベトナム語</option>
                <!-- 必要に応じて追加 -->
            </select>
        </div>

        <br>
        <!-- テスト絞り込みフィルタ -->
        <!-- <div>
            <label>
                <input type="checkbox" name="filter_low_accuracy" value="1"
                  <?php echo !empty($_POST['filter_low_accuracy']) ? 'checked' : ''; ?>>
                正答率が低い単語のみ (80%未満 など)
            </label>
        </div>
        <div>
            <label for="filter_unseen_days">最後のテストから何日以上経った単語のみ:</label><br>
            <input type="number" id="filter_unseen_days" name="filter_unseen_days"
                   min="0" max="999" value="<?php echo isset($_POST['filter_unseen_days'])? (int)$_POST['filter_unseen_days']: 0; ?>">
        </div> -->

        <br>
        <button type="submit">テスト開始</button>
    </form>

    <p><a href="../index.php">トップに戻る</a></p>
</div>
</body>
</html>
