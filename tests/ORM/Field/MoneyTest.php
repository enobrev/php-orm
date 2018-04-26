<?php
    namespace Enobrev;

    require __DIR__ . '/../../../vendor/autoload.php';

    use PHPUnit\Framework\TestCase;
    use Enobrev\ORM\Field;

    use Money\Money;
    use Money\Currency;

    class MoneyTest extends TestCase {

        public function test__toString() {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertEquals('500', (string) $oMoney);
        }

        public function testSetValue() {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertInstanceOf('Money\\Money', $oMoney->sValue);
            $this->assertEquals('500', $oMoney->sValue->getAmount());
            $this->assertEquals('USD', $oMoney->sValue->getCurrency());
        }

        public function testSetValueObject() {
            $oMoney = new Field\Money('amount');
            $oObject = new \stdClass();
            $oObject->amount = 500;
            $oObject->currency = 'USD';
            $oMoney->setValue($oObject);
            $this->assertInstanceOf('Money\\Money', $oMoney->sValue);
            $this->assertEquals('500', $oMoney->sValue->getAmount());
            $this->assertEquals('USD', $oMoney->sValue->getCurrency());
        }

        public function testSetValueArray() {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(['amount' => 500, 'currency' => 'USD']);
            $this->assertInstanceOf('Money\\Money', $oMoney->sValue);
            $this->assertEquals('500', $oMoney->sValue->getAmount());
            $this->assertEquals('USD', $oMoney->sValue->getCurrency());
        }

        public function testIs() {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertTrue($oMoney->is(500));
            $this->assertTrue($oMoney->is(new Money(500, new Currency('USD'))));
            $this->assertTrue($oMoney->is(['amount' => 500, 'currency' => 'USD']));
        }

        public function testToSQL() {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertEquals('500', $oMoney->toSQL());
        }

        public function testGetValue() {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertInstanceOf('Money\\Money', $oMoney->getValue());
            $this->assertEquals('500', $oMoney->getValue()->getAmount());
            $this->assertEquals('USD', $oMoney->getValue()->getCurrency());
        }

        public function testSetValueFromArray() {
            $oCurrency = new Field\Text('currency');
            $oMoney = new Field\Money('my_money');
            $oMoney->setCurrencyField($oCurrency->sColumn);
            $oMoney->setValueFromArray([
                'id'        => 1,
                'currency'  => 'USD',
                'my_money'  => 500
            ]);
            $this->assertInstanceOf('Money\\Money', $oMoney->getValue());
            $this->assertEquals('500', $oMoney->getValue()->getAmount());
            $this->assertEquals('USD', $oMoney->getValue()->getCurrency());
        }

        public function testSetValueFromObject() {
            $oCurrency = new Field\Text('currency');
            $oMoney = new Field\Money('my_money');
            $oMoney->setCurrencyField($oCurrency->sColumn);
            $oRecord = new \stdClass();
            $oRecord->id = 1;
            $oRecord->my_money = 500;
            $oRecord->currency = 'USD';
            $oMoney->setValueFromData($oRecord);
            $this->assertInstanceOf('Money\\Money', $oMoney->getValue());
            $this->assertEquals('500', $oMoney->getValue()->getAmount());
            $this->assertEquals('USD', $oMoney->getValue()->getCurrency());
        }
    }
