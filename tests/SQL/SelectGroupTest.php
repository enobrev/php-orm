<?php
    namespace Enobrev;

    use Enobrev\Mock\User;

    class SQLGroupTest extends \PHPUnit_Framework_TestCase {
        public function testSelectGroup() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::group($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users GROUP BY users.user_id", (string) $oSQL);
        }

        public function testSelectGroupMultiple() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::group($oUser->user_id, $oUser->user_email)
            );
            $this->assertEquals("SELECT * FROM users GROUP BY users.user_id, users.user_email", (string) $oSQL);
        }
    }