<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\User;

    class SQLBuilderSelectLikeTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntLikeValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->like($oUser->user_email, '%gmail%');
            $this->assertEquals('SELECT * FROM users WHERE users.user_email LIKE "%gmail%"', (string) $oSQL);
        }
    }