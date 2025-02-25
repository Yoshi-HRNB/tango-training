<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["error" => "Invalid request method."]);
    exit;
}

if (!isset($_POST['word']) || empty($_POST['word'])) {
    echo json_encode(["error" => "単語が指定されていません。"]);
    exit;
}

$word = $_POST['word'];
$command = escapeshellcmd("python3 nltk_script.py " . escapeshellarg($word));
$output = shell_exec($command);

if ($output === null) {
    echo json_encode(["error" => "Pythonスクリプトが実行されませんでした。"]);
    exit;
}

$data = json_decode($output, true);
if ($data === null) {
    echo json_encode(["error" => "JSONデコードに失敗しました。"]);
    exit;
}

echo json_encode($data);
?>
