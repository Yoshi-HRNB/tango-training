<?php
/**
 * fetch_test_words.php
 *
 * テスト用に単語を抽出し、JSON形式で返すスクリプト。
 * - セッションにある is_retry_test が true の場合、
 *   直前テストで間違えた単語だけを返す。
 * - それ以外は、WordController::getWords() にフィルタ条件を渡して取得。
 * - 取得した単語は translations をまとめて JSON に変換して返す。
 */

// エラー表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

// ログインチェック - Ajax専用なのでJSONでエラーを返す
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

header('Content-Type: application/json');

// 必要なクラス読込
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/WordController.php';

use TangoTraining\Database;
use TangoTraining\WordController;

$db = new Database();
$pdo = $db->getConnection();
$wordController = new WordController($db);

// セッション変数から各種情報を取得
$user_id    = (int)$_SESSION['user_id'];
$limit      = isset($_SESSION['test_limit']) ? (int)$_SESSION['test_limit'] : 5;
$language   = $_SESSION['test_language'] ?? '';   // 出題する単語の言語
$testType   = $_SESSION['test_type'] ?? '';       // 'retry_incorrect' など
$testFilter = $_SESSION['test_filter'] ?? [];     // ['low_accuracy' => bool, 'unseen_days'=>int,...]
$answerLang = $_SESSION['answer_lang'] ?? '';     // 答えの訳を特定言語に絞るか
$isRetryTest = $_SESSION['is_retry_test'];        // 再テスト判定
$branch_id = $_SESSION['branch_id'];              // 今回実施するテストの枝番

