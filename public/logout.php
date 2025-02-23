<?php
/**
 * logout.php
 * ログアウト処理。セッションを破棄してトップへ戻る。
 */

session_start();
session_unset();
session_destroy();

header('Location: index.php');
exit;
