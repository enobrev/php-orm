<?php
    namespace Enobrev\ORM\Field;

    use Enobrev\ORM\Db;
    use Enobrev\ORM\Field;
    use Enobrev\ORM\FieldInvalidValueException;

    class Enum extends Field {
        public $aValues = array();

        /**
         * @param string $sTable
         * @param string $sColumn
         * @param array $aValues
         */
        public function __construct($sTable, $sColumn, Array $aValues = array()) {
            if (is_array($sColumn)) {
                $aValues = $sColumn;
                $sColumn = $sTable;
                $sTable  = null;
            }

            parent::__construct($sTable, $sColumn);
            
            if (count($aValues)) {
                $this->aValues = $aValues;
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
         */
        public function toSQL() {
            return '"' . Db::getInstance()->real_escape_string($this->__toString()) . '"';
        }
        /**
         *
         * @return string
         */
        public function toSQLLog() {
            return parent::toSQLLog() . ':' . $this->__toString();
        }

        /**
         *
         * @return string
         */
        public function toInfoArray() {
            $aInfo = parent::toInfoArray();
            $aInfo['values'] = $this->aValues;

            return $aInfo;
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return parent::hasValue() && strlen((string) $this) > 0;
        }

        /**
         * @param string $sValue
         * @return bool
         */
        public function isValue($sValue) {
            return in_array($sValue, $this->aValues);
        }

        /**
         * @param mixed $sValue
         * @throws FieldInvalidValueException
         */
        public function setValue($sValue) {
            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }

            $sValue = (string) $sValue;
                        
            if (!$this->isValue($sValue)) {
                throw new FieldInvalidValueException($this->sColumn . ' [' . $sValue . ']');
            }

            $this->sValue = $sValue;
        }
    }
?>