// 1) もし isRetryTest なら、直前の誤答単語を used_test_words から再抽出
if ($isRetryTest) {
    // 現在のtest_id（再テスト時は同じtest_idを使う）
    $testId = $_SESSION['test_id'] ?? null;
    if (!$testId) {
        http_response_code(400);
        echo json_encode(['error' => 'No test ID found for retry']);
        exit;
    }
    try {
        // デバッグ用の変数
        $debug_vars = [
            'is_retry_test' => $isRetryTest,
            'test_id' => $testId,
            'branch_id' => $branch_id,
            'user_id' => $user_id,
            'session' => array_keys($_SESSION)
        ];
        
        // まずは誤答のword_idを取得
        $sql = "
            SELECT utw.word_id
            FROM used_test_words utw
            WHERE utw.test_id = :test_id
            AND utw.branch_id = :branch_id - 1
            AND utw.is_correct = 0
            ORDER BY utw.created_at DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':test_id', $testId, PDO::PARAM_INT);
        $stmt->bindValue(':branch_id', $branch_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $wrongWords = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // デバッグ用：データがない場合の詳細情報
        if (empty($wrongWords)) {
            // テスト用のデータをチェック
            $checkSql = "SELECT * FROM used_test_words WHERE test_id = :test_id AND branch_id = :branch_id - 1 ORDER BY created_at DESC LIMIT 10";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([
                ':test_id' => $testId,
                ':branch_id' => $branch_id
            ]);
            $testRecords = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // 不正解の単語をチェック
            $countWrongSql = "SELECT COUNT(*) FROM used_test_words WHERE test_id = :test_id AND branch_id = :branch_id - 1 AND is_correct = 0";
            $countWrongStmt = $pdo->prepare($countWrongSql);
            $countWrongStmt->execute([
                ':test_id' => $testId,
                ':branch_id' => $branch_id
            ]);
            $wrongCount = $countWrongStmt->fetchColumn();
            
            // 間違い単語がなければ空配列を返して終了
            echo json_encode([
                'status' => 'no_wrong_words',
                'debug' => [
                    'test_id' => $testId,
                    'branch_id' => $branch_id,
                    'total_records' => count($testRecords),
                    'wrong_count' => $wrongCount,
                    'test_records' => $testRecords,
                    'debug_vars' => $debug_vars
                ]
            ]);
            exit;
        }

        // IN句でまとめて取得
        $placeholders = implode(',', array_fill(0, count($wrongWords), '?'));
        $sql2 = "
            SELECT
                w.word_id,
                w.language_code,
                w.word,
                -- translations
                GROUP_CONCAT(t.language_code SEPARATOR '|') AS translation_languages,
                GROUP_CONCAT(t.translation SEPARATOR '|')   AS translations,
                w.note,
                w.part_of_speech,
                w.reading
            FROM words w
            LEFT JOIN translations t ON w.word_id = t.word_id
            WHERE w.user_id = ?
              AND w.word_id IN ($placeholders)
            GROUP BY w.word_id
        ";
        $stmt2 = $pdo->prepare($sql2);

        // bindパラメータ (最初の?は user_id)
        $bindParams = [$user_id];
        // 続いて word_id群
        foreach ($wrongWords as $wid) {
            $bindParams[] = $wid;
        }
        $stmt2->execute($bindParams);
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // 配列を整形
        $formatted = formatWords($rows, $answerLang);

        echo json_encode($formatted, JSON_UNESCAPED_UNICODE);
        exit;
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// 2) 通常モードの場合 (WordControllerのgetWordsで取得)
$translationLangCodes = null; // ここでは翻訳言語を特に限定しない
$words = $wordController->getWords($user_id, null, $translationLangCodes, $testFilter);

// さらに $language が指定されていればソース言語コードで絞り込み
if (!empty($language)) {
    $words = array_filter($words, function($w) use($language){
        return ($w['language_code'] === $language);
    });
}

// 配列のindexを振り直す
$words = array_values($words);

// ランダム出題
shuffle($words);

// 上限数だけ切り出し
$words = array_slice($words, 0, $limit);

// 取得データを翻訳込みの形にフォーマット
$formatted = [];
foreach ($words as $row) {
    // getWords() では translationsを "en: hello, ja: こんにちは" のように
    // GROUP_CONCATした文字列で持ってくるが、ここでは個別に扱いたいので再分割は手間。
    // そこで簡易的に作り直すか、あるいはこのままでもOK。
    // ただし fetch_test_words.php では下の関数 formatWords() のほうが一貫性があるので
    // まとめて使う方針にする。
    $temp = [
        'word_id'       => (int)$row['word_id'],
        'language_code' => $row['language_code'],
        'word'          => $row['word'],
        'note'          => isset($row['note']) ? $row['note'] : '',
        'part_of_speech' => isset($row['part_of_speech']) ? $row['part_of_speech'] : '',
        'reading'       => isset($row['reading']) ? $row['reading'] : '',
        'translation_languages' => '',
        'translations'  => ''
    ];

    // 事前にJOINした "en: hello, ja: こんにちは" のような文字列を
    // 再度パースするなら、下記のように行う:
    if (!empty($row['translations'])) {
        // 例: "en: hello, ja: こんにちは"
        // これを区切りに分ける (本来はもう少し厳密なパースが必要)
        $pieces = explode(',', $row['translations']); // ["en: hello", " ja: こんにちは" ...]
        $langList = [];
        $transList= [];
        foreach ($pieces as $p) {
            // trimと分割
            $p = trim($p);
            $arr = explode(':', $p);
            if (count($arr) === 2) {
                $lang = trim($arr[0]);
                $tra  = trim($arr[1]);
                $langList[]  = $lang;
                $transList[] = $tra;
            }
        }
        // カンマ区切りでまとめる(古い実装と合わせる)
        $temp['translation_languages'] = implode('|', $langList);
        $temp['translations']          = implode('|', $transList);
    }

    $formatted[] = $temp;
}

// formatWords() に渡して整形し、answer_langフィルタも適用
$finalData = formatWords($formatted, $answerLang);

// JSON出力
echo json_encode($finalData, JSON_UNESCAPED_UNICODE);

/**
 * 単語データを整形して "translations" => [...], の配列に変換する関数
 * @param array $rows DB取得結果
 * @param string $answerLang 特定言語のみ表示する場合はその言語コードを指定
 * @return array
 */
function formatWords(array $rows, string $answerLang = ''): array
{
    $result = [];
    foreach ($rows as $r) {
        // translation_languagesは "en|ja|..."、 translationsは "hello|こんにちは|..."
        $langArr  = !empty($r['translation_languages']) ? explode('|', $r['translation_languages']) : [];
        $transArr = !empty($r['translations']) ? explode('|', $r['translations']) : [];

        // 翻訳の配列を作成
        $transData = [];
        for ($i = 0; $i < count($langArr); $i++) {
            if (isset($langArr[$i]) && isset($transArr[$i])) {
                $lc = trim($langArr[$i]);
                $tx = trim($transArr[$i]);
                // answer_lang が指定されていれば、その言語だけ追加
                if ($answerLang === '' || $answerLang === $lc) {
                    $transData[] = [
                        'language_code' => $lc,
                        'translation'   => $tx
                    ];
                }
            }
        }

        $result[] = [
            'word_id'       => (int)$r['word_id'],
            'language_code' => $r['language_code'],
            'word'          => $r['word'],
            'note'          => isset($r['note']) ? $r['note'] : '',
            'part_of_speech' => isset($r['part_of_speech']) ? $r['part_of_speech'] : '',
            'reading'       => isset($r['reading']) ? $r['reading'] : '',
            'translations'  => $transData
        ];
    }
    return $result;
}
