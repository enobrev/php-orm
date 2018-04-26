<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SQLBuilderSelectGtTest extends \PHPUnit\Framework\TestCase {
        public function testSelectIntGreaterThanValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->gt($oUser->user_id, 1);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectIntGreaterThanSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQLBuilder::select($oUser)->gt($oUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectIntFieldGreaterThanField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQLBuilder::select($oUser)->gt($oUser->user_id, $oOtherUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }
    }