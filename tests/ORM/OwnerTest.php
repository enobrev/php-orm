<?php
    namespace Enobrev;

    use PHPUnit_Framework_TestCase as TestCase;

    use Enobrev\ORM\Mock\Table;
    use Enobrev\ORM\Db;
    use Enobrev\Log;
    use PDO;

    class OwnerTest extends TestCase {

        /** @var PDO */
        private $oPDO;

        /** @var  Table\User[] */
        private $aUsers;

        /** @var  Table\Address[] */
        private $aAddresses;

        public function setUp() {
            Log::setPurpose('OwnerTest');

            $sDatabase = file_get_contents(__DIR__ . '/../Mock/sqlite.sql');
            $aDatabase = explode(';', $sDatabase);
            $aDatabase = array_filter($aDatabase);

            $this->oPDO = Db::defaultSQLiteMemory();
            $this->oPDO->exec("DROP TABLE IF EXISTS users");
            $this->oPDO->exec("DROP TABLE IF EXISTS addresses");
            Db::getInstance($this->oPDO);

            foreach($aDatabase as $sCreate) {
                Db::getInstance()->query($sCreate);
            }

            $this->aUsers[] = Table\User::createFromArray([
                'user_name'         => 'Test',
                'user_email'        => 'test@example.com',
                'user_happy'        => false
            ]);

            $this->aUsers[] = Table\User::createFromArray([
                'user_name'         => 'Test2',
                'user_email'        => 'test2@example.com',
                'user_happy'        => true
            ]);

            foreach($this->aUsers as &$oUser) {
                $oUser->insert();
            }

            $this->aAddresses[] = Table\Address::createFromArray([
                'user_id'               => $this->aUsers[0]->user_id,
                'address_line_1'        => '123 Main Street',
                'address_city'          => 'Chicago'
            ]);

            $this->aAddresses[] = Table\Address::createFromArray([
                'user_id'               => $this->aUsers[1]->user_id,
                'address_line_1'        => '234 Main Street',
                'address_city'          => 'Brooklyn'
            ]);

            foreach($this->aAddresses as &$oAddress) {
                $oAddress->insert();
            }
        }

        public function tearDown() {
            Db::getInstance()->query("DROP TABLE IF EXISTS users");
            Db::getInstance()->query("DROP TABLE IF EXISTS addresses");
        }

        public function testOwner() {
            $this->assertFalse($this->aAddresses[0]->hasOwner($this->aUsers[1]));
            $this->assertTrue($this->aAddresses[0]->hasOwner($this->aUsers[0]));
            $this->assertFalse($this->aAddresses[1]->hasOwner($this->aUsers[0]));
            $this->assertTrue($this->aAddresses[1]->hasOwner($this->aUsers[1]));

            $this->assertFalse($this->aUsers[0]->hasOwner($this->aUsers[1]));
            $this->assertTrue($this->aUsers[0]->hasOwner($this->aUsers[0]));
            $this->assertFalse($this->aUsers[1]->hasOwner($this->aUsers[0]));
            $this->assertTrue($this->aUsers[1]->hasOwner($this->aUsers[1]));
        }
    }
