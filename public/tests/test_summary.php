<?php
/**
 * test_summary.php
 *
 * 同じ test_id の全 branch を合計して、word_id単位で正解・誤答数を集計し、
 * 正解率が低い順に並べて表示するサマリ画面。
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// テストエラー
// echo $undefined_variable; // Commented out to avoid warning

// init.phpを読み込んでセッション管理を統一する
require_once __DIR__ . '/../../src/init.php';

// ログインチェック
if (!isset($_SESSION['user_id'], $_SESSION['test_id'])) {
    header('Location: ../login.php');
    exit;
}


require_once __DIR__ . '/../../src/Database.php';
use TangoTraining\Database;

$db  = new Database();
$pdo = $db->getConnection();

$userId = (int)$_SESSION['user_id'];
$testId = (int)$_SESSION['test_id'];

// used_test_words を集計
// test_id ごとに正解/不正解を合算
$sql = "
SELECT
  w.word_id,
  w.word,
  w.language_code,
  SUM(CASE WHEN u.is_correct = 1 THEN 1 ELSE 0 END) AS total_correct,
  SUM(CASE WHEN u.is_correct = 0 THEN 1 ELSE 0 END) AS total_wrong
FROM used_test_words u
JOIN words w ON u.word_id = w.word_id
WHERE u.test_id = :test_id
  AND w.user_id = :user_id
GROUP BY w.word_id, w.word, w.language_code

";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':test_id' => $testId,
    ':user_id' => $userId
]);
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// 正解率計算し、低い順にソート
foreach($rows as &$r) {
    $total = $r['total_correct'] + $r['total_wrong'];
    $acc   = ($total > 0) ? ($r['total_correct'] / $total) * 100 : 0;
    $r['accuracy'] = $acc;
}
unset($r);

usort($rows, function($a, $b){
    return $a['accuracy'] <=> $b['accuracy']; // 昇順(低い→高い)
});
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>テスト結果サマリ</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
  <h1>テストサマリ (test_id=<?php echo (int)$testId; ?>)</h1>
  <p>同一test_id内で何度でも再テストした合計結果を表示します。</p>

  <?php if(empty($rows)): ?>
    <p>集計データがありません。</p>
  <?php else: ?>
    <table border="1" cellpadding="5" cellspacing="0">
      <tr>
        <th>Word ID</th>
        <th>Language</th>
        <th>Word</th>
        <th>Correct</th>
        <th>Wrong</th>
        <th>Accuracy</th>
      </tr>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['word_id']; ?></td>
          <td><?php echo htmlspecialchars($r['language_code'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($r['word'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo (int)$r['total_correct']; ?></td>
          <td><?php echo (int)$r['total_wrong']; ?></td>
          <td><?php echo sprintf('%.1f%%', $r['accuracy']); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <p><a href="../index.php">トップへ戻る</a></p>
</div>
</body>
</html>
