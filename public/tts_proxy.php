<?php
/**
 * TTSプロキシ - Google Translate音声APIへのアクセスを提供
 * CORSの問題を回避するため、サーバーサイドでリクエストを処理
 */

// エラー表示（開発時のみ有効にしてください）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// リクエストパラメータの確認
$text = isset($_GET['q']) ? $_GET['q'] : '';
$lang = isset($_GET['tl']) ? $_GET['tl'] : 'en';

// 入力検証
if (empty($text)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Text parameter (q) is required']);
    exit;
}

// テキストの長さ制限（Google TTSは短いテキストのみ対応）
if (strlen($text) > 150) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Text too long (max 150 characters)']);
    exit;
}

// Google TTSのURL生成
$url = 'https://translate.google.com/translate_tts?ie=UTF-8&client=tw-ob&tl=' . urlencode($lang) . '&q=' . urlencode($text);

// cURLセッションの初期化
$ch = curl_init();

// cURLオプションの設定
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

// リクエストの実行
$audio_data = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// エラーチェック
if ($http_code !== 200) {
    header('HTTP/1.1 502 Bad Gateway');
    echo json_encode(['error' => 'Failed to fetch audio from Google TTS', 'code' => $http_code]);
    exit;
}

// エラーチェック
if (curl_errno($ch)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    exit;
}

// cURLセッションのクローズ
curl_close($ch);

// 音声データのMIMEタイプを設定
header('Content-Type: audio/mpeg');
header('Cache-Control: public, max-age=86400'); // 24時間のキャッシュ
header('Access-Control-Allow-Origin: *');

// 音声データを出力
echo $audio_data;
?> 