<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SelectGtTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntGreaterThanValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_id, 1)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectIntGreaterThanSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectIntFieldGreaterThanField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_id, $oOtherUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }
    }