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
                    w.part_of_speech,
                    w.reading,
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
            if (!empty($testFilter)) {
                // テスト状況フィルター
                if (!empty($testFilter['has_been_tested'])) {
                    $conditions[] = "ws.test_count > 0";
                }
                
                if (!empty($testFilter['not_tested'])) {
                    $conditions[] = "(ws.test_count IS NULL OR ws.test_count = 0)";
                }
                
                // 正解率フィルター
                if (!empty($testFilter['min_accuracy_rate'])) {
                    $minAccuracyRate = (int)$testFilter['min_accuracy_rate'];
                    // テストしていない単語は除外
                    $conditions[] = "ws.test_count > 0 AND ws.accuracy_rate >= {$minAccuracyRate}";
                }
                
                if (!empty($testFilter['max_accuracy_rate'])) {
                    $maxAccuracyRate = (int)$testFilter['max_accuracy_rate'];
                    // テストしていない単語は含む
                    $conditions[] = "(ws.test_count IS NULL OR ws.accuracy_rate <= {$maxAccuracyRate})";
                }
                
                // 登録日フィルター
                if (!empty($testFilter['registration_start_date'])) {
                    $registrationStartDate = $testFilter['registration_start_date'] . ' 00:00:00';
                    $conditions[] = "w.created_at >= :registration_start_date";
                    $params[':registration_start_date'] = $registrationStartDate;
                }
                
                if (!empty($testFilter['registration_end_date'])) {
                    $registrationEndDate = $testFilter['registration_end_date'] . ' 23:59:59';
                    $conditions[] = "w.created_at <= :registration_end_date";
                    $params[':registration_end_date'] = $registrationEndDate;
                }
                
                // 1. 学習状況に関するフィルター
                
                // テスト回数制限: 指定回数以上テストした単語を除外
                if (!empty($testFilter['min_test_count'])) {
                    $minTestCount = (int)$testFilter['min_test_count'];
                    $conditions[] = "COALESCE(ws.test_count, 0) < {$minTestCount}";
                }
                
                // 学習頻度: 最後にテストしたのが X日以上前 or テストしたことがない
                if (!empty($testFilter['unseen_days'])) {
                    $days = (int)$testFilter['unseen_days'];
                    // last_test_date がNULL(まだテストしていない) または
                    // last_test_date < 現在- X日
                    $conditions[] = "(
                      ws.last_test_date IS NULL
                      OR ws.last_test_date < DATE_SUB(NOW(), INTERVAL {$days} DAY)
                    )";
                }
                
                // 2. 正答率・難易度に関するフィルター
                
                // 正解率の低い単語だけ
                if (!empty($testFilter['low_accuracy'])) {
                    // word_statistics がない場合はNULLなので coalesce で 0扱い
                    $conditions[] = "COALESCE(ws.accuracy_rate, 0) < 80";
                }
                
                // 3. 期間・登録日に関するフィルター
                
                // テスト実施日: 指定期間内にテストした単語を表示
                if (!empty($testFilter['last_test_start_date'])) {
                    $lastTestStartDate = $testFilter['last_test_start_date'] . ' 00:00:00';
                    $conditions[] = "ws.last_test_date >= :last_test_start_date";
                    $params[':last_test_start_date'] = $lastTestStartDate;
                }
                
                if (!empty($testFilter['last_test_end_date'])) {
                    $lastTestEndDate = $testFilter['last_test_end_date'] . ' 23:59:59';
                    $conditions[] = "ws.last_test_date <= :last_test_end_date";
                    $params[':last_test_end_date'] = $lastTestEndDate;
                }
            }

            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }

            $sql .= " GROUP BY w.word_id";
            
            // 並び替え
            if (!empty($testFilter['sort_by'])) {
                switch ($testFilter['sort_by']) {
                    case 'oldest':
                        $sql .= " ORDER BY w.created_at ASC";
                        break;
                    case 'accuracy_asc':
                        $sql .= " ORDER BY COALESCE(ws.accuracy_rate, 0) ASC, w.word_id DESC";
                        break;
                    case 'accuracy_desc':
                        $sql .= " ORDER BY COALESCE(ws.accuracy_rate, 0) DESC, w.word_id DESC";
                        break;
                    case 'newest':
                    default:
                        $sql .= " ORDER BY w.word_id DESC";
                }
            } else {
                $sql .= " ORDER BY w.word_id DESC";
            }

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
     * @param string|null $reading 読み仮名（オプション）
     * @return bool 成功した場合は true、それ以外は false
     */
    public function createWord(int $user_id, string $language_code, string $word, ?string $note, ?string $part_of_speech = null, array $translations, ?string $reading = null): bool
    {
        try {
            $this->pdo->beginTransaction();

            // words テーブルに挿入
            $sql = '
                INSERT INTO words (user_id, language_code, word, part_of_speech, note';
            
            // readingカラムが存在する場合は追加
            if ($reading !== null) {
                $sql .= ', reading';
            }
            
            $sql .= ', created_at, updated_at)
                VALUES (:user_id, :language_code, :word, :part_of_speech, :note';
            
            // readingパラメータが存在する場合は追加
            if ($reading !== null) {
                $sql .= ', :reading';
            }
            
            $sql .= ', NOW(), NOW())';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':language_code', $language_code);
            $stmt->bindValue(':word', $word);
            $stmt->bindValue(':part_of_speech', $part_of_speech);
            $stmt->bindValue(':note', $note);
            
            // readingパラメータが存在する場合はバインド
            if ($reading !== null) {
                $stmt->bindValue(':reading', $reading);
            }
            
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
     * 単語情報を更新する
     * @param int $word_id
     * @param int $user_id
     * @param string $language_code
     * @param string $word
     * @param string|null $note
     * @param string|null $part_of_speech
     * @param string|null $reading
     * @return bool
     */
    public function updateWord(int $word_id, int $user_id, string $language_code, string $word, ?string $note = null, ?string $part_of_speech = null, ?string $reading = null): bool
    {
        try {
            $sql = '
                UPDATE words
                SET user_id = :user_id,
                    language_code = :language_code,
                    word = :word,
                    note = :note';
            
            if ($part_of_speech !== null) {
                $sql .= ',
                    part_of_speech = :part_of_speech';
            }
            
            if ($reading !== null) {
                $sql .= ',
                    reading = :reading';
            }
            
            $sql .= ',
                    updated_at = NOW()
                WHERE word_id = :word_id';
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':word_id', $word_id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':language_code', $language_code);
            $stmt->bindValue(':word', $word);
            $stmt->bindValue(':note', $note);
            
            if ($part_of_speech !== null) {
                $stmt->bindValue(':part_of_speech', $part_of_speech);
            }
            
            if ($reading !== null) {
                $stmt->bindValue(':reading', $reading);
            }
            
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

