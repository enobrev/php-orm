<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';

    use DateTime;
    use Enobrev\ORM\Mock\Table\Address;
    use Enobrev\ORM\Mock\Table\User;
    use PHPUnit\Framework\TestCase;

    class SelectTest extends TestCase {
        public function testSelectStar() {
            $this->assertEquals("SELECT * FROM users", (string) SQLBuilder::select(new User()));
        }

        public function testSelectStaticFields() {
            $oSQL = SQLBuilder::select(
                new User(),
                User::Field('user_id'),
                User::Field('user_name'),
                User::Field('user_email')
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email FROM users", (string) $oSQL);
        }

        public function testSelectFields() {
            $oUser = new User;
            $oSQL = SQLBuilder::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email FROM users", (string) $oSQL);
        }

        public function testSelectFull() {
            $oUser = new User;
            $oSQL = SQLBuilder::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email
            );
            $this->assertEquals("SELECT users.user_id, users.user_name, users.user_email FROM users", (string) $oSQL);
        }

        public function testBigOne() {
            $oUser    = new User();
            $oSQL     = SQLBuilder::select(
                $oUser,
                $oUser->user_id,
                $oUser->user_name,
                $oUser->user_email,
                Address::Field('address_city', 'billing'),
                Address::Field('address_city', 'shipping')
            )->join($oUser->user_id, Address::Field('user_id', 'billing'))
             ->join($oUser->user_id, Address::Field('user_id', 'shipping'))
             ->either(
                SQL::also(
                    SQL::eq($oUser->user_id, 1),
                    SQL::eq($oUser->user_email, 'test@example.com')
                ),
                SQL::between($oUser->user_date_added, new DateTime('2015-01-01'), new DateTime('2015-06-01'))
             )->asc($oUser->user_name)
             ->desc($oUser->user_email)
             ->group($oUser->user_id)
             ->limit(5, 10);

            $this->assertEquals('SELECT users.user_id, ANY_VALUE(users.user_name) AS user_name,'
                                . ' ANY_VALUE(users.user_email) AS user_email,'
                                . ' ANY_VALUE(billing.address_city) AS billing_address_city,'
                                . ' ANY_VALUE(shipping.address_city) AS shipping_address_city'
                                . ' FROM users'
                                . ' LEFT OUTER JOIN addresses AS billing ON users.user_id = billing.user_id'
                                . ' LEFT OUTER JOIN addresses AS shipping ON users.user_id = shipping.user_id'
                                . ' WHERE (users.user_id = 1 AND users.user_email = "test@example.com")'
                                . ' OR users.user_date_added BETWEEN "2015-01-01 00:00:00" AND "2015-06-01 00:00:00"'
                                . ' GROUP BY users.user_id'
                                . ' ORDER BY users.user_name ASC, users.user_email DESC'
                                . ' LIMIT 5, 10', (string) $oSQL);
        }
    }



