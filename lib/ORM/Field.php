<?php
    namespace Enobrev\ORM;
    
    class FieldException extends DbException {}
    class FieldInvalidTypeException extends FieldException {}
    class FieldInvalidValueException extends FieldException {}
    
    abstract class Field {
        /** @var string  */
        public $sTable;

        /** @var string  */
        public $sTableClass;

        /**
         * @var string
         */
        public $sColumn;

        /**
         * @var mixed
         */
        public $sValue;

        /**
         * @var mixed
         */
        public $sDefault;

        /**
         * @var string
         */
        public $sAlias;

        /**
         * @var boolean
         */
        public $bPrimary;
        
        /**
         *
         * @param string $sTable Can also be column name if no table is to be specified
         * @param string $sColumn
         */
        public function __construct($sTable, $sColumn = null) {
            if ($sColumn === null) {
                $sColumn = $sTable;
                $sTable  = null;
            }

            $this->sTable   = $sTable;
            $this->sColumn  = $sColumn;
            $this->bPrimary = false;
            $this->sDefault = null;
            $this->sValue   = null;
            $this->sAlias   = null;
        }
        
        /**
         *
         * @return string|integer
         */
        abstract public function __toString();
        
        /**
         *
         * @return string
         */
        abstract public function toSQL();

        /**
         *
         * @return string
         */
        public function toSQLLog() {
            return str_replace('Field_', '', get_class($this));
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumn($bWithTable=true) {
            if ($bWithTable) {
                if (strlen($this->sAlias)) {
                    return implode('.', array($this->sAlias, $this->sColumn));
                } else if (strlen($this->sTable)) {
                    return implode('.', array($this->sTable, $this->sColumn));
                }
            }
            
            return $this->sColumn;
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnForFields($bWithTable = true) {
            if ($bWithTable) {
                if (strlen($this->sAlias)) {
                    return implode(' ', array(implode('.', [$this->sAlias, $this->sColumn]), "AS", implode('_', [$this->sAlias, $this->sColumn])));
                } else if (strlen($this->sTable)) {
                    return implode('.', [$this->sTable, $this->sColumn]);
                }
            }

            return $this->sColumn;
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnForSelect($bWithTable = true) {
            return $this->toSQLColumnForFields($bWithTable);
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnForCount($bWithTable = true) {
            if ($bWithTable) {
                return implode('.', array($this->sTable, $this->sColumn));
            }

            return $this->sColumn;
        }

        /**
         * @return string
         */
        public function toSQLColumnForInsert() {
            return $this->toSQLColumnForFields(false);
        }

        /**
         *
         * @return string
         */
        public function toInfoArray() {
            return array(
                'name'  => $this->sColumn,
                'type'  => get_class($this)
            );
        }

        /**
         *
         * @param mixed $sValue
         * @return Field
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof Field) {
                $sValue = $sValue->getValue();
            }
            
            $this->sValue = $sValue;

            return $this;
        }

        public function applyDefault() {
            $this->setValue($this->sDefault);
        }

        /**
         * @param string $sDefault
         */
        public function setDefault($sDefault) {
            $this->sDefault = $sDefault;
        }

        /**
         * @return bool
         */
        public function hasDefault() {
            return $this->sDefault !== null;
        }

        /**
         * @param string $sAlias
         */
        public function setAlias($sAlias) {
            $this->sAlias = $sAlias;
        }

        /**
         * @param boolean $bPrimary
         */
        public function setPrimary($bPrimary) {
            $this->bPrimary = $bPrimary;
        }

        /**
         * @return bool
         */
        public function isPrimary() {
            return $this->bPrimary;
        }

        /**
         * @return bool
         */
        public function hasAlias() {
            return $this->sAlias !== null;
        }
        
        /**
         *
         * @return mixed
         */
        public function getValue() {            
            return $this->sValue;
        }

        /**
         *
         * @return mixed
         */
        public function getValueOrDefault() {
            return $this->hasValue() ? $this->sValue : $this->sDefault;
        }

        /**
         * @param mixed $mValue
         * @return bool
         */
        public function is($mValue) {
            if ($mValue instanceof self) {
                return $this->is($mValue->getValue());
            }

            if ($mValue === null && $this->isNull()) {
                return true;
            }

            return (string) $this == (string) $mValue;
        }

        /**
         * @param $aValues
         * @return bool
         */
        public function in($aValues) {
            if (!is_array($aValues)) {
                $aValues = func_get_args();
            }
            
            foreach($aValues as $mValue) {
                if ($this->is($mValue)) {
                    return true;
                }
            }

            return false;
        }
        
        /**
         *
         * @return boolean
         */
        public function isNull() {
            return $this->sValue === NULL;
        }

        /**
         * @return bool
         */
        public function hasValue() {
            return !$this->isNull();
        }
        
        /**
         *
         * @param \stdClass $oData
         */
        public function setValueFromData($oData) {
            if (isset($oData->{$this->sColumn})) {
                $this->setValue($oData->{$this->sColumn});
            }
        }

        /**
         *
         * @param array $aData
         */
        public function setValueFromArray($aData) {    
            if (isset($aData[$this->sColumn]) || array_key_exists($this->sColumn, $aData)) {
                $this->setValue($aData[$this->sColumn]);
            }
        }

        public function getTable() {
            if ($this->sTableClass) {
                return new $this->sTableClass;
            }
        }
    }
?>