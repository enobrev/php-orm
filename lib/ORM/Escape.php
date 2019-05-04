<?php
    namespace Enobrev\ORM;

    use PDO;

    class Escape {
        /**
         * @param string $sString
         * @param int    $sPDOType
         * @return string
         * @throws DbException
         */
        public static function string(string $sString, int $sPDOType = PDO::PARAM_STR): string {
            if (defined('PHPUNIT_ENOBREV_ORM_TESTSUITE') === true) {
                $sReturn = strtr($sString, [
                    "\x00"  => '\x00',
                    "\n"    => '\n',
                    "\r"    => '\r',
                    "\\"    => '\\\\',
                    "'"     => "\'",
                    '"'     => '\"',
                    "\x1a"  => '\x1a'
                ]);

                if ($sPDOType !== PDO::PARAM_INT
                &&  $sPDOType !== PDO::PARAM_BOOL) {
                    $sReturn = '"' . $sReturn .'"';
                }

                return $sReturn;
            }

            return Db::getInstance()->quote($sString, $sPDOType);
        }
    }