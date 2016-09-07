<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SQLBuilderSelectBetweenTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntBetweenValues() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->between($oUser->user_id, 1, 5);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id BETWEEN 1 AND 5", (string) $oSQL);
        }

        public function testSelectIntBetweenFields() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(5);
            $oSQL = SQLBuilder::select($oUser)->between($oUser->user_id, $oOtherUser->user_id);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id BETWEEN 1 AND 5", (string) $oSQL);
        }
    }