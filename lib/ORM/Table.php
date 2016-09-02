<?php
    namespace Enobrev\ORM;

    use stdClass;
    use MySQLi_Result;
    use Enobrev\SQL;

    class TableNamelessException extends TableException {}
    class TableFieldNotFoundException extends TableException {}

    class Table {
        /** @var string  */
        protected $sTitle;

        /** @var stdClass  */
        public $oResult;

        /** @var string  */
        public $sKey = __CLASS__;

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
         * @static
         * @param MySQLi_Result $oResult
         * @return Table
         */
        public static function createFromResult(MySQLi_Result $oResult) {
            /** @var Table $oTable */
            $oTable = new static;
            $oTable->setFromResult($oResult);
            return $oTable;
        }

        /**
         * @param array $aData
         * @return Table
         */
        public static function createFromArray(array $aData) {
            /** @var Table $oTable */
            $oTable = new static;
            $oTable->setFromArray($aData);
            return $oTable;
        }

        /**
         * @static
         * @param stdClass $oObject
         * @return Table
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
         * @param array $aData
         * @param array $aMap
         * @param array $aOverride
         * @return Table
         */
        public static function createAndUpdateFromMap(Array $aData, Array $aMap, Array $aOverride = []) {
            /** @var Table $oTable */
            $oTable = new static;
            $oTable->mapArrayToFields($aData, $aMap, $aOverride);

            if ($oTable->primaryHasValue()) {
                /** @var Table $oExisting */
                $oExisting = $oTable->getByPrimary();
                if ($oExisting instanceof static) {
                    $oExisting->mapArrayToFields($aData, $aMap, $aOverride);
                    $oExisting->update();
                    return $oExisting;
                }
            }

            $oTable->insert();
            return $oTable;
        }

        /**
         * @param array $aData
         * @return Table
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

        protected $bConstructed = false;

        /**
         * @throws TableNamelessException
         * @param string $sTitle
         */
        public function __construct($sTitle = '') {
            if ($this->bConstructed === false) {
                $this->oResult = new stdClass();

                if (strlen($sTitle)) {
                    $this->sTitle = $sTitle;
                }

                if (strlen($this->sTitle) == 0) {
                    throw new TableNamelessException;
                }

                $this->init();
                $this->applyDefaults();
            }

            $this->bConstructed = true;
        }

        protected function init() {
        }

        /**
         * @return bool
         */
        public function applyDefaults() {
            /** @var Field $oField */
            foreach ($this->getFields() as $oField) {
                if ($oField->hasDefault()) {
                    if (!$oField->hasValue()) {
                        $oField->applyDefault();
                    }
                }
            }
        }


        /**
         * @return string
         */
        public function getTitle() {
            return $this->sTitle;
        }

        /**
         * @param Field $oField
         * @return bool
         */
        public function fieldChanged(Field $oField) {
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
        public function changed() {
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

        public function getPrimaryFieldNames() {
            $aNames = [];
            foreach($this->getPrimary() as $oPrimary) {
                $aNames[] = $oPrimary->sColumn;
            }

            return $aNames;
        }

        /**
         * @param Table $oTable
         * @return Field
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
        }

        /**
         *
         * @param MySQLi_Result $oResult
         * @return Table
         */
        public function setFromResult(MySQLi_Result $oResult) {
            return $this->setFromObject($oResult->fetch_object());
        }

        /**
         *
         * @param stdClass $oData
         * @return Table
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
         * @return Table
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
        public function mapArrayToFields(Array $aData, Array $aMap, Array $aOverride = []) {
            $aMappedData = array();
            foreach($aMap as $sDataField => $mField) {
                if (isset($aData[$sDataField])) {
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
        public function setPrimaryFromArray(Array $aData) {
            foreach ($this->getPrimary() as $oPrimary) {
                if (isset($aData[$oPrimary->sColumn])) {
                    $this->{$oPrimary->sColumn}->setValue($aData[$oPrimary->sColumn]);
                }
            }
        }

        /**
         *
         * @return Table
         */
        public function getByPrimary() {
            return $this->getBy(...$this->getPrimary());
        }

        /**
         * @param Field[] $aFields
         * @return Table|null
         * @throws TableException
         */
        protected static function getBy(...$aFields) {
            $oObject = NULL;
            $oConditions = SQL::also($aFields);
            if ($oConditions->count() == 0) {
                throw new TableException('No Conditions Given');
            }

            $aQueryName = array();
            foreach($aFields as $oField) {
                $aQueryName[] = $oField->sColumn;
            }

            /** @var Table $oTable */
            $oTable     = new static;
            $aClass     = explode('\\', get_class($oTable));
            $sQueryName = array_pop($aClass) . '.getBy.' . implode('_', $aQueryName);

            if ($oResult = Db::getInstance()->namedQuery($sQueryName, SQL::select($oTable, $oConditions))) {
                if ($oResult->num_rows > 0) {
                    $oTable->setFromObject($oResult->fetch_object());
                    return $oTable;
                }
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

            $mExistingValue = $this->{$oField->sColumn};
            if ($mExistingValue) {
                $oField->setValue($mExistingValue);
            }

            $this->{$oField->sColumn} =& $oField;
        }

        /**
         * @param Field[] ...$aFields
         * @return void
         */
        public function addFields(...$aFields) {
            foreach ($aFields as $oField) {
                $this->addField($oField);
            }
        }

        /**
         * @param Field $oField
         * @return void
         */
        public function addPrimary(Field $oField) {
            $this->addField($oField);
            $oField->setPrimary(true);
        }

        /**
         * @param Field[] $aFields
         * @return void
         */
        public function addPrimaries(...$aFields) {
            foreach ($aFields as $oField) {
                $this->addPrimary($oField);
            }
        }

        protected function preUpdate() {}
        protected function postUpdate() {}

        /**
         *
         * @return MySQLi_Result|bool
         */
        public function update() {
            if (!$this->changed()) {
                return false;
            }

            if ($this->primaryHasValue()) {
                $sFromTable = 'Table';
                if ($sFromTable === NULL) {
                    $sFromTable = get_class($this);
                }

                $oConditions = Conditions::also($this->getPrimary());

                $this->preUpdate();
                $oReturn = Db::getInstance()->namedQuery($sFromTable . '.update',
                    SQL::update($this, $oConditions)
                );
                $this->postUpdate();

                return $oReturn;
            }

            return false;
        }

        public function primaryHasValue() {
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
         * @return MySQLi_Result
         */
        public function delete() {
            if ($this->primaryHasValue()) {
                $oConditions = Conditions::also($this->getPrimary());

                $oReturn = Db::getInstance()->namedQuery(get_class($this) . '.delete',
                    SQL::delete($this, $oConditions)
                );

                return $oReturn;
            }

            return false;
        }

        protected function preInsert() {}
        protected function postInsert() {}

        /**
         *
         * @return int
         */
        public function insert() {
            $bPrimaryAlreadySet = $this->primaryHasValue();

            $this->preInsert();

            Db::getInstance()->namedQuery(get_class($this) . '.insert',
                SQL::insert($this)
            );

            $iLastInsertId = Db::getInstance()->getLastInsertId();
            if (!$bPrimaryAlreadySet) {
                $this->updatePrimary($iLastInsertId);
            }

            $this->postInsert();

            return $iLastInsertId;
        }

        /**
         * @param int $iLastInsertId
         */
        private function updatePrimary($iLastInsertId) {
            $aPrimary = $this->getPrimary();
            if (count($aPrimary) == 1) {
                /** @var Field\Id $oField */
                $oField =& $aPrimary[0];
                if ( $oField instanceof Field\Id
                ||   $oField instanceof Field\Integer ) {
                    $oField->setValue($iLastInsertId);
                }
            }
        }

        protected function preUpsert() {}
        protected function postUpsert() {}

        /**
         *
         * @return int
         */
        public function upsert() {
            $this->preUpsert();
            Db::getInstance()->namedQuery(get_class($this) . '.upsert',
                SQL::upsert($this)
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

        public function toHash() {
            return hash('sha1', json_encode($this->toArray()));
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