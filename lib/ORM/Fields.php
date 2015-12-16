<?php
    namespace Enobrev\ORM;

    use stdClass;
    use ArrayIterator;

    class Fields extends ArrayIterator {
        /**
         *
         * @param string $sTitle
         * @return Field
         */
        public function __get($sTitle) {
            return $this->seekByTitle($sTitle);
        }

        /**
         *
         * @param array $aFilter
         * @return array
         */
        public function getValues(Array $aFilter = array()) {
            if (count($aFilter) == 0) {

                /** @var Field $oField */
                foreach ($this as $oField) {
                    $aFilter[] = $oField->sColumn;
                }
            }

            $aArray  = array();
            foreach ($aFilter as $sField) {
                $oField = $this->$sField;
                if ($oField instanceof Field) {
                    $aArray[$sField] = (string) $oField;
                }
            }

            return $aArray;
        }

        /**
         * @return bool
         */
        public function hasValue() {
            /** @var Field $oField */
            foreach ($this as $oField) {
                if (!$oField->hasValue()) {
                    return false;
                }
            }

            return true;
        }

        /**
         * @return bool
         */
        public function applyDefaults() {
            /** @var Field $oField */
            foreach ($this as $oField) {
                if ($oField->hasDefault()) {
                    if (!$oField->hasValue()) {
                        $oField->applyDefault();
                    }
                }
            }
        }

        /**
         * @param array $aFilter
         * @return array
         */
        public function toArray(Array $aFilter = array()) {
            $aArray = array();

            /** @var Field $oField */
            foreach ($this as $oField) {
                if (count($aFilter)) {
                    if (!in_array($oField->sColumn, $aFilter)) {
                        continue;
                    }
                }

                /** @var Field $oField */
                $aArray[$oField->sColumn] = (string) $oField;
            }
            
            return $aArray;
        }
        
        /**
         *
         * @return String[]
         */
        public function toSQLArray() {
            $aArray = array();

            /** @var Field $oField */
            foreach ($this as $oField) {
                if (!$oField->isNull()) {
                    $aArray[$oField->sColumn] = $oField->toSQL();
                }
            }
            
            return $aArray;
        }

        /**
         * @param string $sTitle
         * @return Field
         */
        public function seekByTitle($sTitle) {
            /** @var Field $oField */
            foreach ($this as $oField) {
                if ($oField->sColumn == $sTitle) {
                    return $this->current();
                }
            }
        }
        
        /**
         *
         * @param Fields|Field $aFields,...
         */
        public function add($aFields) {
            $aFields = func_get_args();

            foreach ($aFields as $mField) {
                switch(true) {
                    case $mField instanceof self:
                        foreach($mField as $oField) {
                            $this->append($oField);
                        }
                        break;

                    case is_array($mField):
                        foreach($mField as $oField) {
                            $this->append($oField);
                        }
                        break;

                    case $mField instanceof Field:
                        $this->append($mField);
                        break;
                }
            }
        }

        /**
         *
         * @param Field $oFieldToRemove
         * @return null
         */
        public function remove(Field $oFieldToRemove) {
            /** @var Field $oField */
            foreach ($this as $oField) {
                if ($oField->sColumn == $oFieldToRemove->sColumn) {
                    $this->offsetUnset($this->key());
                    return NULL;
                }
            }
        }

        /**
         * @return array[]
         */
        public function toInfoArray() {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                $aFields[$oField->sColumn] = $oField->toInfoArray();
            }

            return $aFields;
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumns($bWithTable = true) {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                $aFields[] = $oField->toSQLColumnForFields($bWithTable);
            }

            return implode(', ', $aFields);
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnsForSelect($bWithTable = true) {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                $aFields[] = $oField->toSQLColumnForSelect($bWithTable);
            }

            return implode(', ', $aFields);
        }

        /**
         * @param bool $bWithTable
         * @return string
         */
        public function toSQLColumnsForCount($bWithTable = true) {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                $aFields[] = $oField->toSQLColumnForCount($bWithTable);
            }

            return implode(', ', $aFields);
        }

        /**
         * @return string
         */
        public function toSQLColumnsForInsert() {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                $aFields[] = $oField->toSQLColumnForInsert();
            }

            return implode(', ', $aFields);
        }

        /**
         * @return string
         */
        public function toSQL() {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                $aFields[] = $oField->toSQL();
            }

            return implode(', ', $aFields);
        }

        /**
         * @return string
         */
        public function toSQLLog() {
            $aFields = array();
            foreach($this as $oField) {
                /** @var Field $oField */
                $aFields[] = get_class($oField);
            }

            return implode(', ', $aFields);
        }

        /**
         * @param stdClass $oResult
         *
         * @return string
         */
        public function toSQLUpdate(stdClass $oResult = NULL) {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                if ($oResult !== NULL) {
                    if (property_exists($oResult, $oField->sColumn)) {
                        if ($oField->is($oResult->{$oField->sColumn})) {
                            continue;
                        }
                    }
                }

                $aFields[] = $oField->toSQLColumn() . ' = ' . $oField->toSQL();
            }

            return implode(', ', $aFields);
        }

        /**
         * @param stdClass $oResult
         *
         * @return string
         */
        public function toSQLUpdateLog(stdClass $oResult = NULL) {
            $aFields = array();

            /** @var Field $oField */
            foreach($this as $oField) {
                if ($oResult !== NULL) {
                    if (property_exists($oResult, $oField->sColumn)) {
                        if ($oField->is($oResult->{$oField->sColumn})) {
                            continue;
                        }
                    }
                }

                /** @var Field $oField */
                $aFields[] = $oField->toSQLColumn() . ' = ' . get_class($oField);
            }

            return implode(', ', $aFields);
        }
    }
?>