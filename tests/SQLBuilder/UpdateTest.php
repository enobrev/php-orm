<?php
    namespace Enobrev;

    require __DIR__ . '/../../vendor/autoload.php';

    use Enobrev\ORM\Mock\Table\User;

    class SQLBuilderUpdateTest extends \PHPUnit\Framework\TestCase {
        public function testOne() {
            $oUser    = new User();
            $oUser->user_id->setValue(2);
            $oUser->user_name->setValue('Testtttt');
            $oUser->user_email->setValue('whynot@fuckit.com');

            $oSQL     = SQLBuilder::update($oUser)
                 ->fields(
                    $oUser->user_id,
                    $oUser->user_name,
                    $oUser->user_email
                )->eq($oUser->user_id, 1)
                 ->eq($oUser->user_email, 'test@example.com');

            $this->assertEquals('UPDATE users SET user_id = 2, user_name = "Testtttt", user_email = "whynot@fuckit.com" WHERE users.user_id = 1 AND users.user_email = "test@example.com"', (string) $oSQL);
        }

        public function testTwo() {
            $oUser    = new User();
            $oUser->user_id->setValue(2);
            $oUser->user_name->setValue('Testtttt');
            $oUser->user_email->setValue('whynot@fuckit.com');

            $oSQL     = SQLBuilder::update($oUser)->eq($oUser->user_id, 1);

            $this->assertEquals('UPDATE users SET user_id = 2, user_name = "Testtttt", user_email = "whynot@fuckit.com", user_happy = 0, user_date_added = NULL WHERE users.user_id = 1', (string) $oSQL);
        }
    }



