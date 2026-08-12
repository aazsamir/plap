<?php

declare(strict_types=1);

namespace Aazsamir\Plap\Definition;

readonly class ArgsDefinition
{
    /**
     * @param ArgDefinition[] $args
     */
    public function __construct(
        public array $args = [],
    ) {}

    public function getByName(string $name): ?ArgDefinition
    {
        foreach ($this->args as $arg) {
            if ($arg->name === $name) {
                return $arg;
            }
        }

        return null;
    }
}
