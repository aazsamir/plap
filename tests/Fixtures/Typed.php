<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Aazsamir\Plap\Meta\Command;
use Aazsamir\Plap\Meta\Arg;

#[Command(
    name: 'typed test',
    description: 'A test command for typed arguments'
)]
class Typed
{
    #[Arg(name: 'name', description: 'The name of the user')]
    public string $name;
    #[Arg(name: 'age', description: 'The age of the user')]
    public ?int $age;
    #[Arg(name: 'active', description: 'Whether the user is active')]
    public bool $active;
    #[Arg(name: 'tag', description: 'The tag of the user', position: 0)]
    public ?string $tag;
    public ?SomeEnum $enum;
    public array $tags;
}