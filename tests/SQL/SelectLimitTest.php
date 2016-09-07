<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SQLLimitTest extends \PHPUnit_Framework_TestCase {
        public function testSelectLimitCount() {
            $oSQL = SQL::select(
                new User(),
                SQL::limit(10)
            );
            $this->assertEquals("SELECT * FROM users LIMIT 10", (string) $oSQL);
        }

        public function testSelectLimitOffsetCount() {
            $oSQL = SQL::select(
                new User(),
                SQL::limit(5, 10)
            );
            $this->assertEquals("SELECT * FROM users LIMIT 5, 10", (string) $oSQL);
        }
    }