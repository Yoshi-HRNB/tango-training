<?php
/**
 * update.php
 * edit.php のフォームから受け取り、DBを更新。
 */
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $language = trim($_POST['language_code'] ?? '');
    $word = trim($_POST['word'] ?? '');
    $supplement = trim($_POST['supplement'] ?? '');
    $translation_languages = $_POST['word_translation_language'] ?? [];
    $translations = $_POST['word_translation'] ?? [];

    if ($id > 0 && $language !== '' && $word !== '') {
        require_once __DIR__ . '/../../src/Database.php';
        require_once __DIR__ . '/../../src/WordController.php';

        $db = new \TangoTraining\Database();
        $wordController = new \TangoTraining\WordController($db);

        // updateWord のパラメータが (word_id, user_id, language_code, word, note=?)
        $success = $wordController->updateWord($id, $_SESSION['user_id'], $language, $word, $supplement);

        if ($success) {
            // 既存の翻訳を削除
            $wordController->deleteTranslationsByWordId($id);

            // 新たに翻訳を追加
            foreach ($translation_languages as $index => $trans_lang) {
                $trans_word = trim($translations[$index] ?? '');
                if ($trans_lang !== '' && $trans_word !== '') {
                    $wordController->addTranslation($id, $trans_lang, $trans_word);
                }
            }
        }
    }
}
header('Location: list.php');
exit;

