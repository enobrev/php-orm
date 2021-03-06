<?php
    namespace Enobrev;

    use Enobrev\ORM\Mock\Table\User;
    use PHPUnit\Framework\TestCase;

    class SelectGroupTest extends TestCase {
        public function testSelectGroup() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->group($oUser->user_id);
            $this->assertEquals("SELECT * FROM users GROUP BY users.user_id", (string) $oSQL);
        }

        public function testSelectGroupMultiple() {
            $oUser = new User();
            $oSQL = SQLBuilder::select($oUser)->group($oUser->user_id, $oUser->user_email);
            $this->assertEquals("SELECT * FROM users GROUP BY users.user_id, users.user_email", (string) $oSQL);
        }
    }