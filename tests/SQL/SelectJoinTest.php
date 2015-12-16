<?php
    namespace Enobrev;

    use Enobrev\Mock\Address;
    use Enobrev\Mock\User;

    class SelectJoinTest extends \PHPUnit_Framework_TestCase {
        public function testSelectJoin() {
            $oUser    = new User();
            $oAddress = new Address();
            $oSQL     = SQL::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email,
                $oAddress->address_city,
                SQL::join($oUser->user_id, $oAddress->user_id)
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email, addresses.address_city FROM users LEFT OUTER JOIN addresses ON users.user_id = addresses.user_id", (string) $oSQL);
        }
    }