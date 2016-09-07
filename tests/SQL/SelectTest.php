<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\Address;
    use Enobrev\ORM\Mock\Table\User;

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

        public function testSelectFull() {
            $oUser = new User;
            $oSQL = SQL::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email FROM users", (string) $oSQL);
        }

        public function testBigOne() {
            $oUser    = new User();
            $oSQL     = SQL::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email,
                Address::Field('address_city', 'billing'),
                Address::Field('address_city', 'shipping'),
                SQL::join($oUser->user_id, Address::Field('user_id', 'billing')),
                SQL::join($oUser->user_id, Address::Field('user_id', 'shipping')),
                SQL::either(
                    SQL::also(
                        SQL::eq($oUser->user_id, 1),
                        SQL::eq($oUser->user_email, 'test@example.com')
                    ),
                    SQL::between($oUser->user_date_added, new \DateTime('2015-01-01'), new \DateTime('2015-06-01'))
                ),
                SQL::asc($oUser->user_name),
                SQL::desc($oUser->user_email),
                SQL::group($oUser->user_id),
                SQL::limit(5)
            );
            $this->assertEquals('SELECT users.user_id, users.user_name, users.user_email, billing.address_city AS billing_address_city, shipping.address_city AS shipping_address_city'
                                . ' FROM users'
                                . ' LEFT OUTER JOIN addresses AS billing ON users.user_id = billing.user_id'
                                . ' LEFT OUTER JOIN addresses AS shipping ON users.user_id = shipping.user_id'
                                . ' WHERE (users.user_id = 1 AND users.user_email = "test@example.com")'
                                . ' OR users.user_date_added BETWEEN "2015-01-01 00:00:00" AND "2015-06-01 00:00:00"'
                                . ' GROUP BY users.user_id'
                                . ' ORDER BY users.user_name ASC, users.user_email DESC'
                                . ' LIMIT 5', (string) $oSQL);
        }
    }



