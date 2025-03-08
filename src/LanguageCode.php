<?php

namespace TangoTraining;

/**
 * 言語コードの定義を管理するクラス
 * プロジェクト全体で言語コードを統一するために使用します
 */
class LanguageCode
{
    // 言語コード定数
    public const ENGLISH = 'en';
    public const VIETNAMESE = 'vi';
    public const JAPANESE = 'ja';
    public const FRENCH = 'fr';
    public const GERMAN = 'de';
    public const SPANISH = 'es';
    public const CHINESE = 'zh';

    // 言語コードから表示名へのマッピング
    private static $codeToName = [
        self::ENGLISH => '英語',
        self::VIETNAMESE => 'ベトナム語',
        self::JAPANESE => '日本語',
        self::FRENCH => 'フランス語',
        self::GERMAN => 'ドイツ語',
        self::SPANISH => 'スペイン語',
        self::CHINESE => '中国語',
    ];

    // 表示名から言語コードへのマッピング
    private static $nameToCode = [
        '英語' => self::ENGLISH,
        'ベトナム語' => self::VIETNAMESE,
        '日本語' => self::JAPANESE,
        'フランス語' => self::FRENCH,
        'ドイツ語' => self::GERMAN,
        'スペイン語' => self::SPANISH,
        '中国語' => self::CHINESE,
    ];

    /**
     * 全ての有効な言語コードを取得
     * 
     * @return array 言語コードの配列
     */
    public static function getAllCodes(): array
    {
        return array_keys(self::$codeToName);
    }

    /**
     * 言語コードから表示名を取得
     * 
     * @param string $code 言語コード
     * @return string 表示名。不明なコードの場合はコードをそのまま返す
     */
    public static function getNameFromCode(string $code): string
    {
        return self::$codeToName[$code] ?? $code;
    }

    /**
     * 表示名から言語コードを取得
     * 
     * @param string $name 表示名
     * @return string 言語コード。不明な表示名の場合はデフォルトでENGLISHを返す
     */
    public static function getCodeFromName(string $name): string
    {
        return self::$nameToCode[$name] ?? self::ENGLISH;
    }

    /**
     * 全ての言語コードと表示名のマッピングを取得
     * 
     * @return array コード => 名前 の連想配列
     */
    public static function getLanguageMap(): array
    {
        return self::$codeToName;
    }

    /**
     * JavaScriptで利用するための言語コード定義を出力
     * 
     * @return string JavaScriptのコード
     */
    public static function getJavaScriptDefinition(): string
    {
        $js = "const LanguageCode = {\n";
        
        // 定数の出力
        foreach (self::$codeToName as $code => $name) {
            $constName = strtoupper($code);
            $js .= "  {$constName}: '{$code}',\n";
        }
        
        // マッピング関数の出力
        $js .= "  nameToCode: " . json_encode(self::$nameToCode, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ",\n";
        $js .= "  codeToName: " . json_encode(self::$codeToName, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . ",\n";
        
        // ヘルパー関数
        $js .= "  getCodeFromName: function(name) {\n";
        $js .= "    return this.nameToCode[name] || this.EN;\n";
        $js .= "  },\n";
        $js .= "  getNameFromCode: function(code) {\n";
        $js .= "    return this.codeToName[code] || code;\n";
        $js .= "  }\n";
        
        $js .= "};\n";
        
        return $js;
    }
} 