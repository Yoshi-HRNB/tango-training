<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../src/LanguageCode.php';
use TangoTraining\LanguageCode;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method.']);
    exit;
}

$text = isset($_POST['text']) ? $_POST['text'] : '';
$language = isset($_POST['sourceLanguage']) ? $_POST['sourceLanguage'] : '英語';
$targetLanguage = isset($_POST['targetLanguage']) ? $_POST['targetLanguage'] : '日本語';
$level = isset($_POST['level']) ? (int)$_POST['level'] : 1;

if (!$text) {
    echo json_encode(['error' => '翻訳する文章が入力されていません。']);
    exit;
}

$levelDescription = [
    1 => "抽出レベル(100%)",
    2 => "抽出レベル(80%)",
    3 => "抽出レベル(60%)",
    4 => "抽出レベル(40%)",
    5 => "抽出レベル(20%)"
];

$apiKey = "AIzaSyDEOFn-7w_hlqJn8hQFe9oHfciqoIgeJI4";  
$apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}";

$prompt = "
以下の {$language} の文章を{$targetLanguage}に翻訳し、指定されたレベル(1〜5)に応じた{$language}の単語を、熟語ごとに抽出してください。
アラビア数字の抽出は不要。抽出する単語の単位は熟語を優先してください。
複合語の場合は、意味を分けて、noteに表示してください。
低いレベルでは抽出する単語の量を多くし、高いレベルでは難易度の高い単語のみを抽出してください。
各単語の品詞（名詞、動詞、形容詞など）も「part_of_speech」フィールドに出力してください。
品詞の言語は、{$targetLanguage}で出力してください。
注意: 単語のwordフィールドには必ず{$language}の単語を出力してください。
";

// 日本語の場合はフリガナ（reading）を追加するよう指示
if ($language === '日本語') {
    $prompt .= "
また、抽出された単語が日本語の場合は、「reading」フィールドにフリガナ（カタカナ）も追加してください。
";
}

$prompt .= "
結果は必ず以下の JSON 形式のみで出力してください（meaningも必ず出力）。

余計な説明文やテキストは含めないでください。

### フォーマット例:
{
  \"translated_text\": \"（翻訳された文章）\",
  \"extracted_words\": [
    {
      \"word\": \"（{$language}の単語）\",";

// 日本語の場合はreading（フリガナ）のフィールドを追加
if ($language === '日本語') {
    $prompt .= "
      \"reading\": \"（フリガナ）\",";
}

$prompt .= "
      \"part_of_speech\": \"（品詞）\",
      \"meaning\": \"（{$targetLanguage}での意味）\",
      \"note\": \"（補足、複合語の場合は分解して意味を表示）\"
    },
    {
      \"word\": \"（{$language}の単語）\",";

// 日本語の場合はreading（フリガナ）のフィールドを追加
if ($language === '日本語') {
    $prompt .= "
      \"reading\": \"（フリガナ）\",";
}

$prompt .= "
      \"part_of_speech\": \"（品詞）\",
      \"meaning\": \"（{$targetLanguage}での意味）\",
      \"note\": \"（補足、複合語の場合は分解して意味を表示）\"
    }
  ]
}

### 翻訳元の文章:
{$text}

### 単語抽出レベル:
{$level} - {$levelDescription[$level]}
";

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];
$jsonData = json_encode($data);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(["error" => "APIリクエストエラー: $error"]);
    exit;
}

$responseData = json_decode($response, true);

// JSONとしてパースできなかった場合はHTMLなどのエラーページが返っている可能性があります
if ($responseData === null) {
    echo json_encode(["error" => "APIからの応答が正しいJSON形式ではありません。", "raw" => $response]);
    exit;
}

$candidateText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

// 応答がHTML形式の場合のチェック
if (strpos(trim($candidateText), '<') === 0) {
    echo json_encode(["error" => "Gemini APIの応答がHTML形式です。", "raw" => $candidateText]);
    exit;
}

if (!$candidateText) {
    echo json_encode(["error" => "Geminiからの有効な応答が得られませんでした。"]);
    exit;
}

$cleanText = preg_replace('/^```json\s*/', '', $candidateText);
$cleanText = preg_replace('/\s*```$/', '', $cleanText);

$jsonOutput = json_decode($cleanText, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "error" => "Gemini応答のJSONデコードに失敗: " . json_last_error_msg(),
        "raw" => $candidateText
    ]);
    exit;
}

// 抽出された単語に必要なフィールドが含まれていることを確認
$extracted_words = [];
foreach ($jsonOutput["extracted_words"] ?? [] as $word) {
    $wordData = [
        "word" => $word["word"] ?? "",
        "part_of_speech" => $word["part_of_speech"] ?? "",
        "meaning" => $word["meaning"] ?? "",
        "note" => $word["note"] ?? ""
    ];
    
    // 日本語の場合はreadingを追加
    if ($language === '日本語' && isset($word["reading"])) {
        $wordData["reading"] = $word["reading"];
    }
    
    $extracted_words[] = $wordData;
}

echo json_encode([
    "translated_text" => $jsonOutput["translated_text"] ?? "",
    "extracted_words" => $extracted_words
]);
?>
