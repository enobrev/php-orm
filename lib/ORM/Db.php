<?php
    namespace Enobrev\ORM;

    use DateTime;
    use DateTimeZone;
    use Enobrev\ORM\Exceptions\DbConstraintException;
    use Enobrev\ORM\Exceptions\DbDeadlockException;
    use Enobrev\ORM\Exceptions\DbEmptyQueryException;
    use Exception;
    use PDO;
    use PDOException;
    use PDOStatement;

    use Enobrev\Log;
    use Enobrev\ORM\Exceptions\DbDuplicateException;
    use Enobrev\ORM\Exceptions\DbException;
    use Enobrev\SQLBuilder;

    class Db {
        private static ?Db $oInstance_MySQL = null;

        private static ?Db $oInstance_PG = null;

        private static bool $bConnected = false;

        public static bool $bUpsertInserted = false;

        public static bool $bUpsertUpdated  = false;

        private static bool $bForceSource = false;

        /** @var mixed */
        private $sLastInsertId;

        private ?int $iLastRowsAffected;

        private ?PDO $oPDO_Source;
        private ?PDO $oPDO_Replica;

        /**
         * @param PDO|null $oPDO_Source
         * @param PDO|null $oPDO_Replica if available, will be used for reads
         *
         * @return Db
         * @throws DbException
         */
        public static function getInstance(?PDO $oPDO_Source = null, ?PDO $oPDO_Replica = null): Db {
            if (!self::$oInstance_MySQL instanceof self) {
                if ($oPDO_Source === null) {
                    throw new DbException('Db Has Not been Initialized Properly');
                }

                self::$oInstance_MySQL = new self($oPDO_Source, $oPDO_Replica);
            }

            return self::$oInstance_MySQL;
        }

        /**
         * Hackish and Silly.  There are definitely cleaner ways to do this.  But I want two databases at once with minimal effort, and this does it for now
         *
         * @param PDO|null $oPDO_PG
         *
         * @return Db
         * @throws DbException
         */
        public static function getPGInstance(PDO $oPDO_PG = null): Db {
            if (!self::$oInstance_PG instanceof self) {
                if ($oPDO_PG === null) {
                    throw new DbException('Db Has Not been Initialized Properly');
                }

                self::$oInstance_PG = new self($oPDO_PG);
            }

            return self::$oInstance_PG;
        }

        public function getPDOSource(): ?PDO {
            return $this->oPDO_Source;
        }

        public function getPDOReplica(): ?PDO {
            return $this->oPDO_Replica;
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
        public static function defaultMySQLPDO(string $sHost, string $sUsername = null, string $sPassword = null, string $sDatabase = null, array $aOptions = []): PDO {
            $sDSN = "mysql:host=$sHost";
            if ($sDatabase) {
                $sDSN .= ";dbname=$sDatabase";
            }
            $sDSN .= ';charset=utf8mb4';

            $oPDO = new PDO($sDSN, $sUsername, $sPassword, $aOptions);
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $oPDO->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS,   true);
            $oPDO->setAttribute(PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci');

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
        public static function defaultPostgresPDO(string $sHost, string $sUsername = null, string $sPassword = null, string $sDatabase = null, array $aOptions = []): PDO {
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
        public static function defaultSQLiteMemory(): PDO {
            $oPDO = new PDO('sqlite::memory:');
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $oPDO;
        }

        /**
         * @param string $sFile
         * @return PDO
         */
        public static function defaultSQLiteFile(string $sFile): PDO {
            $oPDO = new PDO("sqlite:$sFile");
            $oPDO->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $oPDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $oPDO;
        }

        /**
         * @param PDO      $oPDO_Source
         * @param PDO|null $oPDO_Replica
         */
        private function __construct(PDO $oPDO_Source, ?PDO $oPDO_Replica = null) {
            $this->oPDO_Source  = $oPDO_Source;
            $this->oPDO_Replica = $oPDO_Replica;
        }

        /**
         * @psalm-suppress InvalidPropertyAssignment
         */
        public function close(): void {
            if (self::$oInstance_MySQL instanceof self && self::$bConnected) {
                $this->oPDO_Source = null;
                $this->oPDO_Replica = null;
            }

            if (self::$oInstance_PG instanceof self && self::$bConnected) {
                $this->oPDO_Source = null;
            }

            self::$bConnected = false;
            self::$oInstance_MySQL  = null;
            self::$oInstance_PG     = null;
        }

        /**
         *
         * @return boolean
         */
        public static function isConnected(): bool {
            return self::$bConnected;
        }

        public static function forceSource(bool $bForceSource): void {
            self::$bForceSource = $bForceSource;
        }

        /**
         *
         * Keep the connection alive on a long-lived process
         */
        public function ping(): void {
            $this->rawQuery('SELECT 1');
        }

        /**
         *
         * @return boolean
         */
        public static function wasUpsertInserted(): bool {
            return self::$bUpsertInserted;
        }

        /**
         *
         * @return boolean
         */
        public static function wasUpsertUpdated(): bool {
            return self::$bUpsertUpdated;
        }

        /**
         * @return mixed
         */
        public function getLastInsertId() {
            return $this->sLastInsertId;
        }

        public function getLastRowsAffected(): ?int {
            return $this->iLastRowsAffected;
        }

        /**
         * @param string|string[] $sName
         * @param string|SQLBuilder $sQuery
         *
         * @return PDOStatement|null
         * @throws DbException
         */
        public function namedQuery($sName, $sQuery): ?PDOStatement {
            if (is_array($sName)) {
                $sName = implode('.', $sName);
            }

            $sName = str_replace(['/', '\\'], '.', $sName);
            return $this->query($sQuery, $sName);
        }

        private function getPDOForQuery(string $sQuery): ?PDO {
            if (self::$bForceSource === false
            &&  $this->inTransaction() === false
            &&  $this->oPDO_Replica !== null
            &&  self::isRead($sQuery)) {
                return $this->oPDO_Replica;
            }

            return $this->oPDO_Source;
        }

        /**
         * @param string|SQLBuilder $sQuery
         * @param string            $sName
         *
         * @return PDOStatement|null
         * @throws DbException
         */
        public function query($sQuery, $sName = ''): ?PDOStatement {
            $sTimerName = 'ORM.Db.query.' . $sName;
            Log::startTimer($sTimerName);

            $aLogOutput = [
                'name'  => $sName
            ];

            $sSQL = $sQuery;
            if ($sSQL instanceof SQLBuilder) {
                try {
                    $sSQL = $sSQL->toString();
                } catch(Exception $e) {
                    Log::ex('ORM.Db.query.builder', $e, $aLogOutput);
                }

                $oPDO = $this->getPDOForQuery($sSQL);

                /** @psalm-suppress PossiblyInvalidPropertyFetch */
                $aLogOutput['sql'] = [
                    'driver'  => $oPDO ? $oPDO->getAttribute(PDO::ATTR_DRIVER_NAME) : 'N/A',
                    'replica' => $oPDO === $this->oPDO_Replica,
                    'query'   => preg_replace("/[\r\n\s\t]+/", " ", $sSQL),
                    'group'   => $sQuery->sSQLGroup,
                    'table'   => $sQuery->sSQLTable,
                    'type'    => $sQuery->sSQLType,
                    'hash'    => [
                        'group' => hash('sha1', $sQuery->sSQLGroup),
                        'query' => hash('sha1', $sSQL)
                    ]
                ];
            } else {
                $oPDO = $this->getPDOForQuery($sSQL);

                /* @var string $sSQL */
                // We have no pre-defined group, so the name or the query itself becomes the group
                $sGroup     = trim($sName) !== '' ? $sName : $sSQL;

                $aLogOutput['sql'] = [
                    'driver'  => $oPDO ? $oPDO->getAttribute(PDO::ATTR_DRIVER_NAME) : 'N/A',
                    'replica' => $oPDO === $this->oPDO_Replica,
                    'query'   => preg_replace("/[\r\n\s\t]+/", " ", $sSQL),
                    'group'   => $sGroup,
                    'hash'    => [
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
                            if (preg_match('/into\s+(\w+)/', $sSQLLower, $aMatches)) {
                                $aLogOutput['sql']['table'] = $aMatches[1];
                            }
                            break;

                        case SQLBuilder::TYPE_UPDATE:
                            if (preg_match('/update\s+(\w+)/', $sSQLLower, $aMatches)) {
                                $aLogOutput['sql']['table'] = $aMatches[1];
                            }
                            break;

                        case SQLBuilder::TYPE_DELETE:
                        case SQLBuilder::TYPE_SELECT:
                        case SQLBuilder::TYPE_COUNT:
                            if (preg_match('/from\s+(\w+)/', $sSQLLower, $aMatches)) {
                                $aLogOutput['sql']['table'] = $aMatches[1];
                            }
                            break;
                    }
                }
            }

            /* @var string $sSQL */
            assert(trim($sSQL) !== '', new DbException('Empty Query'));

            try {
                $mResult = $this->rawQuery($sSQL);
            } catch(PDOException $e) {
                $iCode = (int) $e->getCode();
                $sMessage = $e->getMessage();

                switch($iCode) {
                    case 1062:
                    case 23000:
                        if (strpos($sMessage, 'foreign key constraint fails') !== false) {
                            $oException = new DbConstraintException($sMessage . ' in SQL: ' . $sSQL, $iCode);
                        } else {
                            $oException = new DbDuplicateException($sMessage . ' in SQL: ' . $sSQL, $iCode);
                        }
                        break;

                    case 40001:
                        $oException = new DbDeadlockException($sMessage . ' in SQL: ' . $sSQL, $iCode);
                        break;

                    case 42000:
                        $oException = new DbEmptyQueryException($sMessage, $iCode);
                        break;

                    default:
                        $oException = new DbException($sMessage . ' in SQL: ' . $sSQL, $iCode);
                        break;
                }

                $aLogOutput['--ms']  = Log::stopTimer($sTimerName);

                Log::ex('ORM.Db.query', $e, $aLogOutput);

                throw $oException;
            }

            $this->iLastRowsAffected = 0;
            if ($mResult instanceof PDOStatement) {
                switch($oPDO->getAttribute(PDO::ATTR_DRIVER_NAME)) {
                    case 'mysql':
                        $this->sLastInsertId     = $oPDO->lastInsertId();
                        $this->iLastRowsAffected = $mResult->rowCount();
                        break;

                    case 'sqlite':
                        $this->sLastInsertId     = $oPDO->lastInsertId();
                        if (0 !== stripos($sSQL, 'select')) {
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

            if (false !== stripos($sSQL, 'ON DUPLICATE KEY UPDATE')) {
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

        private static function isRead($sQuery) {
            return strpos(strtolower(trim($sQuery)), 'select') === 0;
        }

        /**
         * Do not use Log class here as it will cause an infinite loop
         * @param string $sQuery
         * @return PDOStatement|null
         */
        public function rawQuery(string $sQuery): ?PDOStatement {
            $oPDO    = $this->getPDOForQuery($sQuery);
            $oResult = $oPDO->query($sQuery);

            if ($oResult === false) {
                return null;
            }
            
            return $oResult;
        }

        /**
         * @param string     $sStatement
         * @param array|null $aDriverOptions
         *
         * @return false|PDOStatement
         */
        public function prepare(string $sStatement, ?array $aDriverOptions = []) {
            $oPDO = $this->getPDOForQuery($sStatement);
            return $oPDO->prepare($sStatement, $aDriverOptions);
        }

        /**
         * @param     $sString
         * @param int $sPDOType
         * @return string
         */
        public function quote($sString, $sPDOType = PDO::PARAM_STR): string {
            $oPDO = $this->replicaOrSource();
            return $oPDO->quote($sString, $sPDOType);
        }

        public function beginTransaction(): bool {
            return $this->oPDO_Source->beginTransaction();
        }

        public function inTransaction(): bool {
            return $this->oPDO_Source->inTransaction();
        }

        public function rollBack(): bool {
            return $this->oPDO_Source->rollBack();
        }

        public function commit(): bool {
            return $this->oPDO_Source->commit();
        }

        private function replicaOrSource() {
            if (!self::$bForceSource) {
                return $this->oPDO_Replica ?? $this->oPDO_Source;
            }

            return $this->oPDO_Source;
        }

        /**
         *
         * @param DateTimeZone|null $oTimezone
         *
         * @return DateTime
         * @throws Exception
         * @psalm-suppress InvalidReturnType
         */
        public function getDate(?DateTimeZone $oTimezone = null): DateTime {
            if (!$oTimezone) {
                $oTimezone = new DateTimeZone("UTC");
            }

            $oPDO = $this->replicaOrSource();
            $sDriver = $oPDO->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($sDriver === 'sqlite') {
                return new DateTime('now');
            }

            return new DateTime($this->rawQuery('SELECT SYSDATE(6)')->fetchColumn(), $oTimezone);
        }

        /**
         * @param string $sModification
         *
         * @return DateTime
         * @throws Exception
         */
        public function getModifiedDate(string $sModification): DateTime {
            $oDate = $this->getDate();
            $oDate->modify($sModification);
            return $oDate;
        }

        /**
         * http://stackoverflow.com/a/15875555/14651
         * @return string
         * @throws Exception
         */
        public function getUUID(): string {
            $data = random_bytes(16);

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