?php
// Broken PHP init to avoid indexing on clients (specifically for intellij
    declare(strict_types=1);

    const FAIL    = '~FAIL~';
    const SKIPPED = '~SKIPPED~';

    class Input {
        private $sInput;
        private $iPosition;
        private $iLength;

        public function __construct($sInput) {
            $this->sInput     = $sInput;
            $this->iPosition  = 0;
            $this->iMark      = 0;
            $this->iLength    = strlen($this->sInput);
        }

        private function remaining($iLength = null) {
            if ($iLength) {
                return substr($this->sInput, $this->iPosition, $iLength);
            }

            if ($this->iPosition) {
                return substr($this->sInput, $this->iPosition);
            }

            return $this->sInput;
        }

        public function get($iLength = null) {
            if (!$iLength) {
                $iLength = $this->iLength - $this->iPosition;
            }

            $sResponse = $this->remaining($iLength);
            $this->iPosition    += strlen($sResponse);

            return $sResponse;
        }

        public function compare($sString) {
            $iLength = strlen($sString);
            $bMatch  = $this->remaining($iLength) == $sString;

            if ($bMatch) {
                $this->iPosition    += $iLength;
            }

            return $bMatch ? $sString : false;
        }

        public function match($sRegex, $iGroup = 0, $sModifier = '') {
            $aMatches = [];
            $bMatch   = preg_match('/^' . $sRegex . '/' . $sModifier, $this->remaining(), $aMatches);

            if ($bMatch) {
                $this->iPosition    += strlen($aMatches[0]);
            }

            return $bMatch ? $aMatches[$iGroup] : false;
        }

        public function complete() {
            return $this->iPosition >= $this->iLength;
        }

        public function mark() {
            $this->iMark = $this->iPosition;
            return $this->iMark;
        }

        public function rewind($iMark = null) {
            $this->iPosition = $iMark ?: $this->iMark;
        }
    }


    function str(string $sWord) {
        return function(Input $oInput) use ($sWord) {
            $sResult = $oInput->compare($sWord);
            return $sResult ? ['type' => 'string', 'value' => $sResult] : FAIL;
        };
    }

    function regex(string $sRegex, int $iGroup = 0, string $sModifier = '') {
        return function(Input $oInput) use ($sRegex, $iGroup, $sModifier) {
            $sResult = $oInput->match($sRegex, $iGroup, $sModifier);
            return $sResult ? ['type' => 'regex', 'value' => $sResult] : FAIL;
        };
    }

    function altStr(string ...$aStrings) {
        $aParsers = [];
        foreach($aStrings as $sString) {
            $aParsers[] = str($sString);
        }

        return alt(...$aParsers);
    }

    function alt(callable ...$aParsers) {
        return function(Input $oInput) use ($aParsers) {
            foreach($aParsers as $fParser) {
                $aResult = $fParser($oInput);
                if ($aResult != FAIL) {
                    return $aResult;
                }
            }

            return FAIL;
        };
    }

    function seq(callable ...$aParsers) {
        return function(Input $oInput) use ($aParsers) {
            $aResults = [];
            foreach($aParsers as $fParser) {
                $aResult = $fParser($oInput);

                if ($aResult == FAIL) {
                    return FAIL;
                }

                if ($aResult != SKIPPED) {
                    $aResults[] = $aResult;
                }
            }

            return $aResults;
        };
    }

    function optional(callable ...$aParsers) {
        return function(Input $oInput) use ($aParsers) {
            $aResults = [];
            $iMarker  = $oInput->mark();

            foreach($aParsers as $fParser) {
                $aResult = $fParser($oInput);

                if ($aResult == FAIL || $aResult == SKIPPED) {
                    break;
                }

                $aResults[] = $aResult;
            }

            if (count($aResults) == 0) {
                $oInput->rewind($iMarker);
                return SKIPPED;
            }

            return $aResults;
        };
    }

    function multi(callable $fParser, $iMin = 0, $iMax = null) {
        return function(Input $oInput) use ($fParser, $iMin, $iMax) {
            $aResults = [];
            $iMarker  = $oInput->mark();
            $iCount   = 0;

            while($iMax === null || $iCount < $iMax) {
                $sResult = $fParser($oInput);

                if ($sResult == FAIL) {
                    break;
                }

                $aResults[] = $sResult;
                $iCount++;
            }

            if (count($aResults) < $iMin) {
                $oInput->rewind($iMarker);
                return FAIL;
            }

            return $aResults;
        };
    }

    function skip(callable $fParser) {
        return function(Input $oInput) use ($fParser) {
            $fParser($oInput);
            return SKIPPED;
        };
    }

    function eof() {
        return function(Input $oInput) {
            return $oInput->complete() ? SKIPPED : FAIL;
        };
    }

    function any() {
        return function(Input $oInput) {
            return $oInput->get(1);
        };
    }

    function all() {
        return function(Input $oInput) {
            return $oInput->get();
        };
    }

    function space() {
        return regex('\s');
    }

    function spaces() {
        return regex('\s*');
    }

    function digit() {
        return type(__FUNCTION__, regex('[0-9]'));
    }

    function digits() {
        return type(__FUNCTION__, regex('[0-9]+'));
    }

    function letter() {
        return type(__FUNCTION__, regex('[a-z]', 0, 'i'));
    }

    function letters() {
        return type(__FUNCTION__, regex('[a-z]+', 0, 'i'));
    }

    function alpha() {
        return type(__FUNCTION__, regex('[0-9a-z]', 0, 'i'));
    }

    function alphas() {
        return type(__FUNCTION__, regex('[a-z0-9]+', 0, 'i'));
    }

    function quoted() {
        return type(__FUNCTION__, regex('"([^"]*)"', 1));
    }

    function nonSpace() {
        return type(__FUNCTION__, regex('[^\s]+'));
    }

    function skipSpaces() {
        return skip(spaces());
    }

    function type($sType, $fParser) {
        return function(Input $oInput) use ($sType, $fParser) {
            $mResult = $fParser($oInput);
            if ($mResult == FAIL) {
                return FAIL;
            }

            $mResult['type'] = $sType;

            return $mResult;
        };
    }

    function parse(string $sInput, callable $fParser) {
        return $fParser(new Input($sInput));
    }

    /* ********************************* */

    function table(string $sTable) {
        return optional(
            type('table', str($sTable)),
            skip(str('.'))
        );
    }

    function fields(string ...$aFields) {
        return type('column', altStr(...$aFields));
    }

    function tableColumn(string $sTable, string ...$aFields) {
        return seq(
            optional(
                type('table', str($sTable)),
                skip(str('.'))
            ),
            fields(...$aFields)
        );
    }

    function usersTable() {
        return tableColumn('users', 'id', 'name', 'date_added', 'email');
    }

    function addressesTable() {
        return tableColumn('addresses', 'id', 'line_1', 'date_added', 'city');
    }

    function fieldOrValue() {
        return alt(
            usersTable(),
            addressesTable(),
            quoted(),
            digits(),
            alphas()
        );
    }

    function between() {
        return type('between', seq(
            fieldOrValue(),
            str('..'),
            fieldOrValue()
        ));
    }

    function like() {
        return type('like', regex('(?=.*[*_])[a-z0-9*_]+'));
    }

    function condition() {
        return seq(
            alt(
                usersTable(),
                addressesTable()
            ),
            type('divider', str(':')),
            alt(
                quoted(),
                like(),
                between(),
                nonSpace()
            ),
            skipSpaces()
        );
    }

    function dbg(...$aArgs) {
        echo print_r($aArgs, 1) . "\n";
    }

    function conditions() {
        return multi(condition());
    }

    $aInputs = [
        'users.id:1..30',
        'id:1..30',
        'users.id:1..30'/*,
        'addresses.id:1..30',
        'name:value123',
        'name:val*',
        'name:"quoted values"'*/
    ];

    /*
    foreach($aInputs as $sInput) {
        echo 'Result |' . $sInput . '|: ' . print_r(parse($sInput,  conditions()), 1) . "\n";
    }
    */

    $sInputs = implode(' ', $aInputs);
    dbg('Result', $sInputs, parse($sInputs,  conditions()));