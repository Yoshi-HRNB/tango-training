<?php

/**
 * test_config.php
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
    $testType   = '2'; // 単語帳形式に固定
    $limit      = (int)($_POST['limit']       ?? 5);
    $language   = trim($_POST['language_code'] ?? '');
    $answerLang = trim($_POST['answer_lang']   ?? '');
    $filterLowAccuracy = isset($_POST['filter_low_accuracy']);
    $filterUnseenDays  = (int)($_POST['filter_unseen_days'] ?? 0);

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
    <style>
        .config-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .section-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 8px;
        }
        .radio-card {
            display: flex;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        .radio-card label {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .radio-card label:hover {
            background-color: #f0f0f0;
        }
        .radio-card input[type="radio"] {
            margin-right: 10px;
        }
        .radio-card input[type="radio"]:checked + span {
            font-weight: bold;
            color: #4CAF50;
        }
        .radio-card:has(input[type="radio"]:checked) {
            border-color: #4CAF50;
            background-color: #f0fff0;
        }
        .form-row {
            display: flex;
            flex-direction: column;
            margin-bottom: 16px;
        }
        .form-label {
            margin-bottom: 8px;
            font-weight: 500;
        }
        .form-input, .form-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .form-input:focus, .form-select:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }
        .submit-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            margin-top: 10px;
        }
        .submit-button:hover {
            background-color: #3e8e41;
        }
        .home-link {
            display: inline-block;
            text-align: center;
            margin-top: 16px;
            color: #4CAF50;
            text-decoration: none;
            font-weight: 500;
        }
        .home-link:hover {
            text-decoration: underline;
        }
        .error-alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            border: 1px solid #f5c6cb;
        }
        .error-alert ul {
            margin: 0;
            padding-left: 20px;
        }
        
        /* レスポンシブデザイン用のメディアクエリ */
        @media (min-width: 768px) {
            .form-row {
                flex-direction: row;
                align-items: center;
            }
            .form-label {
                width: 140px;
                margin-bottom: 0;
                margin-right: 16px;
            }
            .form-input, .form-select {
                flex: 1;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="config-container">
        <h1 class="text-center">テスト設定</h1>

        <!-- エラーメッセージ表示 -->
        <?php if (!empty($errors)): ?>
            <div class="error-alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- テスト設定フォーム -->
        <form method="POST" action="">
            <!-- 単語帳形式（test_type=2）に固定するために隠しフィールドを追加 -->
            <input type="hidden" name="test_type" value="2">

            <div class="section-card">
                <div class="section-title">出題設定</div>
                <div class="form-row">
                    <label class="form-label" for="limit">出題数:</label>
                    <input type="number" id="limit" name="limit" class="form-input"
                           value="<?php echo isset($_POST['limit']) ? (int)$_POST['limit'] : 5; ?>"
                           min="1" max="100">
                </div>

                <div class="form-row">
                    <label class="form-label" for="language_code">対象言語:</label>
                    <select id="language_code" name="language_code" class="form-select">
                        <option value="">すべての言語</option>
                        <option value="en" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'en') ? 'selected' : ''; ?>>英語</option>
                        <option value="ja" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'ja') ? 'selected' : ''; ?>>日本語</option>
                        <option value="vi" <?php echo (isset($_POST['language_code']) && $_POST['language_code'] === 'vi') ? 'selected' : ''; ?>>ベトナム語</option>
                    </select>
                </div>

                <div class="form-row">
                    <label class="form-label" for="answer_lang">答えの言語:</label>
                    <select id="answer_lang" name="answer_lang" class="form-select">
                        <option value="">すべて表示</option>
                        <option value="ja" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'ja') ? 'selected' : ''; ?>>日本語のみ</option>
                        <option value="en" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'en') ? 'selected' : ''; ?>>英語のみ</option>
                        <option value="vi" <?php echo (isset($_POST['answer_lang']) && $_POST['answer_lang'] === 'vi') ? 'selected' : ''; ?>>ベトナム語のみ</option>
                    </select>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title">フィルター設定（オプション）</div>
                <div class="form-row">
                    <label class="form-label">正解率:</label>
                    <div>
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="filter_low_accuracy" 
                                <?php echo (isset($_POST['filter_low_accuracy']) && $_POST['filter_low_accuracy']) ? 'checked' : ''; ?>>
                            <span style="margin-left: 8px;">正解率の低い単語だけ出題する</span>
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-label" for="filter_unseen_days">期間:</label>
                    <div style="display: flex; align-items: center;">
                        <input type="number" id="filter_unseen_days" name="filter_unseen_days" class="form-input"
                               style="width: 80px; margin-right: 8px;"
                               value="<?php echo isset($_POST['filter_unseen_days']) ? (int)$_POST['filter_unseen_days'] : 0; ?>"
                               min="0" max="365">
                        <span>日以上テストしていない単語のみ</span>
                    </div>
                </div>
            </div>

            <button type="submit" class="submit-button">テスト開始</button>
        </form>

        <div class="text-center">
            <a href="../index.php" class="home-link">トップページに戻る</a>
        </div>
    </div>
</div>
</body>
</html>
