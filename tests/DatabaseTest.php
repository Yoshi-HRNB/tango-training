<?php
namespace TangoTraining\Tests;

use PHPUnit\Framework\TestCase;
use TangoTraining\Database;

class DatabaseTest extends TestCase
{
    public function testDatabaseConnection()
    {
        $db = new Database();
        $pdo = $db->getConnection();
        $this->assertNotNull($pdo, 'PDOインスタンスが取得できない');
    }
}
