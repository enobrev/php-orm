<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\User;

    class SQLOrderTest extends \PHPUnit_Framework_TestCase {
        public function testSelectOrderAsc() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::asc($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users ORDER BY users.user_id ASC", (string) $oSQL);
        }

        public function testSelectOrderDesc() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::desc($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users ORDER BY users.user_id DESC", (string) $oSQL);
        }

        public function testSelectOrderMultiple() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::asc($oUser->user_id),
                SQL::desc($oUser->user_email)
            );
            $this->assertEquals("SELECT * FROM users ORDER BY users.user_id ASC, users.user_email DESC", (string) $oSQL);
        }

        public function testSelectOrderByField() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::byfield($oUser->user_id, [1, 2, 3])
            );
            $this->assertEquals("SELECT * FROM users ORDER BY FIELD(users.user_id, 1, 2, 3)", (string) $oSQL);
        }
    }