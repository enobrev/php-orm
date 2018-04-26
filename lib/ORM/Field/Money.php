<?php
    namespace Enobrev\ORM\Field;

    use PDO;
    use stdClass;

    use Money\Currency;
    use Money\Money as MoneyPHP;
    use Money\Parser\DecimalMoneyParser;
    use Money\Currencies;

    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\FieldInvalidValueException;

    use function Enobrev\dbg;

    class Money extends Number {
        const DEFAULT_CURRENCY = 'USD';

        /** @var MoneyPHP|null */
        public $sValue;

        /** @var Currency */
        protected $oCurrency;

        /** @var string */
        protected $sCurrencyField;

        /**
         * @return MoneyPHP|null
         */
        public function getValue() {
            return $this->sValue;
        }

        /**
         * @param string $sCurrency
         */
        public function setCurrency(string $sCurrency) {
            $this->oCurrency = new Currency($sCurrency);
        }

        public function setCurrencyField($sField) {
            $this->sCurrencyField = $sField instanceof Field ? $sField->sColumn : $sField;
        }

        /**
         * @return Currency
         */
        public function getCurrency() {
            if (!$this->oCurrency) {
                $this->setCurrency(self::DEFAULT_CURRENCY);
            }

            return $this->oCurrency;
        }

        /**
         *
         * @return string
         */
        public function __toString():string {
            $sValue = 0;

            if ($this->sValue instanceof MoneyPHP) {
                $sValue = $this->sValue->getAmount();
            }

            return $sValue != 0 ? (string) $sValue : '0';
        }

        /**
         * @param $sValue
         */
        public function setValueFromDecimal($sValue) {
            $oParser = new DecimalMoneyParser(new Currencies\ISOCurrencies());
            $this->sValue = $oParser->parse($sValue, $this->getCurrency());
        }

        /**
         *
         * @param stdClass $oData
         */
        public function setValueFromData($oData): void {
            if (isset($oData->{$this->sColumn})) {
                if ($this->sCurrencyField && isset($oData->{$this->sCurrencyField})) {
                    $this->setCurrency($oData->{$this->sCurrencyField});
                }

                $this->setValue($oData->{$this->sColumn});
            }
        }

        /**
         *
         * @param array $aData
         */
        public function setValueFromArray($aData): void {
            if (isset($aData[$this->sColumn]) || array_key_exists($this->sColumn, $aData)) {
                if (isset($aData[$this->sCurrencyField]) || array_key_exists($this->sCurrencyField, $aData)) {
                    $this->setCurrency($aData[$this->sCurrencyField]);
                }

                $this->setValue($aData[$this->sColumn]);
            }
        }

        /**
         * @return string
         */
        public function toSQL():string {
            if ($this->isNull()) {
                return 'NULL';
            }

            if ($this->sValue instanceof MoneyPHP) {
                return Escape::string($this->__toString(), PDO::PARAM_INT);
            } else {
                return 'NULL';
            }
        }

        /**
         *
         * @param mixed $sValue
         * @return $this
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            switch(true) {
                case $sValue === null:
                case $sValue instanceof MoneyPHP:
                    $this->sValue = $sValue;
                    break;

                case $sValue instanceof stdClass:
                    if (property_exists($sValue, 'amount')) { // coming from json
                        if (property_exists($sValue, 'currency')) {
                            /** @var stdClass $sValue */
                            $this->sValue = new MoneyPHP($sValue->amount, new Currency($sValue->currency));
                        } else {
                            /** @var stdClass $sValue */
                            $this->sValue = new MoneyPHP($sValue->amount, $this->getCurrency());
                        }
                    } else {
                        throw new FieldInvalidValueException();
                    }
                    break;

                case is_array($sValue):
                    if (isset($sValue['amount'])) {
                        if (isset($sValue['currency'])) {
                            $this->sValue = new MoneyPHP($sValue['amount'], new Currency($sValue['currency']));
                        } else {
                            $this->sValue = new MoneyPHP($sValue['amount'], $this->getCurrency());
                        }
                    } else {
                        throw new FieldInvalidValueException();
                    }
                    break;

                default:
                    $this->sValue = new MoneyPHP($sValue, $this->getCurrency());
                    break;
            }

            return $this;
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue): bool {
            if ($mValue instanceof Table) {
                $mValue = $mValue->{$this->sColumn};
            }

            if ($mValue instanceof self) {
                $mValue = $mValue->getValue();
            }

            if ($mValue instanceof stdClass) {
                $oMoney = new self($this->sColumn);
                $oMoney->setValue($mValue);
                $mValue = $oMoney;
            }

            if (is_array($mValue)) {
                $oMoney = new self($this->sColumn);
                $oMoney->setValue($mValue);
                $mValue = $oMoney;
            }

            if ($mValue === null) {
                return $this->isNull(); // Both Null
            } else if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            if ($mValue instanceof MoneyPHP === false) {
                $mValue = new MoneyPHP($mValue, $this->getCurrency());
            }

            return $this->sValue->equals($mValue);
        }
    }