<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SelectEitherTest extends \PHPUnit_Framework_TestCase {
        public function testSelectEitherBookended() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::either(
                    SQL::eq($oUser->user_id, 1),
                    SQL::eq($oUser->user_email, 'test@example.com')
                )
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_id = 1 OR users.user_email = "test@example.com"', (string) $oSQL);
        }

        public function testSelectEitherNested() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::either(
                    SQL::either(
                        SQL::eq($oUser->user_id, 1),
                        SQL::eq($oUser->user_email, 'test@example.com')
                    )
                )
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_id = 1 OR users.user_email = "test@example.com"', (string) $oSQL);
        }

        public function testSelectEitherGrouped() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::either(
                    SQL::either(
                        SQL::eq($oUser->user_id, 1),
                        SQL::eq($oUser->user_email, 'test@example.com')
                    ),
                    SQL::either(
                        SQL::eq($oUser->user_id, 1),
                        SQL::eq($oUser->user_email, 'test@example.com')
                    )
                )
            );
            $this->assertEquals('SELECT * FROM users WHERE (users.user_id = 1 OR users.user_email = "test@example.com") OR (users.user_id = 1 OR users.user_email = "test@example.com")', (string) $oSQL);
        }

        public function testSelectAlsoEitherGrouped() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::also(
                    SQL::either(
                        SQL::eq($oUser->user_id, 1),
                        SQL::eq($oUser->user_email, 'test@example.com')
                    ),
                    SQL::either(
                        SQL::eq($oUser->user_id, 1),
                        SQL::eq($oUser->user_email, 'test@example.com')
                    )
                )
            );
            $this->assertEquals('SELECT * FROM users WHERE (users.user_id = 1 OR users.user_email = "test@example.com") AND (users.user_id = 1 OR users.user_email = "test@example.com")', (string) $oSQL);
        }
    }