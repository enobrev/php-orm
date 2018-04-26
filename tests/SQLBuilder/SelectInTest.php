<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;

    class SQLBuilderSelectInTest extends \PHPUnit\Framework\TestCase {
        public function testSelectIntInValue() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->in($oUser->user_id, [1, 2, 3, 4, 5]);
            $this->assertEquals("SELECT * FROM users WHERE users.user_id IN ( 1, 2, 3, 4, 5 )", (string) $oSQL);
        }
    }