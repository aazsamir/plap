<?php

declare(strict_types=1);

namespace Aazsamir\Plap;

use Aazsamir\Plap\Definition\ArgDefinition;
use Aazsamir\Plap\Definition\ArgsDefinition;
use Aazsamir\Plap\Meta\Command;

class ArgsParser
{
    /**
     * @param mixed[] $args
     * @param class-string $toClass
     */
    public function __construct(
        private array $args,
        private string $toClass,
        private bool $consumeFirst = true,
    ) {}

    /**
     * @param class-string $toClass
     */
    public static function parseGlobals(string $toClass): object
    {
        $args = $_SERVER['argv'] ?? [];

        return self::parse($args, $toClass);
    }

    /**
     * @param class-string $toClass
     */
    public static function parse(array $args, string $toClass): object
    {
        $parser = new self($args, $toClass);

        return $parser->doParse();
    }

    public function doParse(): object
    {
        $command = $this->parseCommand($this->toClass);
        $argsDefinition = $command->argsDefinition;
        $reflection = new \ReflectionClass($this->toClass);
        $instance = $reflection->newInstanceWithoutConstructor();
        $values = $this->getArgValues($this->args, $argsDefinition);

        if (isset($values['help']) && $values['help'] === true) {
            $this->printUsage($command);
            exit(0);
        }

        foreach ($argsDefinition->args as $argDefinition) {
            if ($argDefinition->name === 'help') {
                continue;
            }

            $value = $values[$argDefinition->name] ?? null;

            if ($value === null && $argDefinition->required) {
                throw new \InvalidArgumentException("Missing required argument: --{$argDefinition->name}");
            }

            if ($value === null && $argDefinition->default !== null) {
                continue;
            }

            if ($value !== null) {
                $value = $this->coerceType($value, $argDefinition->type);
            }

            $reflectionProperty = $reflection->getProperty($argDefinition->propertyName);
            $reflectionProperty->setValue($instance, $value);
        }

        return $instance;
    }

    private function printUsage(Command $command): void
    {
        $lines = [];

        foreach ($command->argsDefinition->args as $argDefinition) {
            $line = [];
            $line[] = "  --{$argDefinition->name}";

            $value = $argDefinition->type;

            if ($argDefinition->isEnum()) {
                $value = implode('|', $argDefinition->getEnumValues());
            }

            $line[] = "[$value]";

            if ($argDefinition->description) {
                $line[] = "  {$argDefinition->description}";
            } else {
                $line[] = '';
            }

            $lines[] = $line;
        }

        // figure out the max length of each column
        $maxLengths = [];
        foreach ($lines as $line) {
            foreach ($line as $i => $column) {
                $maxLengths[$i] = max($maxLengths[$i] ?? 0, strlen($column));
            }
        }

        if (!empty($command->name)) {
            echo $command->name . "\n";
        }
        if (!empty($command->description)) {
            echo $command->description . "\n";
        }

        // print each line with padding
        foreach ($lines as $line) {
            foreach ($line as $i => $column) {
                echo str_pad($column, $maxLengths[$i] + 2, " ");
            }
            echo "\n";
        }
    }

