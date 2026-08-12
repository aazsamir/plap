<?php

declare(strict_types=1);

namespace Aazsamir\Plap\Meta;

use Aazsamir\Plap\Definition\ArgsDefinition;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Command
{
    public ArgsDefinition $argsDefinition;

    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}
