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
    1 => "抽出レベル（初級：基本的な単語を含め全単語抽出）",
    2 => "抽出レベル（初中級：頻出単語から専門的単語まで全て抽出）",
    3 => "抽出レベル（中級：やや高度な単語から全て抽出）",
    4 => "抽出レベル（中上級：難易度高めの単語から全て抽出）",
    5 => "抽出レベル（上級：専門的または非常に高度な単語のみ抽出）"
];

$apiKey = "AIzaSyDEOFn-7w_hlqJn8hQFe9oHfciqoIgeJI4";  
$apiUrl = "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$apiKey}";


$prompt = "
以下の {$language} の文章を{$targetLanguage}に翻訳し、指定されたレベル(1〜5)に応じた重要単語や熟語を抽出してください。
アラビア数字は抽出対象外です。熟語は単語より優先して抽出してください。
複合語は意味ごとに分解し、noteに分けて記載してください。
日本語以外の言語で、フリガナは不要。

抽出レベルの方針:
- レベル1：レベル1〜5（すべての未知単語を抽出）
- レベル2：レベル2〜5の単語を抽出
- レベル3：レベル3〜5の単語を抽出
- レベル4：レベル4〜5の難易度高め単語を抽出
- レベル5：専門的・非常に高度な単語のみ抽出

必須の単語情報:
- word: 必ず{$language}で表記
- meaning: {$targetLanguage}での意味
- part_of_speech: {$targetLanguage}表記（名詞・動詞・形容詞・副詞・熟語等）
- note: 複合語の分解や文法的注意点、補足事項など

【言語別の制約条件】
日本語の場合:
- フリガナ（reading）をカタカナで必ず追加

英語の場合:
- 冠詞（a, an, the）は抽出不要
- 複数形は原型をnoteに記載
- To不定詞は「to」と動詞の原型に分けて抽出
- 熟語（イディオム）は必ず抽出し、part_of_speechには「idiom」と明記
- 句動詞（phrasal verbs）を抽出し、part_of_speechには「phrasal verb」と明記
- 前置詞が重要な場合はnoteに前置詞の用法を記載
- 不規則動詞の場合、noteに原形・過去形・過去分詞を明記
- 派生語（名詞・形容詞・副詞など）がある場合、noteに派生形を記載
- 類義語や反意語があればnoteに補足
- 慣用表現（collocation）も抽出し、part_of_speechには「collocation」と明記

ベトナム語の場合:
- 類別詞（分類語）は必ずnoteに明記
- 声調記号を含め正確に表記
- 熟語や慣用句（thành ngữ）は「idiom」としてpart_of_speechに明記
- 類義語・反意語をnoteに記載

以下のJSONフォーマットを厳守し、それ以外の説明文を追加しないでください。

### JSONフォーマット:
{
  \"translated_text\": \"（翻訳された文章）\",
  \"extracted_words\": [
    {
      \"word\": \"（{$language}の単語）\",
";

if ($language === '日本語') {
    $prompt .= "      \"reading\": \"（フリガナ）\",";
}

$prompt .= "
      \"part_of_speech\": \"（品詞）\",
      \"meaning\": \"（{$targetLanguage}での意味）\",
      \"note\": \"（補足情報）\"
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
