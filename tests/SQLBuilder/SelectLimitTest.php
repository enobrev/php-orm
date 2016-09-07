<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\User;

    class SQLBuilderSQLLimitTest extends \PHPUnit_Framework_TestCase {
        public function testSelectLimitCount() {
            $oSQL = SQLBuilder::select(new User())->limit(10);
            $this->assertEquals("SELECT * FROM users LIMIT 10", (string) $oSQL);
        }

        public function testSelectLimitOffsetCount() {
            $oSQL = SQLBuilder::select(new User())->limit(5, 10);
            $this->assertEquals("SELECT * FROM users LIMIT 5, 10", (string) $oSQL);
        }
    }