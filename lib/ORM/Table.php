<?php
    namespace Enobrev\ORM;

    use DateTime;
    use Exception;
    use PDOStatement;
    use stdClass;

    use Enobrev\SQLBuilder;

    class TableFieldNotFoundException extends TableException {}

    class Table {
        /** @var null|string  */
        protected $sTitle;

        /** @var Field[] */
        private $aFields = [];

        /** @var null|stdClass  */
        public $oResult;

        /** @var string  */
        public $sKey = __CLASS__;

        /**
         * @return Db
         * @throws DbException
         */
        protected static function Db(): Db {
            return Db::getInstance();
        }

        /**
         * @param string $sTableClass
         * @return Table
         */
        public static function getInstanceFromName(string $sTableClass): self {
            $sTable = Tables::getNamespacedTableClassName($sTableClass);
            return new $sTable;
        }

        /**
         * @param string $sField
         * @param string|null $sAlias
         * @return Field
         * @throws TableFieldNotFoundException
         */
        public static function Field($sField, $sAlias = null): Field {
            $oTable = new static;

            if ($oTable->$sField instanceof Field) {
                if ($sAlias !== null) {
                    $oTable->$sField->sAlias = $sAlias;
                }

                return $oTable->$sField;
            }

            throw new TableFieldNotFoundException($sField);
        }

        /**
         * @throws TableException
         */
        public static function getTables() {
            throw new TableException('This Method Should Have Been Overridden');
        }

        /**
         * @param array $aData
         * @return static
         */
        public static function createFromArray(array $aData) {
            /** @var Table $oTable */
            $oTable = new static;
            $oTable->setFromArray($aData);
            return $oTable;
        }

        /**
         * @param stdClass $oObject
         * @return null|static
         */
        public static function createFromObject(stdClass $oObject = NULL) {
            if ($oObject instanceof stdClass === false) {
                return NULL;
            }

            /** @var Table $oTable */
            $oTable = new static;
            $oTable->setFromObject($oObject);
            return $oTable;
        }

        /**
         * @param array      $aData
         * @param array      $aMap
         * @param array      $aOverride
         * @param string     $sPrimaryField
         * @return static
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TableException
         */
        public static function createAndUpdateFromMap(Array $aData, Array $aMap, Array $aOverride = [], string $sPrimaryField = null) {
            /** @var Table $oTable */
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
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TableException
         */
        public static function createAndUpdate(Array $aData) {
            /** @var Table $oTable */
            $oTable = new static;
            $oTable->setFromArray($aData);

            if ($oTable->primaryHasValue()) {
                /** @var Table $oExisting */
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

        /** @var bool  */
        protected $bConstructed = false;

        /** @var bool  */
        protected $bFromPDO     = false;

        /**
         * @param PDOStatement $oResults
         * @return static|null
         */
        public function createFromPDOStatement(PDOStatement $oResults): ?Table {
            $oResponse = $oResults->fetchObject(get_class($this), [$this->getTitle(), true]);
            if ($oResponse) {
                return $oResponse;
            }

            return null;
        }

        private static function setOriginalProperties($oThis) {
            if (self::$aOriginalProperties === null) {
                self::$aOriginalProperties = array_keys(get_object_vars($oThis));
            }
        }

        private static $aOriginalProperties = null;

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
                $this->applyResult();
            }

            $this->bConstructed = true;
        }

        protected function init(): void {
        }

        public function applyDefaults(): void {
            /** @var Field $oField */
            foreach ($this->getFields() as $oField) {
                if ($oField->hasDefault() && !$oField->hasValue()) {
                    $oField->applyDefault();
                }
            }
        }

        // Applies non-table properties from results to oResult
        private function applyResult(): void {
            $aProperties      = get_object_vars($this);
            $aExtraResultKeys = array_diff(array_keys($aProperties), self::$aOriginalProperties);
            foreach($aExtraResultKeys as $sProperty) {
                if ($this->$sProperty instanceof Field === false) {
                    $this->oResult->$sProperty = $this->$sProperty;
                }
            }
        }

        /**
         * @return null|string
         */
        public function getTitle(): ?string {
            return $this->sTitle;
        }

        /**
         * @param Field $oField
         * @return bool
         */
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

        /**
         * @return bool
         */
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
         *
         * @param stdClass $oData
         * @return static
         */
        public function getNonGeneratedFields() {
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

        public function getPrimaryFieldNames(): array {
            $aNames = [];
            foreach($this->getPrimary() as $oPrimary) {
                $aNames[] = $oPrimary->sColumn;
            }

            return $aNames;
        }

        /**
         * @param Table $oTable
         * @return null|Field
         */
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
         *
         * @param stdClass $oData
         * @return static
         */
        public function setFromObject(stdClass $oData) {
            $this->oResult = $oData;
            foreach ($this->getFields() as &$oField) {
                /** @var Field $oField */
                $oField->setValueFromData($oData);
            }

            return $this;
        }

        /**
         *
         * @param array $aData
         * @return static
         */
        public function setFromArray(Array $aData) {
            $aFields     = $this->getFields();
            $aFieldData  = array_intersect_key($aData, $aFields);
            foreach($aFieldData as $sField => $mValue) {
                $this->$sField->setValue($mValue);
            }

            return $this;
        }

        /**
         * @param array $aData data_field => value
         * @param array $aMap  data_field => column
         * @param array $aOverride Data that overrides the map
         */
        public function mapArrayToFields(Array $aData, Array $aMap, Array $aOverride = []): void {
            $aMatchedKeys = array_intersect_key($aMap, $aData);
            $aMappedData  = array();
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

        /**
         *
         * @param array $aData
         */
        public function setPrimaryFromArray(Array $aData): void {
            foreach ($this->getPrimary() as $oPrimary) {
                if (array_key_exists($oPrimary->sColumn, $aData)) {
                    $this->{$oPrimary->sColumn}->setValue($aData[$oPrimary->sColumn]);
                }
            }
        }

        /**
         * @return static|null
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TableException
         */
        public function getByPrimary() {
            return self::getBy(...$this->getPrimary());
        }

        /**
         * @param Field[] $aFields
         * @return static|null
         * @throws DbDuplicateException
         * @throws DbException
         * @throws TableException
         */
        public static function getBy(...$aFields) {
            /** @var Table $oTable */
            $oTable = new static;
            $oSQL   = SQLBuilder::select($oTable)->also($aFields);

            if ($oSQL->hasConditions() === false) {
                throw new TableException('No Conditions Given');
            }

            $aQueryName = array();
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
         * @return void
         */
        public function addField(Field $oField): void {
            $oField->sTable      = $this->sTitle;
            $oField->sTableClass = $this->sKey;
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
         * @param Field[] ...$aFields
         * @return void
         * @psalm-suppress InvalidArgument
         */
        public function addFields(...$aFields): void {
            foreach ($aFields as $oField) {
                $this->addField($oField);
            }
        }

        /**
         * @param Field $oField
         * @return void
         */
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

        /**
         * @param Field $oField
         * @return void
         */
        public function addGenerated(Field $oField): void {
            $this->addField($oField);
            $oField->setGenerated(true);
        }

        protected function preUpdate():void {}
        protected function postUpdate(): void {}

        /**
         *
         * @return PDOStatement|bool
         * @throws DbDuplicateException
         * @throws DbException
         */
        public function update() {
            if (!$this->changed()) {
                return false;
            }

            if ($this->primaryHasValue()) {
                $this->preUpdate();
                $oReturn = static::Db()->namedQuery(get_class($this) . '.update',
                    SQLBuilder::update($this)->also($this->getPrimary())
                );
                $this->postUpdate();

                return $oReturn;
            }

            return false;
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
         *
         * @return PDOStatement|bool
         * @throws DbDuplicateException
         * @throws DbException
         */
        public function delete() {
            if ($this->primaryHasValue()) {
                $this->preDelete();
                $oReturn = static::Db()->namedQuery(get_class($this) . '.delete',
                    SQLBuilder::delete($this)->also($this->getPrimary())
                );
                $this->postDelete();
                return $oReturn;
            }

            return false;
        }

        protected function preInsert(): void {}
        protected function postInsert(): void {}

        /**
         *
         * @return int|mixed
         * @throws DbDuplicateException
         * @throws DbException
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

        /**
         * @param int $iLastInsertId
         */
        private function updatePrimary(int $iLastInsertId): void {
            $aPrimary = $this->getPrimary();
            if (count($aPrimary) === 1) {
                /** @var Field\Id $oField */
                $oField =& $aPrimary[0];
                if ( $oField instanceof Field\Id ) {
                    $oField->setValue($iLastInsertId);
                }
            }
        }

        /**
         * @return array|mixed
         */
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
         *
         * @return int
         * @throws DbDuplicateException
         * @throws DbException
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
         *
         * @throws Exception
         * @throws DbException
         */
        public function now(): DateTime {
            return static::Db()->getDate();
        }

        /**
         * @return array
         */
        public function toArray(): array {
            $aArray = array();

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
         * @return String[]
         */
        public function toSQLArray(): array {
            $aArray = array();

            /** @var Field $oField */
            foreach ($this->getFields() as $oField) {
                if (!$oField->isNull()) {
                    $aArray[$oField->sColumn] = $oField->toSQL();
                }
            }

            return $aArray;
        }

        /**
         * @return array[]
         */
        public function toInfoArray(): array {
            $aFields = array();

            /** @var Field $oField */
            foreach($this->getFields() as $oField) {
                $aFields[$oField->sColumn] = $oField->toInfoArray();
            }

            return $aFields;
        }

        /**
         * @return bool
         */
        public function isPublic(): bool {
            return true;
        }

    }