<?php
    namespace Enobrev;

    use Enobrev\Mock\User;

    class SelectLikeTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntLikeValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::like($oUser->user_email, '%gmail%')
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_email LIKE "%gmail%"', (string) $oSQL);
        }
    }