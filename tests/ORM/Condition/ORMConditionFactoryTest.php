<?php
    namespace Enobrev;

    require __DIR__ . '/../../../vendor/autoload.php';

    use Enobrev\ORM\ConditionFactory;
    use Enobrev\ORM\DateFunction;
    use Enobrev\ORM\Db;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use PHPUnit\Framework\TestCase;
 
    class ORMConditionFactoryTest extends TestCase {
        public function setUp():void {
            Db::getInstance(Db::defaultSQLiteMemory());
        }
        
        public function testEqual(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(1);
            $oCondition = ConditionFactory::eq($oUsers->user_id);
            $this->assertEquals('users.user_id = 1', $oCondition->toSQL());
        }

        public function testInIntHelper(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oCondition = ConditionFactory::in($oUsers->user_id, array(1, 2, 3));
            $this->assertEquals('users.user_id IN ( 1, 2, 3 )', $oCondition->toSQL());
        }

        public function testNotInIntHelper(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oCondition = ConditionFactory::nin($oUsers->user_id, array(1, 2, 3));
            $this->assertEquals('users.user_id NOT IN ( 1, 2, 3 )', $oCondition->toSQL());
        }

        public function testInStringHelper(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oCondition = ConditionFactory::in($oUsers->user_name_first, ['Bill', 'Bob', 'Biff']);
            $this->assertEquals('users.user_name_first IN ( "Bill", "Bob", "Biff" )', $oCondition->toSQL());
        }

        public function testInEnumHelper(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oCondition = ConditionFactory::in($oUsers->user_gender, array('Male', 'Female'));
            $this->assertEquals('users.user_gender IN ( "Male", "Female" )', $oCondition->toSQL());
        }

        public function testEqualHelper(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::eq($oUsers->user_id);
            $this->assertEquals('users.user_id = 10', $oCondition->toSQL());
        }

        public function testNotEqual(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::neq($oUsers->user_id);
            $this->assertEquals('users.user_id <> 10', $oCondition->toSQL());
        }

        public function testNotEqualHelper(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::neq($oUsers->user_id);
            $this->assertEquals('users.user_id <> 10', $oCondition->toSQL());
        }

        public function testLike(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_name_first->setValue('M%');
            $oCondition = ConditionFactory::like($oUsers->user_name_first);
            $this->assertEquals('users.user_name_first LIKE "M%"', $oCondition->toSQL());
        }

        public function testLikeValue(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oCondition = ConditionFactory::like($oUsers->user_name_first, 'M%');
            $this->assertEquals('users.user_name_first LIKE "M%"', $oCondition->toSQL());
        }

        public function testBetweenOneValue(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(1);
            $oCondition = ConditionFactory::between($oUsers->user_id, 10);
            $this->assertEquals('users.user_id BETWEEN 1 AND 10', $oCondition->toSQL());
        }

        public function testBetweenTwoValues(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oCondition = ConditionFactory::between($oUsers->user_id, 10, 20);
            $this->assertEquals('users.user_id BETWEEN 10 AND 20', $oCondition->toSQL());
        }

        public function testBetweenOneField(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(1);
            $oOtherId = clone $oUsers->user_id;
            $oOtherId->setValue(10);
            $oCondition = ConditionFactory::between($oUsers->user_id, $oOtherId);
            $this->assertEquals('users.user_id BETWEEN 1 AND 10', $oCondition->toSQL());
        }

        public function testBetweenOneFieldOneValue(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(1);

            $oOtherId = clone $oUsers->user_id;
            $oOtherId->setValue(10);

            $oCondition = ConditionFactory::between($oUsers->user_id, $oOtherId, 20);
            $this->assertEquals('users.user_id BETWEEN 10 AND 20', $oCondition->toSQL());
        }

        public function testBetweenTwoFields(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            
            $oFirstId = clone $oUsers->user_id;
            $oFirstId->setValue(10);

            $oSecondId = clone $oUsers->user_id;
            $oSecondId->setValue(20);
            
            $oCondition = ConditionFactory::between($oUsers->user_id, $oFirstId, $oSecondId);
            $this->assertEquals('users.user_id BETWEEN 10 AND 20', $oCondition->toSQL());
        }

        public function testGreaterThanNow(): void {
            $oUsers = new ORMConditionFactoryTestUser();

            $oUsers->user_date_added->setValue(DateFunction::FUNC_NOW);
            $oCondition = ConditionFactory::gt($oUsers->user_date_added);
            $this->assertEquals('users.user_date_added > NOW()', $oCondition->toSQL());
        }

        public function testLessThanNow(): void {
            $oUsers = new ORMConditionFactoryTestUser();

            $oUsers->user_date_added->setValue(DateFunction::FUNC_NOW);
            $oCondition = ConditionFactory::lt($oUsers->user_date_added);
            $this->assertEquals('users.user_date_added < NOW()', $oCondition->toSQL());
        }

        public function testBetweenDateAndNow(): void {
            $oUsers = new ORMConditionFactoryTestUser();

            $oUsers->user_date_added->setValue('2011-11-05 12:02:00');
            $oCondition = ConditionFactory::between($oUsers->user_date_added, DateFunction::FUNC_NOW);
            $this->assertEquals('users.user_date_added BETWEEN "2011-11-05 12:02:00" AND NOW()', $oCondition->toSQL());
        }

        public function testBetweenDateValue(): void {
            $oUsers = new ORMConditionFactoryTestUser();

            $oUsers->user_date_added->setValue('2011-11-05 12:01:00');
            $oCondition = ConditionFactory::between($oUsers->user_date_added, '2011-11-05 12:02:00');
            $this->assertEquals('users.user_date_added BETWEEN "2011-11-05 12:01:00" AND "2011-11-05 12:02:00"', $oCondition->toSQL());
        }

        public function testFieldOrder(): void {
            $oUsers = new ORMConditionFactoryTestUser();

            $oFirstId = clone $oUsers->user_id;
            $oFirstId->setValue(10);

            $oSecondId = clone $oUsers->user_id;
            $oSecondId->setValue(20);

            $oCondition = ConditionFactory::between($oUsers->user_id, $oSecondId, $oFirstId);
            $this->assertEquals('users.user_id BETWEEN 20 AND 10', $oCondition->toSQL());
        }

        public function testLessThan(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::lt($oUsers->user_id);
            $this->assertEquals('users.user_id < 10', $oCondition->toSQL());
        }

        public function testLessThanOrEqual(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::lte($oUsers->user_id);
            $this->assertEquals('users.user_id <= 10', $oCondition->toSQL());
        }

        public function testGreaterThan(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::gt($oUsers->user_id);
            $this->assertEquals('users.user_id > 10', $oCondition->toSQL());
        }

        public function testGreaterThanOrEqual(): void {
            $oUsers = new ORMConditionFactoryTestUser();
            $oUsers->user_id->setValue(10);
            $oCondition = ConditionFactory::gte($oUsers->user_id);
            $this->assertEquals('users.user_id >= 10', $oCondition->toSQL());
        }
    }

    class ORMConditionFactoryTestUser extends Table {
        protected string $sTitle = 'users';

        public Field\Integer $user_id;
        public Field\Text $user_name_first;
        public Field\Enum $user_gender;
        public Field\DateTime $user_date_added;

        public static function getTables() {
            // TODO: Implement getTables() method.
        }

        protected function init(): void {
            $this->addFields(
                new Field\Integer('user_id'),
                new Field\Text('user_name_first'),
                new Field\Enum('user_gender', ['Male', 'Female']),
                new Field\DateTime('user_date_added')
            );
        }
    }