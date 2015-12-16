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

        public function testSelectJoinAlias() {
            $oUser    = new User();
            $oAddress = new Address();
            $oSQL     = SQL::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email,
                Address::Field('address_city', 'billing'),
                Address::Field('address_city', 'shipping'),
                SQL::join($oUser->user_id, Address::Field('user_id', 'billing')),
                SQL::join($oUser->user_id, Address::Field('user_id', 'shipping'))
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email, billing.address_city AS billing_address_city, shipping.address_city AS shipping_address_city FROM users LEFT OUTER JOIN addresses AS billing ON users.user_id = billing.user_id LEFT OUTER JOIN addresses AS shipping ON users.user_id = shipping.user_id", (string) $oSQL);
        }
    }