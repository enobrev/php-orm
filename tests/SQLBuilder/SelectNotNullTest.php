<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SelectNotNullTest extends \PHPUnit\Framework\TestCase {
        public function testSelectIntNotNull() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->nnul($oUser->user_id);
            $this->assertEquals('SELECT * FROM users WHERE users.user_id IS NOT NULL', (string) $oSQL);
        }
    }