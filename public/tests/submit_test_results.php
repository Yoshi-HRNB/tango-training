
<?php
/**
 * submit_test_results.php
 *
 * フロント(例: reveal_test.php)で回答した結果を受け取り、
 * DB(tests, used_test_words, word_statistics) に反映するスクリプト。
 *
 * 1) testsテーブル … branch_idの更新, score/total_questionsなど更新
 * 2) used_test_words … (test_id, branch_id, word_id, retry_count)
 * 3) word_statistics … 累計正解数/誤答数など更新
 */

session_start();
header('Content-Type: application/json');

// セッション変数がなければ不正
if (!isset($_SESSION['user_id'], $_SESSION['test_id'], $_SESSION['branch_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// JSONで送られてきた回答データを取得
$input = json_decode(file_get_contents('php://input'), true);

// JSONが正しく解析できていない場合
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'Invalid JSON']);
    exit;
}

// DB接続
require_once __DIR__ . '/../../src/Database.php';
use TangoTraining\Database;

$db = new Database();
$pdo= $db->getConnection();

// セッションから情報
$userId   = (int)$_SESSION['user_id'];
$testId   = (int)$_SESSION['test_id'];
$branchId = (int)$_SESSION['branch_id'];
$testType    = $_SESSION['test_type'];
$isRetryTest = $_SESSION['is_retry_test'];

// POSTされてきた回答内容
$words      = $input['words'] ?? [];
$userChecks = $input['userChecks'] ?? [];
$timeSpent  = (int)($input['timeSpent'] ?? 0);

// 問題数と回答数が一致していない場合はエラー
if (count($words) !== count($userChecks)) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'error'=>'Data mismatch']);
    exit;
}

// 集計（正解数）
$totalQuestions = count($words);
$correctCount   = 0;
foreach ($userChecks as $chk) {
    if ($chk === true) $correctCount++;
}
$attemptDate = date('Y-m-d H:i:s');

try {
    $pdo->beginTransaction();

    // (1) testsテーブルの更新: 
    if ($isRetryTest) {
        $sqlT = "
            INSERT INTO tests
                (test_id, test_type, user_id, attempt_date, branch_id, score, total_questions, created_at, updated_at)
            VALUES
                (:test_id, :test_type, :user_id, :attempt_date, :branch_id, :score, :total_questions, NOW(), NOW())
        ";
  
        // プリペアドステートメントの準備
        $stmtT = $pdo->prepare($sqlT);
        
        // パラメータのバインド
        $stmtT->execute([
            ':test_id'          => $testId,          // 前回のテストID
            ':test_type'        => $testType,        // テストの種類を指定
            ':user_id'          => $userId,          // ユーザーIDを指定
            ':attempt_date'     => $attemptDate,     // 試行日を指定
            ':branch_id'        => $branchId,        // ブランチIDを指定
            ':score'            => $correctCount,           // スコアを指定
            ':total_questions'  => $totalQuestions,  // 総質問数を指定
        ]);
    } else {
        $sqlT = "
            UPDATE tests
            SET
            branch_id = :branch_id,
            attempt_date = :attempt_date,
            score = :score,
            total_questions = :total_questions,
            updated_at = NOW()
            WHERE test_id = :test_id
            LIMIT 1
        ";
        $stmtT = $pdo->prepare($sqlT);
        $stmtT->execute([
            ':branch_id'       => $branchId,
            ':attempt_date'    => $attemptDate,
            ':score'           => $correctCount,
            ':total_questions' => $totalQuestions,
            ':test_id'         => $testId,
        ]);  
    }

    // (2) used_test_words に回答履歴をINSERT
    $sqlUsed = "
      INSERT INTO used_test_words
        (test_id, branch_id, word_id, is_correct, created_at, updated_at)
      VALUES
        (:test_id, :branch_id, :word_id, :is_correct, NOW(), NOW())
    ";
    $stmtU = $pdo->prepare($sqlUsed);

    // (3) word_statistics 更新用SQL
    $sqlCheck = "SELECT * FROM word_statistics WHERE word_id = :word_id LIMIT 1";
    $stmtC = $pdo->prepare($sqlCheck);

    $sqlInsert = "
      INSERT INTO word_statistics
        (word_id, test_count, correct_count, wrong_count, accuracy_rate,
         first_test_date, last_test_date, last_result,
         created_at, updated_at)
      VALUES
        (:word_id, :test_count, :correct_count, :wrong_count, :accuracy_rate,
         NOW(), NOW(), :last_result,
         NOW(), NOW())
    ";
    $stmtI = $pdo->prepare($sqlInsert);

    $sqlUpdate = "
      UPDATE word_statistics
      SET test_count = :test_count,
          correct_count = :correct_count,
          wrong_count = :wrong_count,
          accuracy_rate = :accuracy_rate,
          last_test_date = NOW(),
          last_result = :last_result,
          updated_at = NOW()
      WHERE word_id = :word_id
    ";
    $stmtUpd = $pdo->prepare($sqlUpdate);

    // フラグ: 間違いが1つでもあれば true
    $has_wrong_words = false;

    // 各問題について処理
    for ($i = 0; $i < $totalQuestions; $i++) {
        $w         = $words[$i];
        $isCorrect = ($userChecks[$i] === true);

        // used_test_words へINSERT
        $stmtU->execute([
            ':test_id'    => $testId,
            ':branch_id'  => $branchId,
            ':word_id'    => $w['word_id'],
            // is_correct = 1(正解),0(不正解) という方針
            ':is_correct' => ($isCorrect ? 1 : 0)
        ]);

        // word_statistics テーブルを更新
        $stmtC->execute([':word_id' => $w['word_id']]);
        $row = $stmtC->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            // 新規登録
            $testCount    = 1;
            $correct      = $isCorrect ? 1 : 0;
            $wrong        = $isCorrect ? 0 : 1;
            $accuracy     = ($correct / $testCount) * 100;
            $lastResult   = $isCorrect ? 1 : 0;
            $stmtI->execute([
                ':word_id'       => $w['word_id'],
                ':test_count'    => $testCount,
                ':correct_count' => $correct,
                ':wrong_count'   => $wrong,
                ':accuracy_rate' => $accuracy,
                ':last_result'   => $lastResult,
            ]);
        } else {
            // 既存レコードを更新
            $testCount    = $row['test_count'] + 1;
            $correct      = $row['correct_count'] + ($isCorrect ? 1 : 0);
            $wrong        = $row['wrong_count']   + ($isCorrect ? 0 : 1);
            $accuracy     = ($correct / $testCount) * 100;
            $lastResult   = $isCorrect ? 1 : 0;
            $stmtUpd->execute([
                ':test_count'    => $testCount,
                ':correct_count' => $correct,
                ':wrong_count'   => $wrong,
                ':accuracy_rate' => $accuracy,
                ':last_result'   => $lastResult,
                ':word_id'       => $w['word_id']
            ]);
        }

        // 間違いがあればフラグを立てる
        if (!$isCorrect) {
            $has_wrong_words = true;
        }
    }

    $pdo->commit();

    // セッションに last_test_id を設定
    $_SESSION['last_test_id'] = $testId;

    echo json_encode([
        'success' => true,
        'message' => '保存完了',
        'test_id' => $testId,
        'branch_id' => $branchId,
        'has_wrong_words' => $has_wrong_words
    ]);
} catch (\Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
?>

