<?php

namespace Enobrev\ORM;

interface SQLLogger {
    public function startQuery($sName = null);

    public function stopQuery($sSQL, array $aParams = null, $sName = null);
}
