<?php
    namespace Enobrev\ORM;

    use DateTime;
    use Enobrev\ORM\Condition\ConditionInterface;
    use Exception;
    use PDOStatement;
    use stdClass;

    use Enobrev\ORM\Exceptions\TableException;
    use Enobrev\ORM\Exceptions\TableFieldNotFoundException;
    use Enobrev\SQLBuilder;

    abstract class Table {
        protected string $sTitle = '';

        /** @var Field[] */
        private array $aFields = [];

        public ?stdClass $oResult;

        /**
         * @return Db
         * @throws Exceptions\DbException
         */
        protected static function Db(): Db {
            return Db::getInstance();
        }

        /**
         * @param string $sTableClass
         *
         * @return static
         */
        public static function getInstanceFromName(string $sTableClass) {
            $sTable = Tables::getNamespacedTableClassName($sTableClass);
            return new $sTable;
        }

        /**
         * @param string      $sField
         * @param string|null $sAlias
         *
         * @return Field
         */
        public static function Field(string $sField, ?string $sAlias = null): Field {
            $oTable = new static;

            if ($oTable->$sField instanceof Field) {
                if ($sAlias !== null) {
                    $oTable->$sField->sAlias = $sAlias;
                }

                return $oTable->$sField;
            }

            assert(false, new TableFieldNotFoundException($sField));
        }

        abstract public static function getTables();

        /**
         * @param array $aData
         *
         * @return static
         */
        public static function createFromArray(array $aData) {
            $oTable = new static;
            $oTable->setFromArray($aData);
            return $oTable;
        }

        /**
         * @param stdClass|null $oObject
         *
         * @return static|null
         */
        public static function createFromObject(?stdClass $oObject = NULL) {
            if ($oObject instanceof stdClass === false) {
                return NULL;
            }

            $oTable = new static;
            $oTable->setFromObject($oObject);
            return $oTable;
        }

        /**
         * @param array       $aData
         * @param array       $aMap
         * @param array       $aOverride
         * @param string|null $sPrimaryField
         *
         * @return static
         * @throws Exceptions\DbException
         */
        public static function createAndUpdateFromMap(array $aData, array $aMap, array $aOverride = [], ?string $sPrimaryField = null) {
            $oTable = new static;
            $oTable->mapArrayToFields($aData, $aMap, $aOverride);

            /** @var Table $oExisting */
            $oExisting = null;

            if ($sPrimaryField) {
                if ($oTable->$sPrimaryField->hasValue()) {
                    $oExisting = static::getBy($oTable->$sPrimaryField);
                }
            } else if ($oTable->primaryHasValue()) {
                $oExisting = $oTable->getByPrimary();
            }

            if ($oExisting instanceof static) {
                $oExisting->mapArrayToFields($aData, $aMap, $aOverride);
                $oExisting->update();

                return $oExisting;
            }

            $oTable->insert();
            return $oTable;
        }

        /**
         * @param array $aData
         *
         * @return static
         * @throws Exceptions\DbException
         */
        public static function createAndUpdate(array $aData) {
            $oTable = new static;
            $oTable->setFromArray($aData);

            if ($oTable->primaryHasValue()) {
                $oExisting = $oTable->getByPrimary();
                if ($oExisting instanceof static) {
                    $oExisting->setFromArray($aData);
                    $oExisting->update();
                    return $oExisting;
                }
            }

            $oTable->insert();
            return $oTable;
        }

        protected bool $bConstructed = false;

        protected bool $bFromPDO     = false;

        /**
         * @param PDOStatement $oResults
         * @return static|null
         */
        public function createFromPDOStatement(PDOStatement $oResults): ?Table {
            $oResponse = $oResults->fetchObject(static::class, [$this->getTitle(), true]);
            if ($oResponse) {
                return $oResponse;
            }

            return null;
        }

        /**
         * @param static $oThis
         * @noinspection PhpMissingParamTypeInspection
         */
        private static function setOriginalProperties($oThis): void {
            if (self::$aOriginalProperties === null) {
                self::$aOriginalProperties = array_keys(get_object_vars($oThis));
            }
        }

        private static ?array $aOriginalProperties = null;

        /**
         *
         * @param string $sTitle
         * @param bool   $bFromPDO
         */
        public function __construct($sTitle = '', $bFromPDO = false) {
            self::setOriginalProperties($this);

            if ($this->bConstructed === false) {
                $this->bFromPDO = $bFromPDO;
                $this->oResult  = new stdClass();

                if ($sTitle !== '') {
                    $this->sTitle = $sTitle;
                }

                $this->init();
                $this->applyDefaults();
                $this->applyPropertiesToResult();
            }

            $this->bConstructed = true;
        }

        abstract protected function init(): void;

        public function applyDefaults(): void {
            foreach ($this->getFields() as $oField) {
                if ($oField->hasDefault() && !$oField->hasValue()) {
                    $oField->applyDefault();
                }
            }
        }

        // Applies non-table properties from results to oResult
        private function applyPropertiesToResult(): void {
            $aProperties      = get_object_vars($this);
            $aExtraResultKeys = array_diff(array_keys($aProperties), self::$aOriginalProperties ?? []);
            foreach($aExtraResultKeys as $sProperty) {
                if ($this->$sProperty instanceof Field === false) {
                    $this->oResult->$sProperty = $this->$sProperty;
                }
            }
        }

        public function getTitle(): ?string {
            return $this->sTitle;
        }

        /**
         * @param Field             $oField
         * @param Table|Field|mixed $mValue
         *
         * @return bool
         */
        public function fieldChangedTo(Field $oField, $mValue): bool {
            if (!$this->fieldChanged($oField)) {
                return false;
            }

            return $oField->is($mValue);
        }

        /**
         * @param Field             $oField
         * @param Table|Field|mixed $mValue
         *
         * @return bool
         */
        public function fieldChangedFrom(Field $oField, $mValue): bool {
            if (!$this->fieldChanged($oField)) {
                return false;
            }

            return $oField->was($mValue);
        }

        public function fieldChanged(Field $oField): bool {
            if (!$this->oResult) {
                return true;
            }

            if (!property_exists($this->oResult, $oField->sColumn)) {
                return true;
            }

            if (!$oField->is($this->oResult->{$oField->sColumn})) {
                return true;
            }

            return false;
        }

        public function changed(): bool {
            if (!property_exists($this, 'oResult')) {
                return true;
            }

            foreach($this->getFields() as $oField) {
                if ($this->fieldChanged($oField)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @return Field[]
         */
        public function getFields(): array {
            return $this->aFields;
        }

        /**
         * @return Field[]
         */
        public function getColumnsWithFields(): array {
            $aFields = [];
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if ($this->$sProperty instanceof Field) {
                    $aFields[$sProperty] =& $this->$sProperty;
                }
            }

            return $aFields;
        }

        /**
         * @return Field[]
         */
        public function getNonGeneratedFields(): array {
            $aFields = [];
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if (($this->$sProperty instanceof Field) && $this->$sProperty->isGenerated() === false) {
                    $aFields[] =& $this->$sProperty;
                }
            }

            return $aFields;
        }

        /**
         * @return Field[]
         */
        public function getPrimary(): array {
            $aPrimary = [];
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if (($this->$sProperty instanceof Field) && $this->$sProperty->isPrimary()) {
                    $aPrimary[] =& $this->$sProperty;
                }
            }

            return $aPrimary;
        }

        /**
         * @return string[]
         */
        public function getPrimaryFieldNames(): array {
            $aNames = [];
            foreach($this->getPrimary() as $oPrimary) {
                $aNames[] = $oPrimary->sColumn;
            }

            return $aNames;
        }

        public function getFieldThatReferencesTable(Table $oTable): ?Field {
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if (($this->$sProperty instanceof Field) &&
                    $this->$sProperty->hasReference() &&
                    $this->$sProperty->referencesTable($oTable)) {
                        return $this->$sProperty;
                    }
            }

            return null;
        }

        /**
         * @param stdClass $oData
         *
         * @return $this
         */
        public function setFromObject(stdClass $oData) {
            $this->oResult = $oData;
            foreach ($this->getFields() as $oField) {
                $oField->setValueFromData($oData);
            }

            return $this;
        }

        /**
         * @param array $aData
         *
         * @return $this
         */
        public function setFromArray(array $aData) {
            $aFields     = $this->getFields();
            $aFieldData  = array_intersect_key($aData, $aFields);
            foreach($aFieldData as $sField => $mValue) {
                $this->$sField->setValue($mValue);
            }

            return $this;
        }

        /**
         * @param static $oTable
         */
        public function setFromTable(Table $oTable) {
            foreach($oTable->getFields() as $oField) {
                $sField = $oField->sColumn;
                if ($this->$sField instanceof Field) {
                    $this->$sField->setValue($oField);
                }
            }

            $this->applyPropertiesToResult();
        }

        /**
         * @param array $aData      data_field => value
         * @param array $aMap       data_field => column
         * @param array $aOverride  Data that overrides the map
         */
        public function mapArrayToFields(array $aData, array $aMap, array $aOverride = []): void {
            $aMatchedKeys = array_intersect_key($aMap, $aData);
            $aMappedData  = [];
            foreach($aMatchedKeys as $sDataField => $mField) {
                if ($mField instanceof Field) {
                    $mField = $mField->sColumn;
                }

                $aMappedData[$mField] = $aData[$sDataField];
            }

            foreach($aOverride as $sField => $mData) {
                if ($mData instanceof self) {
                    $mData = $mData->$sField;
                }

                if ($mData instanceof Field) {
                    $mData = $mData->getValue();
                }

                $aMappedData[$sField] = $mData;
            }

            if (count($aMappedData)) {
                $this->setFromArray($aMappedData);
            }
        }

        public function setPrimaryFromArray(array $aData): void {
            foreach ($this->getPrimary() as $oPrimary) {
                if (array_key_exists($oPrimary->sColumn, $aData)) {
                    $this->{$oPrimary->sColumn}->setValue($aData[$oPrimary->sColumn]);
                }
            }
        }

        /**
         * @return $this|null
         * @throws Exceptions\DbException
         */
        public function getByPrimary() {
            return self::getBy(...$this->getPrimary());
        }

        /**
         * @param ConditionInterface[]|Conditions[]|Field[] $aFields
         *
         * @return static|null
         * @throws Exceptions\DbException
         */
        public static function getBy(...$aFields) {
            $oTable = new static;
            $oSQL   = SQLBuilder::select($oTable)->also($aFields);

            assert($oSQL->hasConditions() !== false, new TableException('No Conditions Given'));

            $aQueryName = [];
            foreach($aFields as $oField) {
                $aQueryName[] = $oField->sColumn;
            }

            $sClass     = get_class($oTable);
            $aClass     = explode('\\', $sClass);
            $sQueryName = array_pop($aClass) . '.getBy.' . implode('_', $aQueryName);

            if ($oResult = static::Db()->namedQuery($sQueryName, $oSQL)) {
                return $oTable->createFromPDOStatement($oResult);
            }

            return NULL;
        }

        /**
         * @param Field $oField
         */
        public function addField(Field $oField): void {
            $oField->sTable      = $this->sTitle;
            $oField->sTableClass = static::class;
            $sField              = $oField->sColumn;

            if (isset($this->$sField)) {
                $oField->setValue($this->$sField);
            }

            if ($this->bFromPDO && property_exists($this, $sField) && $this->$sField instanceof Field === false) {
                $this->oResult->$sField = $this->$sField;
            }

            $this->$sField =& $oField;
            $this->aFields[$sField] =& $oField;
        }

        /**
         * @param Field[] $aFields
         */
        public function addFields(...$aFields): void {
            foreach ($aFields as $oField) {
                $this->addField($oField);
            }
        }

        public function addPrimary(Field $oField): void {
            $this->addField($oField);
            $oField->setPrimary(true);
        }

        /**
         * @param Field[] $aFields
         * @return void
         */
        public function addPrimaries(...$aFields): void {
            foreach ($aFields as $oField) {
                $this->addPrimary($oField);
            }
        }

        public function addGenerated(Field $oField): void {
            $this->addField($oField);
            $oField->setGenerated(true);
        }

        protected function preUpdate(): void {}
        protected function postUpdate(): void {}

        /**
         * @return PDOStatement|null
         * @throws Exceptions\DbException
         */
        public function update(): ?PDOStatement {
            if ($this->primaryHasValue()) {
                $this->preUpdate();
            }

            if (!$this->changed()) {
                return null;
            }

            if ($this->primaryHasValue()) {
                $oReturn = static::Db()->namedQuery(get_class($this) . '.update',
                    SQLBuilder::update($this)->also($this->getPrimary())
                );
                $this->postUpdate();

                return $oReturn;
            }

            return null;
        }

        public function primaryHasValue(): bool {
            $aPrimary = $this->getPrimary();
            foreach($aPrimary as $oPrimary) {
                if (!$oPrimary->hasValue()) {
                    return false;
                }
            }

            return true;
        }

        protected function preDelete(): void {}
        protected function postDelete(): void {}

        /**
         * @return PDOStatement|null
         * @throws Exceptions\DbException
         */
        public function delete(): ?PDOStatement {
            if ($this->primaryHasValue()) {
                $this->preDelete();
                $oReturn = static::Db()->namedQuery(get_class($this) . '.delete',
                    SQLBuilder::delete($this)->also($this->getPrimary())
                );
                $this->postDelete();
                return $oReturn;
            }

            return null;
        }

        protected function preInsert(): void {}
        protected function postInsert(): void {}

        /**
         * @return int|mixed|null
         * @throws Exceptions\DbException
         */
        public function insert() {
            $this->preInsert();

            $bPrimaryAlreadySet = $this->primaryHasValue();

            static::Db()->namedQuery(get_class($this) . '.insert',
                SQLBuilder::insert($this)
            );

            if ($bPrimaryAlreadySet) {
                $iLastInsertId = $this->getPrimaryValue();
            } else {
                $iLastInsertId = static::Db()->getLastInsertId();
                $this->updatePrimary($iLastInsertId);
            }

            $this->postInsert();

            return $iLastInsertId;
        }

        private function updatePrimary(int $iLastInsertId): void {
            $aPrimary = $this->getPrimary();
            if (count($aPrimary) === 1) {
                $oField =& $aPrimary[0];
                if ( $oField instanceof Field\Id ) {
                    $oField->setValue($iLastInsertId);
                }
            }
        }

        private function getPrimaryValue() {
            $aPrimary = $this->getPrimary();
            if (count($aPrimary) > 1) {
                $aValue = [];
                foreach ($aPrimary as $oPrimary) {
                    $aValue[$oPrimary->sColumn] = $oPrimary->getValue();
                }
                return $aValue;
            }

            return $aPrimary[0]->getValue();
        }

        protected function preUpsert(): void {}
        protected function postUpsert(): void {}
        protected function postUpsertInsert(): void {}
        protected function postUpsertUpdate(): void {}

        /**
         * @return int
         * @throws Exceptions\DbException
         */
        public function upsert(): int {
            $this->preUpsert();
            static::Db()->namedQuery(get_class($this) . '.upsert',
                SQLBuilder::upsert($this)
            );

            $iLastInsertId = static::Db()->getLastInsertId();
            $this->updatePrimary($iLastInsertId);
            $this->postUpsert();

            if (Db::wasUpsertInserted()) {
                $this->postUpsertInsert();
            } else {
                $this->postUpsertUpdate();
            }

            return $iLastInsertId;
        }

        /**
         * @return DateTime
         * @throws Exceptions\DbException
         * @throws Exception
         */
        public function now(): DateTime {
            return static::Db()->getDate();
        }

        /**
         * @return array
         */
        public function toArray(): array {
            $aArray = [];

            foreach ($this->getFields() as $oField) {
                $aArray[$oField->sColumn] = (string) $oField;
            }

            return $aArray;
        }

        public function toHash(): string {
            return hash('sha1', (string) json_encode($this->toArray()));
        }

        /**
         *
         * @return string[]
         */
        public function toSQLArray(): array {
            $aArray = [];

            foreach ($this->getFields() as $oField) {
                if (!$oField->isNull()) {
                    $aArray[$oField->sColumn] = $oField->toSQL();
                }
            }

            return $aArray;
        }

        public function toInfoArray(): array {
            $aFields = [];

            foreach($this->getFields() as $oField) {
                $aFields[$oField->sColumn] = $oField->toInfoArray();
            }

            return $aFields;
        }

        public function isPublic(): bool {
            return true;
        }

    }