<?php
/**
 * create.php
 * add_from_picture.php のフォームから受け取り、DBにINSERTする。
 */
// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // フォームからの入力を取得
    $language = trim($_POST['language_code'] ?? '');
    $word = trim($_POST['word'] ?? '');
    $note = trim($_POST['supplement'] ?? '');

    // 翻訳データを取得
    $translations_languages = $_POST['word_translation_language'] ?? [];
    $translations_words = $_POST['word_translation'] ?? [];

    // 翻訳の整形
    $translations = [];
    for ($i = 0; $i < count($translations_languages); $i++) {
        $lang = trim($translations_languages[$i]);
        $trans = trim($translations_words[$i]);
        if ($lang !== '' && $trans !== '') {
            $translations[] = [
                'language_code' => $lang,
                'translation'   => $trans
            ];
        }
    }


    // 必要なフィールドが揃っているか確認
    // （例として、翻訳が空配列の場合はスキップ）
    if ($language !== '' && $word !== '' && !empty($translations)) {
        require_once __DIR__ . '/../../src/Database.php';
        require_once __DIR__ . '/../../src/WordController.php';

        // データベース接続とコントローラーの初期化
        $db = new \TangoTraining\Database();
        $wordController = new \TangoTraining\WordController($db);

        // 新しい createWord は (user_id, language_code, word, note, translations)
        $result = $wordController->createWord($_SESSION['user_id'], $language, $word, $note, $translations);
    }
}

// 成功失敗に関わらず一覧へ遷移
header('Location: list.php');
exit;
