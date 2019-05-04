<?php
    namespace Enobrev\ORM\Field;


    use Enobrev\ORM\DbException;
    use Enobrev\ORM\Escape;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\FieldInvalidValueException;
    use Enobrev\ORM\Table;

    class Enum extends Field {
        /** @var string[]  */
        public $aValues = [];

        /** @var string  */
        public $sValue;

        /**
         * @param string $sTable
         * @param array|string $sColumn
         * @param array $aValues
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
         * @return string|integer
         */
        public function __toString() {
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
        /**
         *
         * @return string
         */
        public function toSQLLog(): string {
            return parent::toSQLLog() . ':' . $this->__toString();
        }

        /**
         *
         * @return array
         */
        public function toInfoArray(): array {
            $aInfo = parent::toInfoArray();
            $aInfo['values'] = $this->aValues;

            return $aInfo;
        }

        /**
         * @return bool
         */
        public function hasValue(): bool {
            return parent::hasValue() && (string)$this !== '';
        }

        /**
         * @param string $sValue
         * @return bool
         */
        public function isValue($sValue):bool {
            return in_array($sValue, $this->aValues, true);
        }

        /**
         * @param mixed $sValue
         * @return $this
         * @throws FieldInvalidValueException
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            $sValue = (string) $sValue;
                        
            if (!$this->isValue($sValue)) {
                throw new FieldInvalidValueException($this->sColumn . ' [' . $sValue . ']');
            }

            $this->sValue = $sValue;

            return $this;
        }
    }