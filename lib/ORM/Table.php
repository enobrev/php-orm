<?php
    namespace Enobrev\ORM;

    use function Enobrev\dbg;
    use Enobrev\SQLBuilder;
    use stdClass;
    use PDOStatement;

    class TableFieldNotFoundException extends TableException {}

    class Table {
        /** @var null|string  */
        protected $sTitle = null;

        /** @var null|stdClass  */
        public $oResult = null;

        /** @var string  */
        public $sKey = __CLASS__;

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
        public static function Field($sField, $sAlias = null) {
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
         * @return Tables
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
                    $oExisting = $oTable->getBy($oTable->$sPrimaryField);
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
         * @return static
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

        /**
         *
         * @param string $sTitle
         * @param bool   $bFromPDO
         */
        public function __construct($sTitle = '', $bFromPDO = false) {
            if ($this->bConstructed === false) {
                $this->bFromPDO = $bFromPDO;
                $this->oResult  = new stdClass();

                if (strlen($sTitle)) {
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
                if ($oField->hasDefault()) {
                    if (!$oField->hasValue()) {
                        $oField->applyDefault();
                    }
                }
            }
        }

        private function applyResult(): void {
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if (preg_match('/^[a-z][A-Z]/', $sProperty)) {
                    // Skip Vars - we just want properties from Queries - FIXME: This is definitely imperfect and relies upon convention
                    continue;
                }

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
        public function getFields() {
            $aFields = [];
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if ($this->$sProperty instanceof Field) {
                    $aFields[] =& $this->$sProperty;
                }
            }

            return $aFields;
        }

        /**
         * @return Field[]
         */
        public function getColumnsWithFields() {
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
        public function getPrimary() {
            $aPrimary = [];
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if ($this->$sProperty instanceof Field) {
                    if ($this->$sProperty->isPrimary()) {
                        $aPrimary[] =& $this->$sProperty;
                    }
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
        public function getFieldThatReferencesTable(Table $oTable) {
            $aProperties = get_object_vars($this);
            foreach(array_keys($aProperties) as $sProperty) {
                if ($this->$sProperty instanceof Field) {
                    if ($this->$sProperty->hasReference()) {
                        if ($this->$sProperty->referencesTable($oTable)) {
                            return $this->$sProperty;
                        }
                    }
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
            foreach ($this->getFields() as &$oField) {
                /** @var Field $oField */
                $oField->setValueFromArray($aData);
            }

            return $this;
        }

        /**
         * @param array $aData data_field => value
         * @param array $aMap  data_field => column
         * @param array $aOverride Data that overrides the map
         */
        public function mapArrayToFields(Array $aData, Array $aMap, Array $aOverride = []): void {
            $aMappedData = array();
            foreach($aMap as $sDataField => $mField) {
                if (isset($aData[$sDataField]) || array_key_exists($sDataField, $aData)) {
                    if ($mField instanceof Field) {
                        $mField = $mField->sColumn;
                    }

                    $aMappedData[$mField] = $aData[$sDataField];
                }
            }

            foreach($aOverride as $sField => $mData) {
                if ($mData instanceof Table) {
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
                if (isset($aData[$oPrimary->sColumn]) || array_key_exists($oPrimary->sColumn, $aData)) {
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
            return $this->getBy(...$this->getPrimary());
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

            if ($oSQL->hasConditions() == false) {
                throw new TableException('No Conditions Given');
            }

            $aQueryName = array();
            foreach($aFields as $oField) {
                $aQueryName[] = $oField->sColumn;
            }

            $sClass     = get_class($oTable);
            $aClass     = explode('\\', $sClass);
            $sQueryName = array_pop($aClass) . '.getBy.' . implode('_', $aQueryName);

            if ($oResult = Db::getInstance()->namedQuery($sQueryName, $oSQL)) {
                return $oTable->createFromPDOStatement($oResult);
            }

            return NULL;
        }

        /**
         * @param Field $oField
         * @return void
         */
        public function addField(Field $oField) {
            $oField->sTable      = $this->sTitle;
            $oField->sTableClass = get_class($this);

            $sField = $oField->sColumn;
            if (property_exists($this, $sField)) {
                $mExistingValue = $this->$sField;

                if ($mExistingValue !== null) {
                    $oField->setValue($mExistingValue);
                }

                if ($this->bFromPDO) {
                    $this->oResult->$sField = $mExistingValue;
                }
            }

            $this->$sField =& $oField;
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
                $oReturn = Db::getInstance()->namedQuery(get_class($this) . '.update',
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

        /**
         *
         * @return PDOStatement|bool
         * @throws DbDuplicateException
         * @throws DbException
         */
        public function delete() {
            if ($this->primaryHasValue()) {
                $oReturn = Db::getInstance()->namedQuery(get_class($this) . '.delete',
                    SQLBuilder::delete($this)->also($this->getPrimary())
                );

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

            Db::getInstance()->namedQuery(get_class($this) . '.insert',
                SQLBuilder::insert($this)
            );

            if ($bPrimaryAlreadySet) {
                $iLastInsertId = $this->getPrimaryValue();
            } else {
                $iLastInsertId = Db::getInstance()->getLastInsertId();
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
            if (count($aPrimary) == 1) {
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

        /**
         *
         * @return int
         * @throws DbDuplicateException
         * @throws DbException
         */
        public function upsert() {
            $this->preUpsert();
            Db::getInstance()->namedQuery(get_class($this) . '.upsert',
                SQLBuilder::upsert($this)
            );

            $iLastInsertId = Db::getInstance()->getLastInsertId();
            $this->updatePrimary($iLastInsertId);
            $this->postUpsert();

            return $iLastInsertId;
        }

        /**
         * @return \DateTime
         * */
        public function now() {
            return Db::getInstance()->getDate();
        }

        /**
         * @return array
         */
        public function toArray() {
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
        public function toSQLArray() {
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
        public function toInfoArray() {
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
        public function isPublic() {
            return true;
        }

    }