<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\ORM\Exceptions\FieldInvalidValueException;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\Table;

    class Enum extends Field {
        /** @var string[]  */
        public array $aValues = [];

        /** @var string  */
        public $sValue;

        /**
         * @param string $sTable
         * @param array|string $sColumn
         * @param array $aValues
         *
         * @noinspection PhpMissingParamTypeInspection
         */
        public function __construct($sTable, $sColumn, Array $aValues = array()) {
            $this->sColumn = '';

            if (is_array($sColumn)) {
                parent::__construct($sTable);
                if (count($sColumn)) {
                    $this->aValues = $sColumn;
                }
            } else {
                parent::__construct($sTable, $sColumn);
                if (count($aValues)) {
                    $this->aValues = $aValues;
                }
            }

        }
        
        /**
         *
         * @return string
         */
        public function __toString(): string {
            if ($this->sValue) {
                return $this->sValue;
            }
            
            return '';
        }

        /**
         *
         * @return string
         * @throws DbException
         */
        public function toSQL(): string {
            return Escape::string($this->__toString());
        }

        public function toSQLLog(): string {
            return parent::toSQLLog() . ':' . $this->__toString();
        }

        public function toInfoArray(): array {
            $aInfo = parent::toInfoArray();
            $aInfo['values'] = $this->aValues;

            return $aInfo;
        }

        public function hasValue(): bool {
            return parent::hasValue() && (string)$this !== '';
        }

        /**
         * @param $sValue
         * @return bool
         */
        public function isValue($sValue):bool {
            return in_array($sValue, $this->aValues, true);
        }

        /**
         * @param mixed $sValue
         * @return $this
         * @noinspection PhpMissingReturnTypeInspection
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            $sValue = (string) $sValue;

            assert($this->isValue($sValue), new FieldInvalidValueException($this->sColumn . ' [' . $sValue . ']'));

            $this->sValue = $sValue;

            return $this;
        }
    }