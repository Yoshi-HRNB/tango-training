<?php

namespace TangoTraining;

use PDO;
use PDOException;


/**
 * WordController
 * ---------------
 * words テーブルに対する操作をまとめたクラス。
 */
class WordController
{
    /** @var PDO $pdo データベース接続オブジェクト */
    private $pdo;

    /**
     * コンストラクタ
     * @param Database $db Database クラスのインスタンス
     */
    public function __construct(Database $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * 単語一覧を取得する
     * 
     * @param int $user_id ユーザーID
     * @param string|null $search 検索キーワード
     * @param array|null $translationLanguageCodes 検索対象の翻訳言語コードの配列
     * @param array|null $testFilter テストに利用するフィルタ条件
     *   例: ['low_accuracy' => true, 'unseen_days' => 7] など
     * @return array
     */
    public function getWords(
        int $user_id,
        ?string $search = null,
        ?array $translationLanguageCodes = null,
        ?array $testFilter = null
    ): array {
        try {
            // ベースのSELECT文
            // word_statistics もJOINして条件に使えるようにする
            $sql = "
                SELECT
                    w.word_id,
                    w.language_code,
                    w.word,
                    w.note,
                    w.created_at,
                    GROUP_CONCAT(CONCAT(t.language_code, ': ', t.translation) SEPARATOR ', ') AS translations,
                    ws.test_count,
                    ws.correct_count,
                    ws.wrong_count,
                    ws.accuracy_rate,
                    ws.last_test_date
                FROM
                    words w
                LEFT JOIN
                    translations t ON w.word_id = t.word_id
                LEFT JOIN
                    word_statistics ws ON w.word_id = ws.word_id
                WHERE
                    w.user_id = :user_id
            ";

            $params = [
                ':user_id' => $user_id
            ];
            $conditions = [];

            // 検索キーワード
            if (!empty($search)) {
                $conditions[] = "(w.word LIKE :search OR t.translation LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }

            // 翻訳言語コードの絞り込み
            if (!empty($translationLanguageCodes)) {
                $placeholders = [];
                foreach ($translationLanguageCodes as $index => $code) {
                    $placeholder = ":lang_code_" . $index;
                    $placeholders[] = $placeholder;
                    $params[$placeholder] = $code;
                }
                $conditions[] = "t.language_code IN (" . implode(", ", $placeholders) . ")";
            }

            // テストフィルタ
            // 例: ['low_accuracy' => true, 'unseen_days' => 7] などを想定
            if (!empty($testFilter)) {
                // 例1: accuracy_rate < 80 の単語だけ
                if (!empty($testFilter['low_accuracy'])) {
                    // word_statistics がない場合はNULLなので coalesce で 0扱い
                    $conditions[] = "COALESCE(ws.accuracy_rate, 0) < 80";
                }
                // 例2: 最後にテストしたのが X日以上前 or テストしたことがない
                if (!empty($testFilter['unseen_days'])) {
                    $days = (int)$testFilter['unseen_days'];
                    // last_test_date がNULL(まだテストしていない) または
                    // last_test_date < 現在- X日
                    $conditions[] = "(
                      ws.last_test_date IS NULL
                      OR ws.last_test_date < DATE_SUB(NOW(), INTERVAL {$days} DAY)
                    )";
                }
            }

            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }

            $sql .= " GROUP BY w.word_id ORDER BY w.word_id DESC";

            $stmt = $this->pdo->prepare($sql);

            // バインドするパラメータ
            foreach ($params as $key => $value) {
                if ($key === ':user_id') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value, PDO::PARAM_STR);
                }
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getWords Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 利用可能な翻訳言語コードと名称を取得する
     * @return array
     */
    public function getAvailableTranslationLanguages(): array
    {
        try {
            $sql = "SELECT DISTINCT language_code FROM translations ORDER BY language_code ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $codes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];

            // 言語コードと名称のマッピング（必要に応じて拡張可能）
            $languageMap = [
                'en' => '英語',
                'es' => 'スペイン語',
                'fr' => 'フランス語',
                'de' => 'ドイツ語',
                'zh' => '中国語',
                'ja' => '日本語',
                'vi' => 'ベトナム語',
                // 他の言語コードと名称を追加
            ];

            $availableLanguages = [];
            foreach ($codes as $code) {
                if (isset($languageMap[$code])) {
                    $availableLanguages[$code] = $languageMap[$code];
                } else {
                    // 未定義の言語コードの場合はコードをそのまま表示
                    $availableLanguages[$code] = $code;
                }
            }

            return $availableLanguages;
        } catch (PDOException $e) {
            error_log('getAvailableTranslationLanguages Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 単語を新規登録する
     * 
     * @param int $user_id 登録ユーザのID（wordsテーブルに必要）
     * @param string $language_code 言語コード
     * @param string $word 単語
     * @param string|null $note 補足情報
     * @param string|null $part_of_speech 品詞情報
     * @param array $translations 翻訳の配列（各要素は ['language_code' => ..., 'translation' => ...]）
     * @return bool 成功した場合は true、それ以外は false
     */
    public function createWord(int $user_id, string $language_code, string $word, ?string $note, ?string $part_of_speech = null, array $translations): bool
    {
        try {
            $this->pdo->beginTransaction();

            // words テーブルに挿入
            $stmt = $this->pdo->prepare('
                INSERT INTO words (user_id, language_code, word, part_of_speech, note, created_at, updated_at)
                VALUES (:user_id, :language_code, :word, :part_of_speech, :note, NOW(), NOW())
            ');
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':language_code', $language_code);
            $stmt->bindValue(':word', $word);
            $stmt->bindValue(':part_of_speech', $part_of_speech);
            $stmt->bindValue(':note', $note);
            $stmt->execute();

            // 挿入した単語の ID を取得
            $word_id = $this->pdo->lastInsertId();

            // translations テーブルに翻訳を挿入
            $stmtTranslation = $this->pdo->prepare('
                INSERT INTO translations (word_id, language_code, translation, created_at, updated_at)
                VALUES (:word_id, :language_code, :translation, NOW(), NOW())
            ');

            foreach ($translations as $trans) {
                $stmtTranslation->bindValue(':word_id', $word_id, PDO::PARAM_INT);
                $stmtTranslation->bindValue(':language_code', $trans['language_code']);
                $stmtTranslation->bindValue(':translation', $trans['translation']);
                $stmtTranslation->execute();
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('createWord Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ID から単語を取得する
     * @param int $word_id
     * @return array|false
     */
    public function getWordById(int $word_id)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM words WHERE word_id = :word_id');
            $stmt->bindValue(':word_id', $word_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('getWordById Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * word_id から翻訳一覧を取得する
     * @param int $word_id
     * @return array
     */
    public function getTranslationsByWordId(int $word_id): array
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM translations WHERE word_id = :word_id');
            $stmt->bindValue(':word_id', $word_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('getTranslationsByWordId Error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 単語を追加する（簡易版）
     * @param int $user_id
     * @param string $language_code
     * @param string $word
     * @return bool
     */
    public function addWord(int $user_id, string $language_code, string $word): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO words (user_id, language_code, word, created_at, updated_at)
                VALUES (:user_id, :language_code, :word, NOW(), NOW())
            ');
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':language_code', $language_code);
            $stmt->bindValue(':word', $word);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('addWord Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 単語を更新する
     * @param int $word_id
     * @param int $user_id
     * @param string $language_code
     * @param string $word
     * @param string|null $note
     * @return bool
     */
    public function updateWord(int $word_id, int $user_id, string $language_code, string $word, ?string $note = null): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE words
                SET user_id = :user_id,
                    language_code = :language_code,
                    word = :word,
                    note = :note,
                    updated_at = NOW()
                WHERE word_id = :word_id
            ');
            $stmt->bindValue(':word_id', $word_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':language_code', $language_code);
            $stmt->bindValue(':word', $word);
            $stmt->bindValue(':note', $note);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('updateWord Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 翻訳を削除する
     * @param int $word_id
     * @return bool
     */
    public function deleteTranslationsByWordId(int $word_id): bool
    {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM translations WHERE word_id = :word_id');
            $stmt->bindValue(':word_id', $word_id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('deleteTranslationsByWordId Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 翻訳を追加する
     * @param int $word_id
     * @param string $language_code
     * @param string $translation
     * @return bool
     */
    public function addTranslation(int $word_id, string $language_code, string $translation): bool
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO translations (word_id, language_code, translation, created_at, updated_at)
                VALUES (:word_id, :language_code, :translation, NOW(), NOW())
            ');
            $stmt->bindValue(':word_id', $word_id, PDO::PARAM_INT);
            $stmt->bindValue(':language_code', $language_code);
            $stmt->bindValue(':translation', $translation);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log('addTranslation Error: ' . $e->getMessage());
            return false;
        }
    }
}

