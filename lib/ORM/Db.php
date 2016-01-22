<?php
    namespace Enobrev\ORM;

    use MySQLi;
    use MySQLi_Result;
    use DateTime;
    use Enobrev\Log;
    use Enobrev\SQL;

    class Db extends MySQLi {
        /** @var Db */
        private static $oInstance;

        /** @var bool */
        private static $bConnected = false;

        /** @var int */
        public static $iRowsAffected = 0;

        /** @var bool */
        public static $bUpsertInserted = false;

        /** @var bool */
        public static $bUpsertUpdated  = false;

        /** @var  SQLLogger */
        private $oLogger;

        /**
         *
         * @param String $sServer
         * @param String $sUser
         * @param String $sPassword
         * @param String $sDatabase
         * @return Db
         * @throws DbException
         */
        public static function getInstance($sServer = '', $sUser = '', $sPassword = '', $sDatabase = '') {
            if (!self::$oInstance instanceof self) {
                if (self::$oInstance = @new self($sServer, $sUser, $sPassword, $sDatabase)) {
                    self::$bConnected = true;
                } else {
                    throw new DbException(self::$oInstance->error, self::$oInstance->errno);
                }

                self::$oInstance->checkErrors();
            }

            return self::$oInstance;
        }

        public function setLogger(SQLLogger $oLogger) {
            $this->oLogger = $oLogger;
        }

        public function close() {
            if (self::$oInstance instanceof self && self::$bConnected) {
                parent::close();
            }
            self::$bConnected = false;
            self::$oInstance = null;
        }

        /**
         * @param string $sServer
         * @param string $sUser
         * @param string $sPassword
         * @param string $sDatabase
         * @return Db
         */
        public static function updateInstance($sServer = '', $sUser = '', $sPassword = '', $sDatabase = '') {
            if (self::$oInstance instanceof self && self::$bConnected) {
                self::$oInstance->close();
            }
            return self::getInstance($sServer, $sUser, $sPassword, $sDatabase);

        }
        
        /**
         *
         * @return boolean
         */
        public static function isConnected() {
            return self::$bConnected;
        }

        /**
         *
         * @return boolean
         */
        public static function wasUpsertInserted() {
            return self::$bUpsertInserted;
        }

        /**
         *
         * @return boolean
         */
        public static function wasUpsertUpdated() {
            return self::$bUpsertUpdated;
        }
        
        public function checkErrors() {
            if ($this->connect_errno) {
                self::$bConnected = false;
                throw new DbException($this->connect_error, $this->connect_errno);
            }

            if ($this->errno) {
                self::$bConnected = false;
                throw new DbException($this->error, $this->errno);
            }
        }
        
        /**
         * close previous connections before opening a new one
         * @param string $host
         * @param string $username
         * @param string $passwd
         * @param string $dbname
         * @param int $port
         * @param string $socket
         * @return boolean 
         */
        public function connect ($host = NULL, $username = NULL, $passwd = NULL, $dbname = NULL, $port = NULL, $socket = NULL) {
            if (self::$bConnected) {
                $this->close();
            }
            
            @parent::connect($host, $username, $passwd, $dbname, $port, $socket);
            $this->checkErrors();
            $this->set_charset("utf8");
            
            return true;
        }
        
        private $iLastInsertId;
        
        public function getLastInsertId() {
            return $this->iLastInsertId;
        }

        /**
         * @param string|string[]   $sName
         * @param string            $sQuery
         * @param int               $iResultMode
         *
         * @return bool|\mysqli_result
         */
        public function namedQuery($sName, $sQuery, $iResultMode = MYSQLI_STORE_RESULT) {
            if (is_array($sName)) {
                $sName = implode('.', $sName);
            }

            $sName = str_replace('\\', '.', str_replace('/', '.', $sName));
            return $this->query($sQuery, $iResultMode, $sName);
        }

        /**
         * @param string $sQuery
         * @param int    $iResultMode
         * @param string $sName
         *
         * @return bool|\mysqli_result
         * @throws DbDuplicateException|DbException
         */
        public function query($sQuery, $iResultMode = MYSQLI_STORE_RESULT, $sName = '') {
            if ($this->oLogger) {
                $this->oLogger->startQuery($sName);
            }

            $sSQL = $sQuery;
            if ($sSQL instanceof SQL) {
                /** @var SQL $sQuery */
                $sSQL = $sQuery->sSQL;
            }
                        
            $mResult = $this->parentQuery($sSQL, $iResultMode);

            if ($this->errno) {
                if ($this->errno == 1062) {
                    $oException = new DbDuplicateException($this->error . ' in SQL: ' . $sSQL, $this->errno);
                } else {
                    $oException = new DbException($this->error . ' in SQL: ' . $sSQL, $this->errno);
                }
                if ($this->oLogger) {
                    $this->oLogger->stopQuery($sQuery, array(), $sName);
                }
                throw $oException;
            }
            
            $this->iLastInsertId = $this->insert_id;

            if ($mResult instanceof MySQLi_Result) {
                if (preg_match('/^select/', strtolower($sSQL))) {
                    self::$iRowsAffected = $mResult->num_rows;
                } else {
                    self::$iRowsAffected = $this->affected_rows;
                }
            } else {
                self::$iRowsAffected = $this->affected_rows;
            }

            if (stristr($sSQL, 'ON DUPLICATE KEY UPDATE') !== false) {
                switch(self::$iRowsAffected) {
                    case 1: self::$bUpsertInserted = true; break;
                    case 2: self::$bUpsertUpdated  = true; break;
                }
            }

            $aParams = array(
                'rows' => self::$iRowsAffected
            );

            if (strlen($sName)) {
                $aParams['SQL_NAME'] = $sName;
            }

            if ($this->oLogger) {
                $this->oLogger->stopQuery($sQuery, $aParams, $sName, self::$iRowsAffected);
            }

            return $mResult;
        }

        
        /**
         *
         * @return DateTime
         */
        public function getDate() {
            $mResult = $this->parentQuery('SELECT SYSDATE() AS system_date;');
            $oResult = $mResult->fetch_object();
            return new DateTime($oResult->system_date);
        }

        /**
         * @param string $sModification
         * @return DateTime
         */
        public function getModifiedDate($sModification) {
            $oDate = $this->getDate();
            $oDate->modify($sModification);
            return $oDate;
        }

        /**
         * http://stackoverflow.com/a/15875555/14651
         * @return string
         */
        public function getUUID() {
            $data = openssl_random_pseudo_bytes(16);

            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        /**
         * Do not use Log class here as it will cause an infinite loop
         * @param String $sQuery
         * @param Integer $iResultMode
         * @return MySQLi_Result
         */
        public function parentQuery($sQuery, $iResultMode = MYSQLI_STORE_RESULT) {                        
            return parent::query($sQuery, $iResultMode);
        }

        /**
         *
         * @param string $sTable
         * @param array $aFieldValues
         * @return MySQLi_Result
         */
        public function insert($sTable, $aFieldValues) {
            return $this->query($this->getInsert($sTable, $aFieldValues));
        }
        
        /**
         *
         * @param string $sTable
         * @param array $aFieldValues
         * @return string
         */
        public function getInsert($sTable, $aFieldValues) {
            $aFields = array_keys($aFieldValues);
            $aValues = array_values($aFieldValues);
            
            return 'INSERT INTO ' . $sTable . '(' . implode(', ', $aFields) . ') VALUES (' . implode(', ', $aValues) . ');';
        }
        
        /**
         *
         * @param string $sTable
         * @param array $aFieldValues
         * @param string $sCondition
         * @return MySQLi_Result
         */
        public function update($sTable, $aFieldValues, $sCondition) {
            $aUpdates = array();
            foreach ($aFieldValues as $sField => $sValue) {
                $aUpdates[] = $sField . ' = ' . $sValue;
            }
            
            $sSQL = 'UPDATE ' . $sTable . ' SET ' . implode(', ', $aUpdates) . ' WHERE ' . $sCondition;        
            return $this->query($sSQL);
        }
        
        /**
         *
         * @param string $sTable
         * @param string $sCondition
         * @return MySQLi_Result
         */
        public function delete($sTable, $sCondition) {            
            $sSQL = 'DELETE FROM ' . $sTable . ' WHERE ' . $sCondition;
            return $this->query($sSQL);
        }

        private function __clone() {
            throw new DbException('Cannot clone the database class');
        }
    }
?>