<?php
    namespace Enobrev\ORM;

    use InvalidArgumentException;

    class DateFunction {
        /** @var string */
        private $sType;

        public const FUNC_NOW = 'NOW()';

        /** @var array */
        private static $aSupported = [
            self::FUNC_NOW
        ];

        /**
         * @param string $sType
         */
        private function __construct(string $sType) {
            if (!self::isSupportedType($sType)) {
                throw new InvalidArgumentException('Connection status type ' . $sType . ' not supported.');
            }

            $this->sType = $sType;
        }

        /**
         * @param string $sType
         * @return DateFunction
         */
        public static function createFromString(string $sType): self {
            return new self($sType);
        }

        /**
         * @return DateFunction
         */
        public static function NOW(): DateFunction {
            return new self('NOW()');
        }

        /**
         * @return array
         */
        public static function getSupportedTypes(): array {
            return self::$aSupported;
        }

        /**
         * @param string $sType
         * @return bool
         */
        public static function isSupportedType(string $sType = null): bool {
            return in_array($sType, self::$aSupported, true);
        }

        /**
         * @return string
         */
        public function getName(): string {
            return $this->sType;
        }
    }