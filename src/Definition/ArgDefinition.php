<?php

declare(strict_types=1);

namespace Aazsamir\Plap\Definition;

use BackedEnum;

readonly class ArgDefinition
{
    public function __construct(
        public string $name,
        public string $propertyName,
        public ?string $description = null,
        public ?int $position = null,
        public ?string $type = null,
        public ?bool $required = null,
        public mixed $default = null,
    ) {}

    public function isBoolean(): bool
    {
        return $this->type === 'bool';
    }

    public function isBackedEnum(): bool
    {
        return is_a($this->type, \BackedEnum::class, true);
    }

    public function isUnitEnum(): bool
    {
        return is_a($this->type, \UnitEnum::class, true);
    }

    public function isEnum(): bool
    {
        return $this->isBackedEnum() || $this->isUnitEnum();
    }

    /**
     * @return string[]
     */
    public function getEnumValues(): array
    {
        if (!$this->isEnum()) {
            throw new \LogicException("Argument {$this->name} is not an enum.");
        }

        if ($this->isBackedEnum()) {
            return array_map(fn(BackedEnum $case) => $case->value, $this->type::cases());
        }

        return array_map(fn(\UnitEnum $case) => $case->name, $this->type::cases());
    }
}