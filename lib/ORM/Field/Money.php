<?php
// DISABLING THIS FILE UNTIL moneyphp/money gets php 8.1 support merged

//    namespace Enobrev\ORM\Field;
//
//    use Enobrev\ORM\Exceptions\DbException;
//    use Enobrev\ORM\Table;
//    use PDO;
//    use stdClass;
//
//    use Money\Currency;
//    use Money\Money as MoneyPHP;
//    use Money\Parser\DecimalMoneyParser;
//    use Money\Currencies;
//
//    use Enobrev\ORM\Escape;
//    use Enobrev\ORM\Field;
//    use Enobrev\ORM\Exceptions\FieldInvalidValueException;
//
//    class Money extends Number {
//        protected const DEFAULT_CURRENCY = 'USD';
//
//        /** @var MoneyPHP|null */
//        public $sValue;
//
//        protected ?Currency $oCurrency = null;
//
//        protected string $sCurrencyField;
//
//        /**
//         * @param string $sCurrency
//         */
//        public function setCurrency(string $sCurrency): void {
//            $this->oCurrency = new Currency($sCurrency);
//        }
//
//        /**
//         * @param Field|string $sField
//         */
//        public function setCurrencyField($sField): void {
//            $this->sCurrencyField = $sField instanceof Field ? $sField->sColumn : $sField;
//        }
//
//        /**
//         * @return Currency
//         */
//        public function getCurrency(): Currency {
//            if (!$this->oCurrency) {
//                $this->setCurrency(self::DEFAULT_CURRENCY);
//            }
//
//            return $this->oCurrency;
//        }
//
//        public function __toString():string {
//            $sValue = 0;
//
//            if ($this->sValue instanceof MoneyPHP) {
//                $sValue = $this->sValue->getAmount();
//            }
//
//            return $sValue !== 0 ? (string) $sValue : '0';
//        }
//
//        public function setValueFromDecimal($sValue): void {
//            $oParser = new DecimalMoneyParser(new Currencies\ISOCurrencies());
//            $this->sValue = $oParser->parse($sValue, $this->getCurrency());
//        }
//
//        /**
//         *
//         * @param stdClass $oData
//         *
//         * @throws FieldInvalidValueException
//         * @noinspection PhpMissingParamTypeInspection
//         */
//        public function setValueFromData($oData): void {
//            if (isset($oData->{$this->sColumn})) {
//                if ($this->sCurrencyField && isset($oData->{$this->sCurrencyField})) {
//                    $this->setCurrency($oData->{$this->sCurrencyField});
//                }
//
//                $this->setValue($oData->{$this->sColumn});
//            }
//        }
//
//        /**
//         * @param array $aData
//         *
//         * @throws FieldInvalidValueException
//         */
//        public function setValueFromArray(array $aData): void {
//            if (array_key_exists($this->sColumn, $aData)) {
//                if (array_key_exists($this->sCurrencyField, $aData)) {
//                    $this->setCurrency($aData[$this->sCurrencyField]);
//                }
//
//                $this->setValue($aData[$this->sColumn]);
//            }
//        }
//
//        /**
//         * @return string
//         * @throws DbException
//         */
//        public function toSQL():string {
//            if ($this->isNull()) {
//                return 'NULL';
//            }
//
//            if ($this->sValue instanceof MoneyPHP) {
//                return Escape::string($this->__toString(), PDO::PARAM_INT);
//            }
//
//            return 'NULL';
//        }
//
//        /**
//         *
//         * @param mixed $sValue
//         *
//         * @return $this
//         * @throws FieldInvalidValueException
//         * @noinspection PhpMissingReturnTypeInspection
//         */
//        public function setValue($sValue) {
//            if ($sValue instanceof Table) {
//                $sValue = $sValue->{$this->sColumn};
//            }
//
//            if ($sValue instanceof Field) {
//                $sValue = $sValue->getValue();
//            }
//
//            switch(true) {
//                case $sValue === null:
//                case $sValue instanceof MoneyPHP:
//                    $this->sValue = $sValue;
//                    break;
//
//                case $sValue instanceof stdClass:
//                    if (property_exists($sValue, 'amount')) { // coming from json
//                        if (property_exists($sValue, 'currency')) {
//                            /** @var stdClass $sValue */
//                            $this->sValue = new MoneyPHP($sValue->amount, new Currency($sValue->currency));
//                        } else {
//                            /** @var stdClass $sValue */
//                            $this->sValue = new MoneyPHP($sValue->amount, $this->getCurrency());
//                        }
//                    } else {
//                        throw new FieldInvalidValueException();
//                    }
//                    break;
//
//                case is_array($sValue):
//                    if (isset($sValue['amount'])) {
//                        if (isset($sValue['currency'])) {
//                            $this->sValue = new MoneyPHP($sValue['amount'], new Currency($sValue['currency']));
//                        } else {
//                            $this->sValue = new MoneyPHP($sValue['amount'], $this->getCurrency());
//                        }
//                    } else {
//                        throw new FieldInvalidValueException();
//                    }
//                    break;
//
//                case trim($sValue) === '':
//                    $this->sValue = null;
//                    break;
//
//                default:
//                    $this->sValue = new MoneyPHP($sValue, $this->getCurrency());
//                    break;
//            }
//
//            return $this;
//        }
//
//        /**
//         * @param mixed $mValue
//         *
//         * @return bool
//         * @throws FieldInvalidValueException
//         */
//        public function is($mValue): bool {
//            if ($mValue instanceof Table) {
//                $mValue = $mValue->{$this->sColumn};
//            }
//
//            if ($mValue instanceof self) {
//                $mValue = $mValue->getValue();
//            }
//
//            if ($mValue instanceof stdClass) {
//                $oMoney = new self($this->sColumn);
//                $oMoney->setValue($mValue);
//                $mValue = $oMoney;
//            }
//
//            if (is_array($mValue)) {
//                $oMoney = new self($this->sColumn);
//                $oMoney->setValue($mValue);
//                $mValue = $oMoney;
//            }
//
//            if ($mValue === null) {
//                return $this->isNull(); // Both Null
//            }
//
//            if ($this->isNull()) {
//                return false;           // My Value is null but comparator is not
//            }
//
//            if ($mValue instanceof MoneyPHP === false) {
//                $mValue = new MoneyPHP($mValue, $this->getCurrency());
//            }
//
//            return $this->sValue->equals($mValue);
//        }
//    }