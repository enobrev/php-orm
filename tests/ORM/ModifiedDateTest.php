<?php
    namespace Enobrev;

    use PHPUnit_Framework_TestCase as TestCase;

    use Enobrev\ORM\Mock\Table;
    use Enobrev\ORM\Db;
    use Enobrev\Log;
    use PDO;

    class ModifiedDateTest extends TestCase {

        /** @var PDO */
        private $oPDO;

        /** @var  Table\User[] */
        private $aUsers;

        /** @var  Table\Address[] */
        private $aAddresses;

        public function setUp() {
            Log::setPurpose('ModifiedDateTest');

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

            Db::getInstance()->query('UPDATE addresses SET address_date_updated = "2016-02-01 01:02:03" WHERE address_id = ' . $this->aAddresses[0]->address_id->getValue());
            Db::getInstance()->query('UPDATE addresses SET address_date_updated = "2016-03-01 01:02:03" WHERE address_id = ' . $this->aAddresses[1]->address_id->getValue());

            Db::getInstance()->query('UPDATE users SET user_date_added = "2016-04-01 01:02:03" WHERE user_id = ' . $this->aUsers[0]->user_id->getValue());
            Db::getInstance()->query('UPDATE users SET user_date_added = "2016-05-01 01:02:03" WHERE user_id = ' . $this->aUsers[1]->user_id->getValue());
        }

        public function tearDown() {
            Db::getInstance()->query("DROP TABLE IF EXISTS users");
            Db::getInstance()->query("DROP TABLE IF EXISTS addresses");
        }

        public function testLastModified() {
            $this->assertEquals(new \DateTime('2016-02-01 01:02:03'), Table\Address::getById($this->aAddresses[0])->getLastModified());
            $this->assertEquals(new \DateTime('2016-03-01 01:02:03'), Table\Address::getById($this->aAddresses[1])->getLastModified());

            $this->assertEquals(new \DateTime('2016-04-01 01:02:03'), Table\User::getById($this->aUsers[0])->getLastModified());
            $this->assertEquals(new \DateTime('2016-05-01 01:02:03'), Table\User::getById($this->aUsers[1])->getLastModified());
        }
    }
