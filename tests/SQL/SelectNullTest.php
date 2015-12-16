<?php
    namespace Enobrev;

    use Enobrev\Mock\User;

    class SelectNullTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntNull() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::nul($oUser->user_id)
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_id IS NULL', (string) $oSQL);
        }
    }