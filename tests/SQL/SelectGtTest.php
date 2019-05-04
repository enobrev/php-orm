<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';

    use Enobrev\ORM\Db;
    use Enobrev\ORM\Mock\Table\User;

    class SelectGtTest extends \PHPUnit\Framework\TestCase {
        public function setUp():void {
            Db::getInstance(Db::defaultSQLiteMemory());
        }

        public function testSelectIntGreaterThanValue() {
            $oUser = new User();
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_id, 1)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectIntGreaterThanSelf() {
            $oUser = new User;
            $oUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectIntFieldGreaterThanField() {
            $oUser = new User;
            $oOtherUser = new User;
            $oOtherUser->user_id->setValue(1);
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_id, $oOtherUser->user_id)
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_id > 1", (string) $oSQL);
        }

        public function testSelectDateFieldGreaterThanNow() {
            $oUser = new User;
            $oSQL = SQL::select(
                $oUser,
                SQL::gt($oUser->user_date_added, SQL::NOW())
            );
            $this->assertEquals("SELECT * FROM users WHERE users.user_date_added > NOW()", (string)$oSQL);
        }
    }