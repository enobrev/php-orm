<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\User;

    class SelectNotInTest extends \PHPUnit_Framework_TestCase {
        public function testSelectNoIntNotInValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::nin($oUser->user_id, array(1, 2, 3, 4, 5))
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id NOT IN ( 1, 2, 3, 4, 5 )", (string) $oSQL);
        }
    }