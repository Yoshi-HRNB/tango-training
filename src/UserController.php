<?php
namespace TangoTraining;

use PDO;
use PDOException;

/**
 * UserController
 * --------------
 * 管理者がユーザーを操作できるようにまとめたクラス。
 * ユーザー一覧、更新、削除など。
 */
class UserController
{
    /** @var \PDO PDOインスタンス */
    private $pdo;

    /**
     * コンストラクタ
     */
    public function __construct(Database $db)
    {
        $this->pdo = $db->getConnection();
    }

    /**
     * ユーザー一覧を取得
     * @return array
     */
    public function getAllUsers(): array
    {
        $sql = "SELECT id, email, name, role, created_at
                FROM users
                ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 指定IDのユーザーを1件取得
     * @param int $id
     * @return array|null
     */
    public function getUserById(int $id): ?array
    {
        $sql = "SELECT id, email, name, role, created_at
                FROM users
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * ユーザー情報を更新
     * @param int    $id   更新対象のユーザーID
     * @param string $name
     * @param string $role
     * @return bool
     */
    public function updateUser(int $id, string $name, string $role): bool
    {
        $sql = "UPDATE users
                SET name = :name,
                    role = :role,
                    updated_at = NOW()
                WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':role', $role);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ユーザー削除
     * @param int $id
     * @return bool
     */
    public function deleteUser(int $id): bool
    {
        $sql = "DELETE FROM users WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
