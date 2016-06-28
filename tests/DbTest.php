<?php

namespace Starlit\Db;

class DbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    private $db;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $mockPdo;

    protected function setUp()
    {
        $this->mockPdo = $this->getMockBuilder('\PDO')
            ->disableOriginalConstructor()->getMock();
        $this->db = new Db($this->mockPdo);
    }

    public function testDisconnectClearsPdo()
    {
        $this->db->disconnect();
        $this->assertEmpty($this->db->getPdo());
    }

    public function testIsConnectedReturnsTrue()
    {
        $this->assertTrue($this->db->isConnected());
    }

    public function testGetPdoIsPdoInstance()
    {
        $this->assertInstanceOf('\PDO', $this->db->getPdo());
    }

    public function testExecCallsPdoWithSqlAndParams()
    {
        $sql = 'UPDATE `test_table` SET `test_column` = ? AND `other_column` = ?';
        $sqlParameters = [1, false];
        $rowCount = 5;

        $mockPdoStatement = $this->getMockBuilder('\PDOStatement')->getMock();

        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($mockPdoStatement);

        $mockPdoStatement->expects($this->once())
            ->method('execute')
            ->with([1, 0]);

        $mockPdoStatement->expects($this->once())
            ->method('rowCount')
            ->willReturn($rowCount);

        $result = $this->db->exec($sql, $sqlParameters);
        $this->assertEquals($rowCount, $result);
    }

    public function testExecFailThrowsQueryException()
    {
        $this->mockPdo
            ->method('prepare')
            ->willThrowException(new \PDOException());

        $this->expectException('\Starlit\Db\Exception\QueryException');
        $this->db->exec('NO SQL');
    }

    public function testFetchRowCallsPdoWithSqlAndParams()
    {
        $sql = 'SELECT * FROM `test_table` WHERE id = ? LIMIT 1';
        $sqlParameters = [1];
        $tableData = ['id' => 5];

        $mockPdoStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($mockPdoStatement);

        $mockPdoStatement->expects($this->once())
            ->method('execute')
            ->with($sqlParameters);

        $mockPdoStatement->expects($this->once())
            ->method('fetch')
            ->willReturn($tableData);

         $this->assertEquals($tableData, $this->db->fetchRow($sql, $sqlParameters));
    }

    public function testFetchAllCallsPdoWithSqlAndParams()
    {
        $sql = 'SELECT * FROM `test_table` WHERE id < ?';
        $sqlParameters = [3];
        $tableData = [['id' => 1], ['id' => 2]];

        $mockPdoStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($mockPdoStatement);

        $mockPdoStatement->expects($this->once())
            ->method('execute')
            ->with($sqlParameters);

        $mockPdoStatement->expects($this->once())
            ->method('fetchAll')
            ->willReturn($tableData);

        $this->assertEquals($tableData, $this->db->fetchAll($sql, $sqlParameters));
    }

    public function testFetchOneCallsPdoWithSqlAndParams()
    {
        $sql = 'SELECT COUNT(*) FROM `test_table` WHERE id < ?';
        $sqlParameters = [10];
        $result = 5;

        $mockPdoStatement = $this->getMockBuilder('\PDOStatement')->getMock();
        $this->mockPdo->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($mockPdoStatement);

        $mockPdoStatement->expects($this->once())
            ->method('execute')
            ->with($sqlParameters);

        $mockPdoStatement->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($result);

        $this->assertEquals($result, $this->db->fetchOne($sql, $sqlParameters));
    }

    public function testQuoteCallPdo()
    {
        $this->mockPdo->expects($this->once())
            ->method('quote');

        $this->db->quote(1);
    }

    public function testGetLastInsertIdCallsPdo()
    {
        $this->mockPdo->expects($this->once())
            ->method('lastInsertId');

        $this->db->getLastInsertId();
    }

    public function testBeginTransactionCallsPdoAndReturnsTrue()
    {
        $this->mockPdo->expects($this->once())
            ->method('beginTransaction');

        $this->assertTrue($this->db->beginTransaction());
    }

    public function testBeginTransactionReturnsFalse()
    {
        $this->db->beginTransaction();

        $this->assertFalse($this->db->beginTransaction(true));
    }

    public function testCommitCallsPdo()
    {
        $this->mockPdo->expects($this->once())
            ->method('commit');

        $this->db->commit();
    }

    public function testRollBackCallsPdo()
    {
        $this->mockPdo->expects($this->once())
            ->method('rollBack');

        $this->db->rollBack();
    }

    public function testHasActiveTransactionReturnsTrue()
    {
        $this->db->beginTransaction();

        $this->assertTrue($this->db->hasActiveTransaction());
    }

    public function testHasActiveTransactionReturnsFalse()
    {
        $this->assertFalse($this->db->hasActiveTransaction());

        $this->db->beginTransaction();
        $this->db->commit();
        $this->assertFalse($this->db->hasActiveTransaction());

        $this->db->beginTransaction();
        $this->db->rollBack();
        $this->assertFalse($this->db->hasActiveTransaction());
    }

    public function testInsertCallsExecWithSqlAndParams()
    {
        $table = 'test_table';
        $insertData = ['id' => 1, 'name' => 'one'];
        $expectedSql = "INSERT INTO `" . $table . "` (`id`, `name`)\nVALUES (?, ?)";
        $expectedAffectedRows = 1;

        $mockDb = $this->getMockBuilder('\Starlit\Db\Db')
            ->setMethods(['exec'])->setConstructorArgs([])->disableOriginalConstructor()->getMock();
        $mockDb->expects($this->once())
            ->method('exec')
            ->with($expectedSql, array_values($insertData))
            ->willReturn($expectedAffectedRows);


        $this->assertEquals($expectedAffectedRows, $mockDb->insert($table, $insertData));
    }

    public function testUpdateCallsExecWithSqlAndParams()
    {
        $table = 'test_table';
        $updateData = ['name' => 'ONE'];
        $whereSql = '`name` = ?';
        $whereParameters = [1];

        $expectedSql = "UPDATE `" . $table . "`\nSET `name` = ?\nWHERE `name` = ?";
        $expectedAffectedRows = 1;

        $mockDb = $this->getMockBuilder('\Starlit\Db\Db')
            ->setMethods(['exec'])->setConstructorArgs([])->disableOriginalConstructor()->getMock();
        $mockDb->expects($this->once())
            ->method('exec')
            ->with($expectedSql, array_merge(array_values($updateData), $whereParameters))
            ->willReturn($expectedAffectedRows);


        $this->assertEquals(
            $expectedAffectedRows,
            $mockDb->update($table, $updateData, $whereSql, $whereParameters)
        );

    }
}
