<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;
    use PHPUnit\Framework\TestCase;

    class SelectNotNullTest extends TestCase {
        public function testSelectIntNotNull() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->nnul($oUser->user_id);
            $this->assertEquals('SELECT * FROM users WHERE users.user_id IS NOT NULL', (string) $oSQL);
        }
    }