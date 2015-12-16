<?php
    namespace Enobrev;

    use Enobrev\Mock\User;

    class SelectNotLikeTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntNotLikeValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::nlike($oUser->user_email, '%gmail%')
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_email NOT LIKE "%gmail%"', (string) $oSQL);
        }
    }