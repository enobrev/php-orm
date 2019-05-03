<?php
    namespace Enobrev\ORM;

    use Enobrev\Log;
    use PDO;
    use PDOException;
    use PDOStatement;
    use DateTime;
    use Enobrev\SQL;
    use Enobrev\SQLBuilder;

    class Db {
        /** @var Db|null */
        private static $oInstance = null;

        /** @var Db|null */
        private static $oInstance2 = null;

        /** @var bool */
        private static $bConnected = false;

        /** @var bool */
        public static $bUpsertInserted = false;

        /** @var bool */
        public static $bUpsertUpdated  = false;

        /** @var mixed */
        private $sLastInsertId = null;

        /** @var int */
        private $iLastRowsAffected = null;

        /** @var PDO $oPDO */
        private $oPDO;

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
         * Hackish and Silly.  There are definitely cleaner ways to do this.  But I want two databases at once with minimal effort, and this does it for now
         * @param PDO|null $oPDO
         * @return Db
         * @throws DbException
         */
        public static function getInstance2(PDO $oPDO = null) {
            if (!self::$oInstance2 instanceof self) {
                if ($oPDO === null) {
                    throw new DbException('Db Has Not been Initialized Properly');
                }

                self::$oInstance2 = new self($oPDO);
            }

            return self::$oInstance2;
        }

        /**
         * @return PDO
         */
        public function getPDO() {
            return $this->oPDO;
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
         * @psalm-suppress PossiblyNullArgument
         */
        public static function defaultMySQLPDO(string $sHost, string $sUsername = null, string $sPassword = null, string $sDatabase = null, array $aOptions = []) {
            $sDSN = "mysql:host=$sHost";
            if ($sDatabase) {
                $sDSN .= ";dbname=$sDatabase";
            }
            $sDSN .= ";charset=utf8mb4";

            $oPDO = new PDO($sDSN, $sUsername, $sPassword, $aOptions);
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $oPDO->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS,   true);
            $oPDO->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

            return $oPDO;
        }

        /**
         * @param string      $sHost
         * @param string|null $sUsername
         * @param string|null $sPassword
         * @param string|null $sDatabase
         * @param array       $aOptions
         * @return PDO
         * @psalm-suppress PossiblyNullArgument
         */
        public static function defaultPostgresPDO(string $sHost, string $sUsername = null, string $sPassword = null, string $sDatabase = null, array $aOptions = []) {
            $sDSN = "pgsql:host=$sHost";
            if ($sDatabase) {
                $sDSN .= ";dbname=$sDatabase";
            }

            $oPDO = new PDO($sDSN, $sUsername, $sPassword, $aOptions);
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $oPDO;
        }

        /**
         * @return PDO
         */
        public static function defaultSQLiteMemory() {
            $oPDO = new PDO('sqlite::memory:');
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $oPDO;
        }

        /**
         * @param string $sFile
         * @return PDO
         */
        public static function defaultSQLiteFile(string $sFile) {
            $oPDO = new PDO("sqlite:$sFile");
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $oPDO;
        }

        /**
         * @param PDO $oPDO
         */
        private function __construct(PDO $oPDO) {
            $this->oPDO = $oPDO;
        }

        /**
         * @psalm-suppress InvalidPropertyAssignment
         */
        public function close(): void {
            if (self::$oInstance instanceof self && self::$bConnected) {
                $this->oPDO = null;
            }

            self::$bConnected = false;
            self::$oInstance  = null;
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
         * Keep the connection alive on a long-lived process
         */
        public function ping() {
            $this->rawQuery('SELECT 1');
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

        /**
         * @return mixed
         */
        public function getLastInsertId() {
            return $this->sLastInsertId;
        }

        public function getLastRowsAffected(): int {
            return $this->iLastRowsAffected;
        }

        /**
         * @param string|string[]       $sName
         * @param string|SQLBuilder|SQL $sQuery
         *
         * @return PDOStatement
         * @throws DbDuplicateException
         * @throws DbException
         */
        public function namedQuery($sName, $sQuery) {
            if (is_array($sName)) {
                $sName = implode('.', $sName);
            }

            $sName = str_replace('\\', '.', str_replace('/', '.', $sName));
            return $this->query($sQuery, $sName);
        }

        /**
         * @param string|SQLBuilder|SQL $sQuery
         * @param string $sName
         *
         * @return PDOStatement
         * @throws DbDuplicateException|DbException
         */
        public function query($sQuery, $sName = '') {
            $sTimerName = 'ORM.Db.query.' . $sName;
            Log::startTimer($sTimerName);

            $aLogOutput = [
                'name'  => $sName
            ];

            $sSQL = $sQuery;
            if ($sSQL instanceof SQL || $sSQL instanceof SQLBuilder) {
                $sSQL = (string) $sQuery;

                /** @psalm-suppress PossiblyInvalidPropertyFetch */
                $aLogOutput['sql'] = [
                    'driver' => $this->oPDO->getAttribute(PDO::ATTR_DRIVER_NAME),
                    'query'  => $sSQL,
                    'group'  => $sQuery->sSQLGroup,
                    'table'  => $sQuery->sSQLTable,
                    'type'   => $sQuery->sSQLType,
                    'hash'   => [
                        'group' => hash('sha1', $sQuery->sSQLGroup),
                        'query' => hash('sha1', $sSQL)
                    ]
                ];
            } else {
                // We have no pre-defined group, so the name or the query itself becomes the group
                $sGroup     = trim($sName) !== '' ? $sName : $sSQL;

                $aLogOutput['sql'] = [
                    'driver' => $this->oPDO->getAttribute(PDO::ATTR_DRIVER_NAME),
                    'query'  => $sSQL,
                    'group'  => $sGroup,
                    'hash'   => [
                        'group' => hash('sha1', $sGroup),
                        'query' => hash('sha1', $sSQL)
                    ]
                ];

                $aTypes = [
                    SQLBuilder::TYPE_INSERT,
                    SQLBuilder::TYPE_UPDATE,
                    SQLBuilder::TYPE_DELETE,
                    SQLBuilder::TYPE_SELECT,
                    SQLBuilder::TYPE_UPSERT
                ];

                $sSQLUpper = strtoupper($sSQL);
                $sSQLLower = strtolower($sSQL);
                $sType     = strtok($sSQLUpper, ' ');
                if (in_array($sType, $aTypes, true)) {
                    $aLogOutput['sql']['type'] = $sType;

                    if ($sType === SQLBuilder::TYPE_SELECT) {
                        $sCount = strtok(' ');
                        if ($sCount === 'COUNT') {
                            $aLogOutput['sql']['type'] = SQLBuilder::TYPE_COUNT;
                        }
                    }

                    switch($sType) {
                        case SQLBuilder::TYPE_INSERT:
                        case SQLBuilder::TYPE_UPSERT:
                            if (preg_match('/into\s+(\S+)/', $sSQLLower, $aMatches)) {
                                $aLogOutput['sql']['table'] = $aMatches[1];
                            }
                            break;

                        case SQLBuilder::TYPE_UPDATE:
                            if (preg_match('/update\s+(\S+)/', $sSQLLower, $aMatches)) {
                                $aLogOutput['sql']['table'] = $aMatches[1];
                            }
                            break;

                        case SQLBuilder::TYPE_DELETE:
                        case SQLBuilder::TYPE_SELECT:
                        case SQLBuilder::TYPE_COUNT:
                            if (preg_match('/from\s+(\S+)/', $sSQLLower, $aMatches)) {
                                $aLogOutput['sql']['table'] = $aMatches[1];
                            }
                            break;
                    }
                }
            }

            try {
                $mResult = $this->rawQuery($sSQL);
            } catch(PDOException $e) {
                $iCode = (int) $e->getCode();

                if ($iCode == 1062 || $iCode == 23000) {
                    $oException = new DbDuplicateException($e->getMessage() . ' in SQL: ' . $sSQL, $iCode);
                } else {
                    $oException = new DbException($e->getMessage() . ' in SQL: ' . $sSQL, $iCode);
                }

                $aLogOutput['--ms']  = Log::stopTimer($sTimerName);
                $aLogOutput['error'] = $oException->getMessage();

                Log::e('ORM.Db.query', $aLogOutput);

                throw $oException;
            }

            $this->iLastRowsAffected = 0;
            if ($mResult instanceof PDOStatement) {
                switch($this->oPDO->getAttribute(PDO::ATTR_DRIVER_NAME)) {
                    case 'mysql':
                        $this->sLastInsertId     = $this->oPDO->lastInsertId();
                        $this->iLastRowsAffected = $mResult->rowCount();
                        break;

                    case 'sqlite':
                        $this->sLastInsertId     = $this->oPDO->lastInsertId();
                        if (!preg_match('/^select/i', $sSQL)) {
                            $this->iLastRowsAffected = $mResult->rowCount();
                        } else {
                            // FIXME: Yes, this is slow and relatively stupid.  But since SQLite is currently just used for testing, we'll just deal
                            while ($oResult = $mResult->fetch()) {
                                $this->iLastRowsAffected++;
                            }

                            $mResult = $this->rawQuery($sSQL);
                        }
                        break;

                    default:
                        $this->iLastRowsAffected = $mResult->rowCount();
                        break;
                }
            }

            if (stristr($sSQL, 'ON DUPLICATE KEY UPDATE') !== false) {
                switch($this->iLastRowsAffected) {
                    case 1: self::$bUpsertInserted = true; break;
                    case 2: self::$bUpsertUpdated  = true; break;
                }
            }

            $aLogOutput['--ms']  = Log::stopTimer($sTimerName);
            $aLogOutput['rows']  = $this->iLastRowsAffected;
            Log::i('ORM.Db.query', $aLogOutput);

            return $mResult;
        }

        /**
         * Do not use Log class here as it will cause an infinite loop
         * @param string $sQuery
         * @return PDOStatement
         */
        public function rawQuery($sQuery) {
            return $this->oPDO->query($sQuery);
        }

        /**
         * @param string $sStatement
         * @return PDOStatement
         */
        public function prepare(string $sStatement) {
            return $this->oPDO->prepare($sStatement);
        }

        /**
         * @param     $sString
         * @param int $sPDOType
         * @return string
         */
        public function quote($sString, $sPDOType = PDO::PARAM_STR) {
            return $this->oPDO->quote($sString, $sPDOType);
        }

        /**
         * @return bool
         */
        public function beginTransaction(): bool {
            return $this->oPDO->beginTransaction();
        }

        /**
         * @return bool
         */
        public function rollBack(): bool {
            return $this->oPDO->rollBack();
        }

        /**
         * @return bool
         */
        public function commit(): bool {
            return $this->oPDO->commit();
        }

        /**
         *
         * @return DateTime
         * @psalm-suppress InvalidReturnType
         */
        public function getDate(): DateTime {
            $sDriver = $this->oPDO->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($sDriver == 'sqlite') {
                return new DateTime('now');
            }

            return new DateTime($this->rawQuery('SELECT SYSDATE(6)')->fetchColumn());
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