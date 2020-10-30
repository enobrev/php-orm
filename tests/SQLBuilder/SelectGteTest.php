<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;
    use PHPUnit\Framework\TestCase;

    class SelectGteTest extends TestCase {
        public function testSelectIntGreaterEqualValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->gte($oUser->user_id, 1);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id >= 1", (string) $oSQL);
        }

        public function testSelectIntGreaterEqualSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQLBuilder::select($oUser)->gte($oUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id >= 1", (string) $oSQL);
        }

        public function testSelectIntFieldGreaterEqualField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQLBuilder::select($oUser)->gte($oUser->user_id, $oOtherUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id >= 1", (string) $oSQL);
        }
    }