<?php
    namespace Enobrev;

    use Enobrev\ORM\Exceptions\DbDeadlockException;

    class Handler {
        /**
         * @template T
         * @param string   $sLogName
         * @param callable $fFunction
         * @param int      $iMaxAttempts
         *
         * @return T
         * @throws DbDeadlockException
         */
        public static function DbDeadlock(string $sLogName, callable $fFunction, int $iMaxAttempts) {
            $iTries = 0;
            while($iTries < $iMaxAttempts) {
                $iTries++;
                try {
                    return $fFunction();
                } catch (DbDeadlockException $e) {
                    if ($iTries >= $iMaxAttempts - 1) {
                        Log::ex($sLogName, $e, ['state' => 'Deadlock.Fail', 'tries' => $iTries]);
                        throw $e;
                    }

                    $iSleep = pow($iTries, 2);
                    Log::w($sLogName, ['state' => 'Deadlock.Retry', 'tries' => $iTries, 'sleep' => $iSleep]);
                    sleep(random_int($iTries, $iSleep));
                }
            }

            return null;
        }
    }