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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1>テスト設定</h1>

        <!-- エラーメッセージ表示 -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- テスト設定フォーム -->
        <form method="POST" action="">
            <div class="form-group">
                <h2>テストモードを選択してください</h2>
                <div class="mt-2">
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="2"
                            <?php echo ($testType ?? '') === '2' ? 'checked' : ''; ?>>
                        <span>単語帳形式</span>
                    </label>
                </div>
                <div class="mt-2">
                    <label class="radio-label">
                        <input type="radio" name="test_type" value="4"
                            <?php echo ($testType ?? '') === '4' ? 'checked' : ''; ?>>
                        <span>Learn Mode (覚えるモード)</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="limit">出題数:</label>
                <input type="number" id="limit" name="limit"
                       value="<?php echo isset($_POST['limit']) ? (int)$_POST['limit'] : 5; ?>"
                       min="1" max="100">
            </div>

            <div class="form-group">
                <label for="language_code">対象言語:</label>
                <select id="language_code" name="language_code">
                    <option value="">すべて</option>
                    <option value="en" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'en') ? 'selected' : ''; ?>>英語</option>
                    <option value="ja" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'ja') ? 'selected' : ''; ?>>日本語</option>
                    <option value="vi" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'vi') ? 'selected' : ''; ?>>ベトナム語</option>
                </select>
            </div>

            <div class="form-group">
                <label for="answer_lang">答えの表示言語:</label>
                <select id="answer_lang" name="answer_lang">
                    <option value="">すべて表示</option>
                    <option value="ja" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'ja') ? 'selected' : ''; ?>>日本語</option>
                    <option value="en" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'en') ? 'selected' : ''; ?>>英語</option>
                    <option value="vi" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'vi') ? 'selected' : ''; ?>>ベトナム語</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary mt-3">テスト開始</button>
        </form>

        <div class="nav-links mt-3">
            <a href="../index.php">トップに戻る</a>
        </div>
    </div>
</div>
</body>
</html>
