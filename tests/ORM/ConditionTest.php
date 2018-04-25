<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';

    use Enobrev\ORM\Condition;

    use Enobrev\ORM\Db;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use PHPUnit_Framework_TestCase as TestCase;
 
    class MySQLConditionTest extends TestCase {
        public function setUp() {
            Db::getInstance(Db::defaultSQLiteMemory());
        }
        
        public function testEqual() {
            $oUsers = new Table('users');
            $oUsers->addFields(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(1);
            $oCondition = Condition::eq($oUsers->user_id);
            $this->assertEquals("users.user_id = '1'", $oCondition->toSQL());
        }

        public function testInIntHelper() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oCondition = Condition::in($oUsers->user_id, array(1, 2, 3));
            $this->assertEquals("users.user_id IN ( '1', '2', '3' )", $oCondition->toSQL());
        }

        public function testNotInIntHelper() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oCondition = Condition::nin($oUsers->user_id, array(1, 2, 3));
            $this->assertEquals("users.user_id NOT IN ( '1', '2', '3' )", $oCondition->toSQL());
        }

        public function testInStringHelper() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Text('user_name_first')
            );
            $oCondition = Condition::in($oUsers->user_name_first, ['Bill', 'Bob', 'Biff']);
            $this->assertEquals("users.user_name_first IN ( 'Bill', 'Bob', 'Biff' )", $oCondition->toSQL());
        }

        public function testInEnumHelper() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Enum('user_gender', ['Male', 'Female'])
            );
            $oCondition = Condition::in($oUsers->user_gender, array('Male', 'Female'));
            $this->assertEquals("users.user_gender IN ( 'Male', 'Female' )", $oCondition->toSQL());
        }

        public function testEqualHelper() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::eq($oUsers->user_id);
            $this->assertEquals("users.user_id = '10'", $oCondition->toSQL());
        }

        public function testNotEqual() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::neq($oUsers->user_id);
            $this->assertEquals("users.user_id <> '10'", $oCondition->toSQL());
        }

        public function testNotEqualHelper() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::neq($oUsers->user_id);
            $this->assertEquals("users.user_id <> '10'", $oCondition->toSQL());
        }

        public function testLike() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Text('user_name_first')
            );
            $oUsers->user_name_first->setValue('M%');
            $oCondition = Condition::like($oUsers->user_name_first);
            $this->assertEquals("users.user_name_first LIKE 'M%'", $oCondition->toSQL());
        }

        public function testLikeValue() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Text('user_name_first')
            );
            $oCondition = Condition::like($oUsers->user_name_first, 'M%');
            $this->assertEquals("users.user_name_first LIKE 'M%'", $oCondition->toSQL());
        }

        public function testBetweenOneValue() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(1);
            $oCondition = Condition::between($oUsers->user_id, 10);
            $this->assertEquals("users.user_id BETWEEN '1' AND '10'", $oCondition->toSQL());
        }

        public function testBetweenTwoValues() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oCondition = Condition::between($oUsers->user_id, 10, 20);
            $this->assertEquals("users.user_id BETWEEN '10' AND '20'", $oCondition->toSQL());
        }

        public function testBetweenOneField() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(1);
            $oOtherId = clone $oUsers->user_id;
            $oOtherId->setValue(10);
            $oCondition = Condition::between($oUsers->user_id, $oOtherId);
            $this->assertEquals("users.user_id BETWEEN '1' AND '10'", $oCondition->toSQL());
        }

        public function testBetweenOneFieldOneValue() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(1);

            $oOtherId = clone $oUsers->user_id;
            $oOtherId->setValue(10);

            $oCondition = Condition::between($oUsers->user_id, $oOtherId, 20);
            $this->assertEquals("users.user_id BETWEEN '10' AND '20'", $oCondition->toSQL());
        }

        public function testBetweenTwoFields() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            
            $oFirstId = clone $oUsers->user_id;
            $oFirstId->setValue(10);

            $oSecondId = clone $oUsers->user_id;
            $oSecondId->setValue(20);
            
            $oCondition = Condition::between($oUsers->user_id, $oFirstId, $oSecondId);
            $this->assertEquals("users.user_id BETWEEN '10' AND '20'", $oCondition->toSQL());
        }

        public function testGreaterThanNow() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\DateTime('user_date_added')
            );

            $oUsers->user_date_added->setValue(Field\DateTime::MYSQL_NOW);
            $oCondition = Condition::gt($oUsers->user_date_added);
            $this->assertEquals('users.user_date_added > NOW()', $oCondition->toSQL());
        }

        public function testLessThanNow() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\DateTime('user_date_added')
            );

            $oUsers->user_date_added->setValue(Field\DateTime::MYSQL_NOW);
            $oCondition = Condition::lt($oUsers->user_date_added);
            $this->assertEquals('users.user_date_added < NOW()', $oCondition->toSQL());
        }

        public function testBetweenDateAndNow() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\DateTime('user_date_added')
            );

            $oUsers->user_date_added->setValue('2011-11-05 12:02:00');
            $oCondition = Condition::between($oUsers->user_date_added, Field\DateTime::MYSQL_NOW);
            $this->assertEquals("users.user_date_added BETWEEN '2011-11-05 12:02:00' AND NOW()", $oCondition->toSQL());
        }

        public function testBetweenDateValue() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\DateTime('user_date_added')
            );

            $oUsers->user_date_added->setValue('2011-11-05 12:01:00');
            $oCondition = Condition::between($oUsers->user_date_added, '2011-11-05 12:02:00');
            $this->assertEquals("users.user_date_added BETWEEN '2011-11-05 12:01:00' AND '2011-11-05 12:02:00'", $oCondition->toSQL());
        }

        public function testFieldOrder() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );

            $oFirstId = clone $oUsers->user_id;
            $oFirstId->setValue(10);

            $oSecondId = clone $oUsers->user_id;
            $oSecondId->setValue(20);

            $oCondition = Condition::between($oUsers->user_id, $oSecondId, $oFirstId);
            $this->assertEquals("users.user_id BETWEEN '20' AND '10'", $oCondition->toSQL());
        }

        public function testLessThan() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::lt($oUsers->user_id);
            $this->assertEquals("users.user_id < '10'", $oCondition->toSQL());
        }

        public function testLessThanOrEqual() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::lte($oUsers->user_id);
            $this->assertEquals("users.user_id <= '10'", $oCondition->toSQL());
        }

        public function testGreaterThan() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::gt($oUsers->user_id);
            $this->assertEquals("users.user_id > '10'", $oCondition->toSQL());
        }

        public function testGreaterThanOrEqual() {
            $oUsers = new Table('users');
            $oUsers->addField(
                new Field\Integer('user_id')
            );
            $oUsers->user_id->setValue(10);
            $oCondition = Condition::gte($oUsers->user_id);
            $this->assertEquals("users.user_id >= '10'", $oCondition->toSQL());
        }
    }