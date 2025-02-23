<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * list.php
 * 単語一覧を表示するページ。
 */
session_start();

// ログイン必須
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}


require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/WordController.php';


$db = new \TangoTraining\Database();
$wordController = new \TangoTraining\WordController($db);

// 利用可能な翻訳言語を取得
$availableLanguages = $wordController->getAvailableTranslationLanguages();

// 検索パラメータ取得
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selectedLanguages = isset($_GET['translation_languages']) ? $_GET['translation_languages'] : [];

// サニタイズ
$searchSanitized = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
$selectedLanguagesSanitized = array_map(function($lang) {
    return htmlspecialchars($lang, ENT_QUOTES, 'UTF-8');
}, $selectedLanguages);

// セッションからユーザーIDを取得
$user_id = (int)$_SESSION['user_id'];

// 翻訳言語のフィルタリング条件を設定
$filterLanguages = !empty($selectedLanguages) ? $selectedLanguages : null;

// 取得する単語一覧（ユーザーIDを渡す）
$words = $wordController->getWords($user_id, $search, $filterLanguages);


?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>単語一覧</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
  <h1>単語一覧</h1>

  <form method="get" action="">
    <input type="text" name="search" placeholder="単語や訳を検索"
           value="<?php echo $searchSanitized; ?>">

    <label for="translation_languages">翻訳言語:</label>
    <select name="translation_languages[]" id="translation_languages" multiple size="5">
      <?php foreach ($availableLanguages as $code => $name): ?>
        <option value="<?php echo htmlspecialchars($code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo in_array($code, $selectedLanguages) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit">検索</button>
    <!-- 全件表示用のリセットボタンを追加 -->
    <a href="list.php" class="button">リセット</a>
  </form>
  <br>

  <p><a href="add.php">+ 単語を登録</a> | <a href="../index.php">トップへ戻る</a></p>

  <table border="1" cellspacing="0" cellpadding="5">
    <tr>
      <th>ID</th>
      <th>言語</th>
      <th>単語</th>
      <th>訳</th>
      <th>ノート</th>
      <th>作成日時</th>
      <th>編集</th>
    </tr>
    <?php if (!empty($words)): ?>
      <?php foreach ($words as $w): ?>
        <tr>
          <td><?php echo htmlspecialchars($w['word_id'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($w['language_code'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($w['word'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <?php 
              if (!empty($w['translations']) && is_array($w['translations'])) {
                  // 言語ごとに改行して表示
                  $translatedLines = [];
                  foreach ($w['translations'] as $langCode => $translation) {
                      $langName = isset($availableLanguages[$langCode]) ? $availableLanguages[$langCode] : $langCode;
                      $translatedLines[] = htmlspecialchars($langName, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($translation, ENT_QUOTES, 'UTF-8');
                  }
                  echo implode('<br>', $translatedLines);
              } else {
                  echo htmlspecialchars($w['translations'] ?? '-', ENT_QUOTES, 'UTF-8');
              }
            ?>
          </td>
          <td><?php echo htmlspecialchars($w['note'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
          <td><?php echo htmlspecialchars($w['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
          <td>
            <a href="edit.php?id=<?php echo (int)$w['word_id']; ?>">編集</a>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="7">登録された単語はありません。</td></tr>
    <?php endif; ?>
  </table>
</div>
</body>
</html>



