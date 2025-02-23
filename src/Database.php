<?php
namespace TangoTraining;

use PDO;
use PDOException;

/**
 * Databaseクラス
 * PDOを利用してMySQLへの接続を管理する。
 * 初心者でも分かるよう、細かめにコメントを入れています。
 */
class Database
{
    /** @var PDO PDOインスタンス */
    private $pdo;

    /**
     * コンストラクタ
     * config/database.phpの情報を読み込んで接続を試みる
     */
    public function __construct()
    {
        // configファイルからDB情報を読み込む
        $config = require __DIR__ . '/../config/database.php';

        // DSNを組み立てる
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['dbname'],
            $config['charset']
        );

        try {
            // PDOインスタンス作成
            $this->pdo = new PDO($dsn, $config['username'], $config['password']);
            // エラーモードを例外に設定
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // 例外が発生したらエラーメッセージを表示して終了
            exit('データベース接続エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
    }

    /**
     * PDOインスタンスを取得するゲッター
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * プリペアドステートメントを生成するメソッド
     * @param string $sql SQL文
     * @return \PDOStatement|false
     */
    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    /**
     * SELECTなど結果を返すクエリを実行するメソッド
     * @param string $sql
     * @param array $params
     * @return \PDOStatement|false
     */
    public function query($sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            echo "クエリエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            return false;
        }
    }

    /**
     * INSERT/UPDATE/DELETEなど行数が変わるクエリ用
     * 実行後に影響のあった行数を返す
     * @param string $sql
     * @param array $params
     * @return int|false
     */
    public function execute($sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount(); // 更新行数を返す
        } catch (PDOException $e) {
            echo "実行エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            return false;
        }
    }
}
