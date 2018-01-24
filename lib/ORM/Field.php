<?php
    namespace Enobrev\ORM;
    
    class FieldException extends DbException {}
    class FieldInvalidTypeException extends FieldException {}
    class FieldInvalidValueException extends FieldException {}
    
    abstract class Field {
        /** @var string|null  */
        public $sTable;

        /** @var string|null  */
        public $sTableClass;

        /** @var string  */
        public $sColumn;

        /** @var mixed|null  */
        public $sValue;

        /** @var mixed|null  */
        public $sDefault;

        /** @var string|null  */
        public $sAlias;

        /** @var boolean  */
        private $bPrimary;

        /** @var string|null */
        private $sReferenceTable = null;

        /** @var string|null */
        private $sReferenceField = null;

        /**
         *
         * @param string $sTable Can also be column name if no table is to be specified
         * @param string $sColumn
         */
        public function __construct(string $sTable, string $sColumn = null) {
            if ($sColumn === null) {
                $sColumn = $sTable;
                $sTable  = null;
            }

            $this->sTable           = $sTable;
            $this->sColumn          = $sColumn;
            $this->bPrimary         = false;
            $this->sDefault         = null;
            $this->sValue           = null;
            $this->sAlias           = null;
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
        abstract public function toSQL(): string;

        /**
         *
         * @return string
         */
        public function toSQLLog(): string {
            return str_replace('Field_', '', get_class($this));
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumn(bool $bWithTable=true): string {
            if ($bWithTable) {
                if ($this->sAlias && strlen($this->sAlias)) {
                    return implode('.', array($this->sAlias, $this->sColumn));
                } else if ($this->sTable && strlen($this->sTable)) {
                    return implode('.', array($this->sTable, $this->sColumn));
                }
            }

            return $this->sColumn;
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnForFields(bool $bWithTable = true): string {
            if ($bWithTable) {
                if ($this->sAlias && strlen($this->sAlias)) {
                    return implode(' ', array(implode('.', [$this->sAlias, $this->sColumn]), "AS", implode('_', [$this->sAlias, $this->sColumn])));
                } else if ($this->sTable && strlen($this->sTable)) {
                    return implode('.', [$this->sTable, $this->sColumn]);
                }
            }

            return $this->sColumn;
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnForSelect(bool $bWithTable = true): string {
            return $this->toSQLColumnForFields($bWithTable);
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnForCount(bool $bWithTable = true): string {
            if ($bWithTable) {
                return implode('.', array($this->sTable, $this->sColumn));
            }

            return $this->sColumn;
        }

        /**
         * @return string
         */
        public function toSQLColumnForInsert(): string {
            return $this->toSQLColumnForFields(false);
        }

        /**
         *
         * @return array
         */
        public function toInfoArray():array {
            return [
                'name'  => $this->sColumn,
                'type'  => get_class($this)
            ];
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

            $this->sValue = $sValue;

            return $this;
        }

        public function applyDefault(): void {
            $this->setValue($this->sDefault);
        }

        /**
         * @param string $sDefault
         */
        public function setDefault($sDefault): void {
            $this->sDefault = $sDefault;
        }

        /**
         * @return bool
         */
        public function hasDefault(): bool {
            return $this->sDefault !== null;
        }

        /**
         * @return bool
         */
        public function isDefault(): bool {
            return $this->getValue() == $this->sDefault;
        }

        /**
         * @param string $sAlias
         */
        public function setAlias($sAlias): void {
            $this->sAlias = $sAlias;
        }

        /**
         * @return bool
         */
        public function hasAlias(): bool {
            return $this->sAlias !== null;
        }

        /**
         * @param boolean $bPrimary
         */
        public function setPrimary($bPrimary):void {
            $this->bPrimary = $bPrimary;
        }

        /**
         * @return bool
         */
        public function isPrimary(): bool {
            return $this->bPrimary;
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
        public function is($mValue): bool {
            if ($mValue instanceof Table) {
                $mValue = $mValue->{$this->sColumn};
            }

            if ($mValue instanceof self) {
                return $this->is($mValue->getValue());
            }

            if ($mValue === null) {
                return $this->isNull(); // Both Null
            } else if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            return (string) $this == (string) $mValue;
        }

        /**
         * @param $aValues
         * @return bool
         */
        public function in($aValues): bool {
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
        public function isNull(): bool {
            return $this->sValue === NULL;
        }

        /**
         * @return bool
         */
        public function hasValue(): bool {
            return !$this->isNull();
        }
        
        /**
         *
         * @param \stdClass $oData
         */
        public function setValueFromData($oData): void {
            if (isset($oData->{$this->sColumn})) {
                $this->setValue($oData->{$this->sColumn});
            }
        }

        /**
         *
         * @param array $aData
         */
        public function setValueFromArray($aData): void {
            if (isset($aData[$this->sColumn]) || array_key_exists($this->sColumn, $aData)) {
                $this->setValue($aData[$this->sColumn]);
            }
        }

        /**
         * @return Table|null
         */
        public function getTable(): ?Table {
            if ($this->sTableClass) {
                return new $this->sTableClass;
            }

            return null;
        }

        /**
         * @param string $sTable
         * @param string $sField
         */
        public function references($sTable, $sField): void {
            $this->sReferenceTable = $sTable;
            $this->sReferenceField = $sField;
        }

        /**
         * @param Table $oTable
         * @return bool
         */
        public function referencesTable(Table $oTable): bool {
            return $this->sReferenceTable == $oTable->getTitle();
        }

        /**
         * @return bool
         */
        public function hasReference(): bool {
            return $this->sReferenceTable !== null
                && $this->sReferenceField !== null;
        }

        /**
         * @return null|string
         */
        public function referenceField(): ?string {
            return $this->sReferenceField;
        }
    }