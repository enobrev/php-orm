<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SQLBuilderSelectNotLikeTest extends \PHPUnit_Framework_TestCase {
        public function testSelectIntNotLikeValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->nlike($oUser->user_email, '%gmail%');
            $this->assertEquals('SELECT * FROM users WHERE users.user_email NOT LIKE "%gmail%"', (string) $oSQL);
        }
    }