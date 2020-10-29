<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';

    use PHPUnit\Framework\TestCase;

    use Enobrev\ORM\Table;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Db;
    use PDO;

    class ORMTableTest extends TestCase {

        /** @var PDO */
        private $oPDO;

        public function setUp():void {
            Log::setPurpose('ORMTableTest');
            Log::setService('ORMTableTest');

            $this->oPDO = Db::defaultSQLiteMemory();
            $this->oPDO->exec('DROP TABLE IF EXISTS extra_field');
            $oDb = Db::getInstance($this->oPDO);

            $oDb->query(<<<SQL
CREATE TABLE extra_field (
  id int(10)  DEFAULT NULL,
  field_1 int(10) DEFAULT NULL,
  field_2 int(10) DEFAULT NULL
);
SQL
            );

            $oDb->query(<<<SQL
INSERT INTO extra_field (id, field_1, field_2) VALUES (1, 2, 3);
SQL
            );
        }

        public function tearDown():void {
            Db::getInstance()->query('DROP TABLE IF EXISTS extra_field');
        }

        public function testExtraResultFields(): void {
            $oExtraField = ORMTableTestExtraField::getById(1);
            $this->assertEquals(1, $oExtraField->id->getValue());
            $this->assertEquals(2, $oExtraField->field_1->getValue());

            $this->assertObjectHasAttribute('field_2', $oExtraField->oResult);
            $this->assertEquals(3, $oExtraField->oResult->field_2);
        }
    }

    class ORMTableTestExtraField extends Table {
        protected string $sTitle = 'extra_field';

        /** @var Field\Integer */
        public $id;

        /** @var Field\Integer */
        public $field_1;

        protected function init(): void {
            $this->addFields(
                new Field\Integer('id'),
                new Field\Integer('field_1')
            );
        }

        public static function getById($iId): ?ORMTableTestExtraField {
            $oTable = new self;
            return self::getBy(
                $oTable->id->setValue($iId)
            );
        }

        public static function getTables() {
            // TODO: Implement getTables() method.
        }
    }

