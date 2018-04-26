<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SelectNullTest extends \PHPUnit\Framework\TestCase {
        public function testSelectIntNull() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::nul($oUser->user_id)
            );
            $this->assertEquals('SELECT * FROM users WHERE users.user_id IS NULL', (string) $oSQL);
        }
    }