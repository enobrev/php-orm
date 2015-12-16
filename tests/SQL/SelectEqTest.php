<?php
    namespace Enobrev;

    use Enobrev\Mock\User;

    class SelectEqTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntEqualsValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::eq($oUser->user_id, 1)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id = 1", (string) $oSQL);
        }

        public function testSelectIntEqualsSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::eq($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id = 1", (string) $oSQL);
        }

        public function testSelectIntFieldEqualsField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::eq($oUser->user_id, $oOtherUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id = 1", (string) $oSQL);
        }

        public function testSelectStringEqualsValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::eq($oUser->user_email, 'test@example.com')
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_email = "test@example.com"', (string) $oSQL);
        }
    }