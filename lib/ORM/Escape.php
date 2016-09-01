<?php
    namespace Enobrev\ORM;

    class Escape {
        /**
         * @param string $sString
         * @return string
         */
        public static function string(string $sString) {
            if (defined('PHPUNIT_ENOBREV_ORM_TESTSUITE') === true) {
                return strtr($sString, [
                    "\x00"  => '\x00',
                    "\n"    => '\n',
                    "\r"    => '\r',
                    "\\"    => '\\\\',
                    "'"     => "\'",
                    '"'     => '\"',
                    "\x1a"  => '\x1a'
                ]);
            }

            return Db::getInstance()->real_escape_string($sString);
        }
    }