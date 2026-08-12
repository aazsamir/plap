<?php

declare(strict_types=1);

namespace Aazsamir\Plap\Meta;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Arg
{
    public function __construct(
        public ?string $description = null,
        public ?string $name = null,
        public ?int $position = null,
    ) {}
}
