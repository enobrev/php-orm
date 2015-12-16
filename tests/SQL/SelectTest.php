<?php
    namespace Enobrev;

    use Enobrev\Mock\User;

    class SQLSelectTest extends \PHPUnit_Framework_TestCase {
        public function testSelectStar() {
            $this->assertEquals("SELECT * FROM users", (string) SQL::select(new User()));
        }

        public function testSelectStaticFields() {
            $oSQL = SQL::select(
                new User(),
                User::Field('user_id'),
                User::Field('user_name'),
                User::Field('user_email')
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email FROM users", (string) $oSQL);
        }

        public function testSelectFields() {
            $oUser = new User;
            $oSQL = SQL::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email FROM users", (string) $oSQL);
        }
    }