<?php
    namespace Enobrev\ORM;

    use InvalidArgumentException;

    class DateFunction {
        private string $sType;

        public const FUNC_NOW = 'NOW()';

        private static array $aSupported = [
            self::FUNC_NOW
        ];

        private function __construct(string $sType) {
            if (!self::isSupportedType($sType)) {
                throw new InvalidArgumentException('Connection status type ' . $sType . ' not supported.');
            }

            $this->sType = $sType;
        }

        public static function createFromString(string $sType): self {
            return new self($sType);
        }

        public static function NOW(): DateFunction {
            return new self('NOW()');
        }

        public static function getSupportedTypes(): array {
            return self::$aSupported;
        }

        public static function isSupportedType(string $sType = null): bool {
            return in_array($sType, self::$aSupported, true);
        }

        public function getName(): string {
            return $this->sType;
        }
    }