    private function getArgValues(array $args, ArgsDefinition $argsDefinition): array
    {
        // we look for
        // --name=value
        // --name="value"
        // --name value
        // in case of boolean, we look for --name or --no-name
        if ($this->consumeFirst) {
            $consumed = [0]; // we always consume the first argument, which is the script name
        } else {
            $consumed = [];
        }
        $values = [];
        $rest = [];

        foreach ($args as $index => $arg) {
            if (\str_starts_with($arg, "--")) {
                $arg = ltrim($arg, '-');
                $argName = \strtok($arg, '=');
                $arg = \str_replace($argName, '', $arg);
                $arg = \str_starts_with($arg, '"') ? \substr($arg, 1) : $arg;
                $arg = \str_ends_with($arg, '"') ? \substr($arg, 0, -1) : $arg;

                // if the arg is in the form --name=value, we return the value
                if (\str_starts_with($arg, '=')) {
                    $values[$argName] = \substr($arg, 1);

                    continue;
                }

                $argDefinition = $this->getArgDefinition($argsDefinition, $argName);

                // if it is a boolean, we dont consume the next argument
                if ($argDefinition?->isBoolean()) {
                    $values[$argDefinition->name] = \str_starts_with($argName, 'no-') ? false : true;

                    continue;
                }

                // otherwise, we return the next argument as the value
                $nextValue = $args[$index + 1] ?? null;

                if ($nextValue && \str_starts_with($nextValue, '-')) {
                    $values[$argName] = null;

                    continue;
                }

                $consumed[] = $index + 1;
                $values[$argName] = $nextValue;

                continue;
            }
        }

        foreach ($args as $i => $arg) {
            if (in_array($i, $consumed, true)) {
                continue;
            }

            if (!\str_starts_with($arg, '-')) {
                $rest[] = $arg;
            }
        }

        foreach ($argsDefinition->args as $argDefinition) {
            if ($argDefinition->position === null) {
                continue;
            }

            if (!\array_key_exists($argDefinition->name, $values)) {
                $values[$argDefinition->name] = $rest[$argDefinition->position] ?? null;
            }
        }

        return $values;
    }

    private function getArgDefinition(ArgsDefinition $argsDefinition, string $argName): ?ArgDefinition
    {
        $argDefinition = $argsDefinition->getByName($argName);

        if ($argDefinition === null && \str_starts_with($argName, 'no-')) {
            $argName = \substr($argName, 3);
            $argDefinition = $argsDefinition->getByName($argName);

            if ($argDefinition?->isBoolean()) {
                return $argDefinition;
            }

            $argDefinition = null;
        }

        return $argDefinition;
    }

    private function parseCommand(string $toClass): Command
    {
        $reflectionClass = new \ReflectionClass($toClass);
        $commandAttribute = $reflectionClass->getAttributes(Meta\Command::class)[0] ?? null;
        /** @var ?Meta\Command $commandInstance */
        $commandInstance = $commandAttribute?->newInstance();
        $argsDefinition = $this->parseArgsDefinition($toClass);

        if ($commandInstance === null) {
            $commandInstance = new Command();
        }

        $commandInstance->argsDefinition = $argsDefinition;

        return $commandInstance;
    }

    private function parseArgsDefinition(string $toClass): ArgsDefinition
    {
        $reflectionClass = new \ReflectionClass($toClass);
        $args = [
            new ArgDefinition(
                name: 'help',
                propertyName: 'help',
                description: 'Show this help message',
                position: null,
                type: 'bool',
                required: false,
                default: false,
            ),
        ];

        foreach ($reflectionClass->getProperties() as $property) {
            $propertyName = $property->getName();
            $longName = $this->toKebabCase($propertyName);
            $argAttribute = $property->getAttributes(Meta\Arg::class)[0] ?? null;

            /** @var ?Meta\Arg $argInstance */
            $argInstance = $argAttribute?->newInstance();

            $args[] = new ArgDefinition(
                name: $longName,
                propertyName: $propertyName,
                description: $argInstance?->description,
                position: $argInstance?->position,
                type: $property->getType()?->getName(),
                required: !$property->getType()?->allowsNull() && $property->hasDefaultValue() === false,
                default: $property->hasDefaultValue() ? $property->getDefaultValue() : null,
            );
        }

        return new ArgsDefinition(args: $args);
    }

    private function toKebabCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
    }

    private function coerceType(mixed $value, string $type): mixed
    {
        if (is_a($type, \BackedEnum::class, true)) {
            return $type::from($value);
        }

        if (is_a($type, \UnitEnum::class, true)) {
            return $this->coerceUnitEnum($value, new $type());
        }

        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array' => explode(',', $value),
            'object' => json_decode($value, true),
            default => $value,
        };
    }

    private function coerceUnitEnum(string $value, \UnitEnum $enum): \UnitEnum
    {
        foreach ($enum::cases() as $case) {
            if ($case->name === $value) {
                return $case;
            }
        }

        throw new \InvalidArgumentException("Invalid value '$value' for enum " . get_class($enum));
    }
}
