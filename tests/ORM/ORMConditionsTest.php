<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';

    use Enobrev\ORM\Condition;
    use Enobrev\ORM\Conditions;
    use Enobrev\ORM\Db;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;
    use PHPUnit\Framework\TestCase;


    class ORMConditionsTest extends TestCase {
        public function setUp():void {
            Db::getInstance(Db::defaultSQLiteMemory());
        }

        public function testOne(): void {
            $oUsers = new ORMConditionsTestUser();
            $oUsers->user_id->setValue(1);

            $oConditions = Conditions::also(Condition::eq($oUsers->user_id));
            $this->assertEquals('users.user_id = 1', $oConditions->toSQL());
        }

        public function testAnd(): void {
            $oUsers = new ORMConditionsTestUser();
            $oUsers->user_id->setValue(1);
            $oUsers->user_name_first->setValue( 'Mark');

            $oConditions = Conditions::also(
                Condition::eq($oUsers->user_id),
                Condition::eq($oUsers->user_name_first)
            );
            $this->assertEquals('users.user_id = 1 AND users.user_name_first = "Mark"', $oConditions->toSQL());
        }

        public function testOr(): void {
            $oUsers = new ORMConditionsTestUser();
            $oUsers->user_id->setValue( 1);
            $oUsers->user_name_first->setValue( 'Mark');

            $oConditions = Conditions::either(
                Condition::eq($oUsers->user_id),
                Condition::eq($oUsers->user_name_first)
            );
            $this->assertEquals('users.user_id = 1 OR users.user_name_first = "Mark"', $oConditions->toSQL());
        }

        public function testAndGroup(): void {
            $oUserOne = new ORMConditionsTestUser();
            $oUserOne->user_id->setValue(1);
            $oUserOne->user_name_first->setValue('Mark');

            $oUserTwo = new ORMConditionsTestUser();
            $oUserTwo->user_id->setValue(2);
            $oUserTwo->user_name_first->setValue('Test');

            $oConditions = Conditions::either(
                Conditions::also(
                    Condition::eq($oUserOne->user_id),
                    Condition::eq($oUserOne->user_name_first)
                ),
                Conditions::also(
                    Condition::eq($oUserTwo->user_id),
                    Condition::eq($oUserTwo->user_name_first)
                )
            );
            $this->assertEquals('(users.user_id = 1 AND users.user_name_first = "Mark") OR (users.user_id = 2 AND users.user_name_first = "Test")', $oConditions->toSQL());
        }
    }

    class ORMConditionsTestUser extends Table {
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