<?php
    namespace Enobrev\ORM;
    
    use stdClass;

    abstract class Field {
        public ?string $sTable;

        public ?string $sTableClass;

        public string $sColumn;

        /** @var mixed|null  */
        public $sValue;

        /** @var mixed|null  */
        public $sDefault;

        public ?string $sAlias;

        private bool $bPrimary;

        private bool $bGenerated;

        private ?string $sReferenceTable;

        private ?string $sReferenceField;

        /**
         *
         * @param string $sTable Can also be column name if no table is to be specified
         * @param string|null $sColumn
         */
        public function __construct(string $sTable, string $sColumn = null) {
            if ($sColumn === null) {
                $this->sTable   = null;
                $this->sColumn  = $sTable;
            } else {
                $this->sTable   = $sTable;
                $this->sColumn  = $sColumn;

            }

            $this->bPrimary    = false;
            $this->bGenerated    = false;
            $this->sDefault    = null;
            $this->sValue      = null;
            $this->sAlias      = null;
        }

        /**
         *
         * @return string|integer
         */
        abstract public function __toString();

        abstract public function toSQL(): string;

        public function toSQLLog(): string {
            return str_replace('Field_', '', get_class($this));
        }

        public function toSQLColumn(bool $bWithTable=true): string {
            if ($bWithTable) {
                if ($this->sAlias && $this->sAlias !== '') {
                    return implode('.', array($this->sAlias, $this->sColumn));
                }

                if ($this->sTable && $this->sTable !== '') {
                    return implode('.', array($this->sTable, $this->sColumn));
                }
            }

            return $this->sColumn;
        }

        /**
         * @param bool $bWithTable
         * @param bool $bAnyValue
         *
         * @return string
         */
        public function toSQLColumnForFields(bool $bWithTable = true, bool $bAnyValue = false): string {
            if ($bWithTable) {
                $sTableColumn = implode('.', [$this->sTable, $this->sColumn]);

                if ($this->sAlias && $this->sAlias !== '') {
                    $sTableColumn = implode('.', [$this->sAlias, $this->sColumn]);
                    $sAliasColumn = implode('_', [$this->sAlias, $this->sColumn]);
                    if ($bAnyValue) {
                        return implode(' ', ["ANY_VALUE($sTableColumn)", 'AS', $sAliasColumn]);
                    }

                    return implode(' ', [$sTableColumn, 'AS', $sAliasColumn]);
                }

                if ($this->sTable && $this->sTable !== '') {
                    if ($bAnyValue) {
                        return implode(' ', ["ANY_VALUE($sTableColumn)", 'AS', $this->sColumn]);
                    }

                    return $sTableColumn;
                }
            }

            if ($bAnyValue) {
                return implode(' ', ["ANY_VALUE(" . $this->sColumn . ")", 'AS', $this->sColumn]);
            }

            return $this->sColumn;
        }

        /**
         * @param bool $bWithTable
         * @param bool $bAnyValue
         *
         * @return string
         */
        public function toSQLColumnForSelect(bool $bWithTable = true, bool $bAnyValue = false): string {
            return $this->toSQLColumnForFields($bWithTable, $bAnyValue);
        }

        /**
         * @param bool $bWithTable
         *
         * @return string
         */
        public function toSQLColumnForCount(bool $bWithTable = true): string {
            if ($bWithTable) {
                return implode('.', array($this->sTable, $this->sColumn));
            }

            return $this->sColumn;
        }

        public function toSQLColumnForInsert(): string {
            return $this->toSQLColumnForFields(false);
        }

        public function toInfoArray():array {
            return [
                'name'  => $this->sColumn,
                'type'  => get_class($this)
            ];
        }

        /**
         * @param $sValue
         *
         * @return $this
         */
        public function setValue($sValue) {
            if ($sValue instanceof Table) {
                $sValue = $sValue->{$this->sColumn};
            }

            if ($sValue instanceof self) {
                $sValue = $sValue->getValue();
            }

            $this->sValue = $sValue;

            return $this;
        }

        public function applyDefault(): void {
            $this->setValue($this->sDefault);
        }

        public function setDefault($sDefault): void {
            $this->sDefault = $sDefault;
        }

        public function hasDefault(): bool {
            return $this->sDefault !== null;
        }

        public function isDefault(): bool {
            return $this->getValue() === $this->sDefault;
        }

        public function setAlias(string $sAlias): void {
            $this->sAlias = $sAlias;
        }

        public function hasAlias(): bool {
            return $this->sAlias !== null;
        }

        public function setGenerated(bool $bGenerated):void {
            $this->bGenerated = $bGenerated;
        }

        public function isGenerated(): bool {
            return $this->bGenerated;
        }

        public function setPrimary(bool $bPrimary):void {
            $this->bPrimary = $bPrimary;
        }

        public function isPrimary(): bool {
            return $this->bPrimary;
        }

        public function getValue() {            
            return $this->sValue;
        }

        public function getValueOrDefault() {
            return $this->hasValue() ? $this->sValue : $this->sDefault;
        }

        /**
         * @param Table|Field|mixed $mValue
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
            }

            if ($this->isNull()) {
                return false;           // My Value is null but comparator is not
            }

            return (string) $this === (string) $mValue;
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

        public function isNull(): bool {
            return $this->sValue === NULL;
        }

        public function hasValue(): bool {
            return !$this->isNull();
        }
        
        /**
         *
         * @param stdClass $oData
         */
        public function setValueFromData($oData): void {
            if (isset($oData->{$this->sColumn})) {
                $this->setValue($oData->{$this->sColumn});
            }
        }

        public function setValueFromArray(array $aData): void {
            if (array_key_exists($this->sColumn, $aData)) {
                $this->setValue($aData[$this->sColumn]);
            }
        }

        public function getTable(): ?Table {
            if ($this->sTableClass) {
                return new $this->sTableClass;
            }

            return null;
        }

        public function references(string $sTable, string $sField): void {
            $this->sReferenceTable = $sTable;
            $this->sReferenceField = $sField;
        }

        public function referencesTable(Table $oTable): bool {
            return $this->sReferenceTable === $oTable->getTitle();
        }

        public function hasReference(): bool {
            return $this->sReferenceTable !== null
                && $this->sReferenceField !== null;
        }

        public function referenceField(): ?string {
            return $this->sReferenceField;
        }
    }