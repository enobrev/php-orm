<?php
    namespace Enobrev\ORM;

    use PDO;
    use PDOException;
    use PDOStatement;
    use DateTime;
    use Enobrev\SQL;
    use Enobrev\SQLBuilder;

    class Db {
        /** @var Db */
        private static $oInstance;

        /** @var bool */
        private static $bConnected = false;

        /** @var bool */
        public static $bUpsertInserted = false;

        /** @var bool */
        public static $bUpsertUpdated  = false;

        /** @var int */
        private $iLastInsertId;

        /** @var SQLLogger */
        private $oLogger;

        /** @var PDO $oPDO */
        private static $oPDO;

        /**
         * @param PDO|null $oPDO
         * @return Db
         * @throws DbException
         */
        public static function getInstance(PDO $oPDO = null) {
            if (!self::$oInstance instanceof self) {
                if ($oPDO === null) {
                    throw new DbException('Db Has Not been Initialized Properly');
                }

                self::$oInstance = new self($oPDO);
            }

            return self::$oInstance;
        }

        /**
         * @param PDO $oPDO
         * @return Db
         */
        public static function replaceInstance(PDO $oPDO) {
            if (self::$oInstance instanceof self && self::$bConnected) {
                self::$oInstance->close();
            }

            return self::getInstance($oPDO);
        }

        /**
         * @param string      $sHost
         * @param string|null $sUsername
         * @param string|null $sPassword
         * @param string|null $sDatabase
         * @param array       $aOptions
         * @return PDO
         */
        public static function defaultMySQLPDO(string $sHost, string $sUsername = null, string $sPassword = null, string $sDatabase = null, array $aOptions = []) {
            $sDSN = "mysql:host=$sHost;port=3307";
            if ($sDatabase) {
                $sDSN .= ";dbname=$sDatabase";
            }

            $oPDO = new PDO($sDSN, $sUsername, $sPassword, $aOptions);
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $oPDO->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS,   true);

            return $oPDO;
        }

        /**
         * @return PDO
         */
        public static function defaultSQLiteMemory() {
            $oPDO = new PDO('sqlite::memory');
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $oPDO;
        }

        /**
         * @param PDO $oPDO
         */
        private function __construct(PDO $oPDO) {
            self::$oPDO = $oPDO;
        }

        /**
         * @param SQLLogger $oLogger
         */
        public function setLogger(SQLLogger $oLogger) {
            $this->oLogger = $oLogger;
        }

        public function close() {
            if (self::$oInstance instanceof self && self::$bConnected) {
                self::$oPDO = null;
            }

            self::$bConnected = false;
            self::$oInstance = null;
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
        
        public function getLastInsertId() {
            return $this->iLastInsertId;
        }

        /**
         * @param string|string[]   $sName
         * @param string            $sQuery
         *
         * @return PDOStatement
         */
        public function namedQuery($sName, $sQuery) {
            if (is_array($sName)) {
                $sName = implode('.', $sName);
            }

            $sName = str_replace('\\', '.', str_replace('/', '.', $sName));
            return $this->query($sQuery, $sName);
        }

        /**
         * @param string $sQuery
         * @param string $sName
         *
         * @return PDOStatement
         * @throws DbDuplicateException|DbException
         */
        public function query($sQuery, $sName = '') {
            if ($this->oLogger) {
                $this->oLogger->startQuery($sName);
            }

            $sSQL = $sQuery;
            if ($sSQL instanceof SQL || $sSQL instanceof SQLBuilder) {
                $sSQL = (string) $sQuery;
            }

            try {
                $mResult = $this->rawQuery($sSQL);
            } catch(PDOException $e) {
                if ($e->getCode() == 1062) {
                    $oException = new DbDuplicateException($e->getMessage() . ' in SQL: ' . $sSQL, $e->getCode());
                } else {
                    $oException = new DbException($e->getMessage() . ' in SQL: ' . $sSQL, $e->getCode());
                }

                if ($this->oLogger) {
                    $this->oLogger->stopQuery($sQuery, array(), $sName);
                }

                throw $oException;
            }
            
            $this->iLastInsertId = self::$oPDO->lastInsertId();

            $iRowsAffected = 0;
            if ($mResult instanceof PDOStatement) {
                $iRowsAffected = $mResult->rowCount();
            }

            if (stristr($sSQL, 'ON DUPLICATE KEY UPDATE') !== false) {
                switch($iRowsAffected) {
                    case 1: self::$bUpsertInserted = true; break;
                    case 2: self::$bUpsertUpdated  = true; break;
                }
            }

            $aParams = array(
                'rows' => $iRowsAffected
            );

            if (strlen($sName)) {
                $aParams['SQL_NAME'] = $sName;
            }

            if ($this->oLogger) {
                $this->oLogger->stopQuery($sQuery, $aParams, $sName, $iRowsAffected);
            }

            return $mResult;
        }

        /**
         * Do not use Log class here as it will cause an infinite loop
         * @param string $sQuery
         * @return PDOStatement
         */
        public function rawQuery($sQuery) {
            return self::$oPDO->query($sQuery);
        }

        
        /**
         *
         * @return DateTime
         */
        public function getDate() {
            return new DateTime($this->rawQuery('SELECT SYSDATE()')->fetchColumn());
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
         * @throws DbException
         */
        private function __clone() {
            throw new DbException('Cannot clone the database class');
        }
    }
?>