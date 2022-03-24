<?php
    namespace Enobrev;

    require __DIR__ . '/../../../vendor/autoload.php';

    use PHPUnit\Framework\TestCase;
    use Enobrev\ORM\Field;

    use Money\Money;
    use Money\Currency;
    use stdClass;

    class MoneyTest extends TestCase {

        protected function setUp(): void {
            parent::setUp();
            $this->markTestIncomplete(); // Disabled until moneyphp is updated for php 8.1
        }

        public function test__toString(): void {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertEquals('500', (string) $oMoney);
        }

        public function testSetValue(): void {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertInstanceOf(Money::class, $oMoney->sValue);
            $this->assertEquals('500', $oMoney->sValue->getAmount());
            $this->assertEquals('USD', $oMoney->sValue->getCurrency());
        }

        public function testSetValueObject(): void {
            $oMoney = new Field\Money('amount');
            $oObject = new stdClass();
            $oObject->amount = 500;
            $oObject->currency = 'USD';
            $oMoney->setValue($oObject);
            $this->assertInstanceOf(Money::class, $oMoney->sValue);
            $this->assertEquals('500', $oMoney->sValue->getAmount());
            $this->assertEquals('USD', $oMoney->sValue->getCurrency());
        }

        public function testSetValueArray(): void {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(['amount' => 500, 'currency' => 'USD']);
            $this->assertInstanceOf(Money::class, $oMoney->sValue);
            $this->assertEquals('500', $oMoney->sValue->getAmount());
            $this->assertEquals('USD', $oMoney->sValue->getCurrency());
        }

        public function testIs(): void {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertTrue($oMoney->is(500));
            $this->assertTrue($oMoney->is(new Money(500, new Currency('USD'))));
            $this->assertTrue($oMoney->is(['amount' => 500, 'currency' => 'USD']));
        }

        public function testToSQL(): void {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertEquals('500', $oMoney->toSQL());
        }

        public function testGetValue(): void {
            $oMoney = new Field\Money('amount');
            $oMoney->setValue(500);
            $this->assertInstanceOf(Money::class, $oMoney->getValue());
            $this->assertEquals('500', $oMoney->getValue()->getAmount());
            $this->assertEquals('USD', $oMoney->getValue()->getCurrency());
        }

        public function testSetValueFromArray(): void {
            $oCurrency = new Field\Text('currency');
            $oMoney = new Field\Money('my_money');
            $oMoney->setCurrencyField($oCurrency->sColumn);
            $oMoney->setValueFromArray([
                'id'        => 1,
                'currency'  => 'USD',
                'my_money'  => 500
            ]);
            $this->assertInstanceOf(Money::class, $oMoney->getValue());
            $this->assertEquals('500', $oMoney->getValue()->getAmount());
            $this->assertEquals('USD', $oMoney->getValue()->getCurrency());
        }

        public function testSetValueFromObject(): void {
            $oCurrency = new Field\Text('currency');
            $oMoney = new Field\Money('my_money');
            $oMoney->setCurrencyField($oCurrency->sColumn);
            $oRecord = new stdClass();
            $oRecord->id = 1;
            $oRecord->my_money = 500;
            $oRecord->currency = 'USD';
            $oMoney->setValueFromData($oRecord);
            $this->assertInstanceOf(Money::class, $oMoney->getValue());
            $this->assertEquals('500', $oMoney->getValue()->getAmount());
            $this->assertEquals('USD', $oMoney->getValue()->getCurrency());
        }
    }
