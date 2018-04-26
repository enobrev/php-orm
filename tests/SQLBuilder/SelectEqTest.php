<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SQLBuilderSelectEqTest extends \PHPUnit\Framework\TestCase {
        public function testSelectIntEqualsValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->eq($oUser->user_id, 1);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id = 1", (string) $oSQL);
        }

        public function testSelectIntEqualsSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQLBuilder::select($oUser)->eq($oUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id = 1", (string) $oSQL);
        }

        public function testSelectIntFieldEqualsField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQLBuilder::select($oUser)->eq($oUser->user_id, $oOtherUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id = 1", (string) $oSQL);
        }

        public function testSelectStringEqualsValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->eq($oUser->user_email, 'test@example.com');
            $this->assertEquals('SELECT * FROM users WHERE users.user_email = "test@example.com"', (string) $oSQL);
        }
    }