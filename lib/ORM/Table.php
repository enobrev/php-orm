<?php
    namespace Enobrev\ORM;

    use stdClass;
    use MySQLi_Result;
    use Enobrev\SQL;

    class TableNamelessException extends TableException {}
    class TableFieldNotFoundException extends TableException {}

    class Table {
        /**
         *
         * @var string
         */
        protected $sTitle;
        
        /**
         *
         * @var Fields
         */
        public $Fields;

        /**
         *
         * @var Fields
         */
        public $Primary;
        
        /**
         *
         * @var stdClass
         */
        public $oResult;

        /**
         * @var Db
         */
        protected $DB = NULL;

        public $sKey = __CLASS__;

        /**
         * @param string $sField
         * @param string|null $sAlias
         * @return Field
         * @throws TableFieldNotFoundException
         */
        public static function Field($sField, $sAlias = null) {
            $oTable = new static;
            $oField = $oTable->$sField;
            
            if ($oField instanceof Field) {
                if ($sAlias !== null) {
                    $oField->sAlias = $sAlias;
                }

                return $oField;
            }
            
            throw new TableFieldNotFoundException($sField);
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
         * @return Table
         */
        public static function createAndUpdateFromMap(Array $aData, Array $aMap) {
            /** @var Table $oTable */
            $oTable = new static;
            $oTable->mapArrayToFields($aData, $aMap);

            if ($oTable->Primary->hasValue()) {
                /** @var Table $oExisting */
                $oExisting = $oTable->getByPrimary();
                if ($oExisting instanceof static) {
                    $oExisting->mapArrayToFields($aData, $aMap);
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

            if ($oTable->Primary->hasValue()) {
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

                $this->Fields  = new Fields(array());
                $this->Primary = new Fields(array());
                $this->DB      = Db::getInstance();

                $this->init();

                $this->Fields->applyDefaults();
            }

            $this->bConstructed = true;
        }
        
        protected function init() {
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

            foreach($this->Fields as $oField) {
                if ($this->fieldChanged($oField)) {
                    return true;
                }
            }

            return false;
        }

        /**
         * @param string $sField
         *
         * @return Field
         * @throws TableFieldNotFoundException
         */
        public function __get($sField) {
            $oField = $this->Fields->$sField;
            
            if ($oField instanceof Field) {
                return $oField;
            } else {
                throw new TableFieldNotFoundException($sField);
            }
        }

        /**
         * @param string $sField
         * @param mixed  $sValue
         *
         * @throws TableFieldNotFoundException
         */
        public function __set($sField, $sValue) {
            if (!$this->bConstructed) {
                $this->__construct();
            }

            if ($this->Fields->$sField instanceof Field) {
                $this->Fields->$sField->setValue($sValue);
            } else {
                throw new TableFieldNotFoundException($sField);
            }
        }
        
        /**
         *
         * @param MySQLi_Result $oResult
         */
        public function setFromResult(MySQLi_Result $oResult) { 
            $this->setFromObject($oResult->fetch_object());
        }

        /**
         *
         * @param stdClass $oData
         */
        public function setFromObject(stdClass $oData) {
            $this->oResult = $oData;            
            foreach ($this->Fields as &$oField) {            
                /** @var Field $oField */
                $oField->setValueFromData($oData);
            }
        }

        /**
         *
         * @param array $aData
         */
        public function setFromArray(Array $aData) {
            foreach ($this->Fields as &$oField) {
                /** @var Field $oField */
                $oField->setValueFromArray($aData);
            }
        }

        /**
         * @param array $aData data_field => value
         * @param array $aMap  data_field => column
         */
        public function mapArrayToFields(Array $aData, Array $aMap) {
            $aMappedData = array();
            foreach($aMap as $sDataField => $mField) {
                if (isset($aData[$sDataField])) {
                    if ($mField instanceof Field) {
                        $mField = $mField->sColumn;
                    }

                    $aMappedData[$mField] = $aData[$sDataField];
                }
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
            foreach ($this->Primary as $oPrimary) {
                if (isset($aData[$oPrimary->sColumn])) {
                    $oField = $this->Fields->seekByTitle($oPrimary->sColumn);

                    /** @var Field $oField */
                    $oField->setValue($aData[$oPrimary->sColumn]);
                }
            }
        }

        /**
         *
         * @return Table
         */
        public function getByPrimary() {
            return $this->getBy($this->Primary);
        }

        /**
         * @param Fields|Field $aFields...
         * @return Table|null
         * @throws TableException
         */
        protected function getBy(...$aFields) {
            $oObject = NULL;
            $oConditions = SQL::also($aFields);
            if ($oConditions->count() == 0) {
                throw new TableException('No Conditions Given');
            }

            $aQueryName = array();
            foreach($aFields as $oField) {
                $aQueryName[] = $oField->sColumn;
            }

            $aClass     = explode('\\', get_class($this));
            $sQueryName = array_pop($aClass) . '.getBy.' . implode('_', $aQueryName);

            if ($oResult = $this->DB->namedQuery($sQueryName, SQL::select($this, $oConditions))) {
                if ($oResult->num_rows > 0) {
                    $this->setFromObject($oResult->fetch_object());
                    return $this;
                }
            }

            return NULL;
        }

        /**
         * Wrapper for Fields object, mostly for auto-setting table name
         * @param Field $oField
         * @return void
         */
        public function addField(Field $oField) {
            $oField->sTable      = $this->sTitle;
            $oField->sTableClass = get_class($this);
            $this->Fields->add($oField);
        }

        /**
         * Wrapper for Fields object, mostly for auto-setting table name
         * @param Field $aFields,... Array of Field
         * @return void
         */
        public function addFields($aFields) {
            $aFields = func_get_args();
            foreach ($aFields as $oField) {
                $this->addField($oField);
            }
        }

        /**
         * Wrapper for Fields object, mostly for auto-setting table name
         * @param $aFields
         */
        public function setFields($aFields) {
            $aFields = func_get_args();

            $this->Fields = new Fields($aFields);
            foreach($this->Fields as $oField) {
                $oField->sTable = $this->sTitle;
            }
        }

        /**
         * Wrapper for Fields object, mostly for auto-setting table name
         * @param Field $oField
         * @return void
         */
        public function addPrimary(Field $oField) {
            $this->Primary->add($oField);
        }

        /**
         * Wrapper for Fields object, mostly for auto-setting table name
         * @param array $aFields,... Array of Field
         * @return void
         */
        public function addPrimaries($aFields) {
            $aFields = func_get_args();
            foreach ($aFields as $oField) {
                $this->addPrimary($oField);
            }
        }
        
        /**
         *
         * @return MySQLi_Result|bool
         */
        public function update() {
            if (!$this->changed()) {
                return false;
            }

            if ($this->Primary->hasValue()) {
                $sFromTable = 'Table';
                if ($sFromTable === NULL) {
                    $sFromTable = get_class($this);
                }

                $oConditions = Conditions::also($this->Primary);

                $oReturn = $this->DB->namedQuery($sFromTable . '.update',
                    SQL::update($this, $oConditions)
                );

                return $oReturn;
            }
        }

        /**
         *
         * @return MySQLi_Result
         */
        public function delete() {
            if ($this->Primary->hasValue()) {
                $oConditions = Conditions::also($this->Primary);

                $oReturn = $this->DB->namedQuery(get_class($this) . '.delete',
                    SQL::delete($this, $oConditions)
                );

                return $oReturn;
            }
        }
        
        /**
         *
         * @return int
         */
        public function insert() {
            $this->DB->namedQuery(get_class($this) . '.insert',
                SQL::insert($this)
            );

            $iLastInsertId = $this->DB->getLastInsertId();
            $this->updatePrimary($iLastInsertId);

            return $iLastInsertId;
        }

        /**
         * @param int $iLastInsertId
         */
        private function updatePrimary($iLastInsertId) {
            if (count($this->Primary) == 1) {
                /** @var Field\Id $oField */
                $oField = $this->Primary[0];
                if ( $oField instanceof Field\Id
                ||   $oField instanceof Field\Integer ) {
                    $oField->setValue($iLastInsertId);
                }
            }
        }

        /**
         *
         * @return int
         */
        public function upsert() {
            $this->DB->namedQuery(get_class($this) . '.upsert',
                SQL::upsert($this)
            );

            $iLastInsertId = $this->DB->getLastInsertId();
            $this->updatePrimary($iLastInsertId);

            return $iLastInsertId;
        }
        
        /**
         * @return \DateTime
         * */
        public function now() {
            return $this->DB->getDate();
        }

        /**
         * Checks if object is new or not
         *
         * @return bool
            @author
         */
        public function isNew() {
            return ($this->Primary->hasValue())? FALSE : TRUE;
        }

        /**
         * @param MySQLi_Result $oResults
         * @param string        $sKey  Column to use as array key
         * @return Table[]
         */
        public static function toTables(MySQLi_Result $oResults, $sKey = null) {
            $aTables = array();
            if ($oResults->num_rows) {
                /** @var Table $sTable */
                if ($sKey) {
                    while ($oResult = $oResults->fetch_object()) {
                        $aTables[$oResult->$sKey] = self::createFromObject($oResult);
                    }
                } else {
                    while ($oResult = $oResults->fetch_object()) {
                        $aTables[] = self::createFromObject($oResult);
                    }
                }
            }

            return $aTables;
        }

    }
?>
