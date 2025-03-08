<?php
/**
 * saveWord.php
 * 単語をDBに保存 (重複チェックあり) ＋ JSONで結果を返す
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

// Ajax経由のPOSTのみを想定
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/WordController.php';
require_once __DIR__ . '/../../src/LanguageCode.php';

use TangoTraining\Database;
use TangoTraining\WordController;
use TangoTraining\LanguageCode;

// （ユーザーID）本来はセッションなどから取得する想定
// デモ用に仮で 1 にしておきます
$user_id = 1;

// 入力パラメータ取得
$word          = isset($_POST['word']) ? trim($_POST['word']) : '';
$meaning       = isset($_POST['meaning']) ? trim($_POST['meaning']) : '';
$note          = isset($_POST['note']) ? trim($_POST['note']) : '';
$part_of_speech = isset($_POST['part_of_speech']) ? trim($_POST['part_of_speech']) : '';
$reading       = isset($_POST['reading']) ? trim($_POST['reading']) : null;

// フォームから送信されたソース言語とターゲット言語を取得
$sourceLanguage = isset($_POST['sourceLanguage']) ? trim($_POST['sourceLanguage']) : '英語';
$targetLanguage = isset($_POST['targetLanguage']) ? trim($_POST['targetLanguage']) : '日本語';

// ソース言語のコードを取得（wordの言語）
$language_code = LanguageCode::getCodeFromName($sourceLanguage);

// 言語コードが直接指定された場合はそれを優先
if (isset($_POST['language_code']) && trim($_POST['language_code']) !== '') {
    // 言語コードが表示名として送信された場合は、コードに変換
    if (!in_array($_POST['language_code'], LanguageCode::getAllCodes())) {
        $language_code = LanguageCode::getCodeFromName($_POST['language_code']);
    } else {
        $language_code = trim($_POST['language_code']);
    }
}

// ターゲット言語（翻訳先言語）のコードを取得
$target_language_code = LanguageCode::getCodeFromName($targetLanguage);

if (!$word) {
    echo json_encode(['error' => '単語が未入力です。']);
    exit;
}

try {
    // DB接続
    $db = new Database();  // Databaseクラスのコンストラクタ内でPDO接続を行う想定
    $wc = new WordController($db);

    // --- 重複チェック ---
    // たとえば words テーブルで user_id & word が既に存在するか確認
    // すでに WordController に同様のメソッドがあればそちらを使う
    // ここでは簡易チェック例を直書きします
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM words WHERE user_id = :user_id AND word = :word');
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':word', $word, PDO::PARAM_STR);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        // 重複エラー
        echo json_encode(['error' => '同じ単語が既に登録されています。']);
        exit;
    }

    // --- DBに登録 ---
    // WordController::createWord($user_id, $language_code, $word, $note, $part_of_speech, $translations, $reading)
    // translations は配列で渡す
    $translations = [];
    if ($meaning) {
        // 翻訳を登録 - ターゲット言語コードを使用
        $translations[] = [
            'language_code' => $target_language_code,
            'translation'   => $meaning
        ];
    }

    // 読みを渡す（言語に関わらず）
    $result = $wc->createWord($user_id, $language_code, $word, $note, $part_of_speech, $translations, $reading);
    if (!$result) {
        echo json_encode(['error' => 'DB登録に失敗しました。']);
        exit;
    }

    // 登録された word_id を取得
    $word_id = $pdo->lastInsertId(); // createWord内でも取得可能

    echo json_encode([
        'success' => true,
        'word_id' => $word_id
    ]);
    exit;

} catch (\Exception $e) {
    echo json_encode(['error' => 'サーバーエラー: ' . $e->getMessage()]);
    exit;
}
