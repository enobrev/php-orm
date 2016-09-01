<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\User;

    class SelectGteTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntGreaterEqualValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::gte($oUser->user_id, 1)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id >= 1", (string) $oSQL);
        }

        public function testSelectIntGreaterEqualSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::gte($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id >= 1", (string) $oSQL);
        }

        public function testSelectIntFieldGreaterEqualField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::gte($oUser->user_id, $oOtherUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id >= 1", (string) $oSQL);
        }
    }