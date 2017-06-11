?php
// Broken PHP init to avoid indexing on clients (specifically for intellij
    declare(strict_types=1);

    const FAIL    = '~~FAIL~~';
    const SKIPPED = '~SKIPPED~';

    function dbg(...$aArgs) {
        echo str_replace(["Array\n(\n", "\n)\n"], '', print_r($aArgs, true)), "\n\n";
    }

    class Input {
        private $sInput;
        private $iPosition;

        public function __construct($sInput) {
            $this->sInput     = $sInput;
            $this->iPosition  = 0;
            $this->iMark      = 0;
        }

        private function remaining($iLength = null) {
            if (!empty($iLength)) {
                return substr($this->sInput, $this->iPosition, $iLength);
            }

            return substr($this->sInput, $this->iPosition);
        }

        private function isComplete() {
            return strlen($this->sInput) <= $this->iPosition;
        }

        public function compare($sString) {
            if ($this->isComplete()) {
                return false;
            }

            $iLength = strlen($sString);
            $bMatch  = $this->remaining($iLength) == $sString;

            if ($bMatch) {
                $this->iPosition    += $iLength;
            }

            return $bMatch ? $sString : false;
        }

        public function match($sRegex, $iGroup = 0, $sModifier = '') {
            if ($this->isComplete()) {
                return false;
            }

            $aMatches = [];
            $sInput   = $this->remaining();
            $sRegex   = '/^' . $sRegex . '/' . $sModifier;
            $bMatch   = preg_match($sRegex, $sInput, $aMatches);

            if ($bMatch) {
                $this->iPosition    += strlen($aMatches[0]);
            }

            return $bMatch ? $aMatches[$iGroup] : false;
        }

        public function mark() {
            $this->iMark = $this->iPosition;
            return $this->iMark;
        }

        public function rewind($iMark = null) {
            $this->iPosition = $iMark ?: $this->iMark;
        }
    }

    function char(string $sChar) {
        return function(Input $oInput) use ($sChar) {
            return $oInput->compare($sChar) ?: FAIL;
        };
    }

    function regex(string $sRegex, int $iGroup = 0, string $sModifier = '') {
        return function(Input $oInput) use ($sRegex, $iGroup, $sModifier) {
            return $oInput->match($sRegex, $iGroup, $sModifier) ?: FAIL;
        };
    }

    function skip(callable $fParser) {
        return function(Input $oInput) use ($fParser) {
            $fParser($oInput);
            return SKIPPED;
        };
    }

    function seq(callable ...$aParsers) {
        return function(Input $oInput) use ($aParsers) {
            $aResults = [];

            foreach($aParsers as $fParser) {
                $sResult = $fParser($oInput);
                if ($sResult == FAIL) {
                    return FAIL;
                }

                if ($sResult != SKIPPED) {
                    $aResults[] = $sResult;
                }
            }

            return $aResults;
        };
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

    function multi(callable $fParser) {
        return function(Input $oInput) use ($fParser) {
            $aResults = [];
            $iCount   = 0;

            while(true) {
                $sResult = $fParser($oInput);

                if ($sResult == FAIL) {
                    break;
                }

                $aResults[] = $sResult;
                $iCount++;
            }

            return $aResults;
        };
    }

    function parse(string $sInput, callable $fParser) {
        $oInput = new Input($sInput);
        $mResult = $fParser($oInput);
        return $mResult;
    }

    dbg(
        parse('abc 123 xyz banana', multi(seq(regex('[a-z0-9]+'), skip(char(' ')))))
    );


