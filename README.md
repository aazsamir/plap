# plap

A PHP library for parsing command-line arguments into a class instance.

## Usage

```php
<?php

#[Command(description: 'Greet a user')]
class GreetArgs {
    #[Arg(description: 'The name of the user')]
    public string $name;
    #[Arg(description: 'The number of times to greet the user')]
    public int $count;
    #[Arg(description: 'Greeting message', position: 0)]
    public ?string $greeting = 'Hello';
}

$args = ArgsParser::parseGlobals(GreetArgs::class);
dd($args);
```

```
./greet.php --name=John --count=3 "hello world!"
```

> **Note**
>
> You can skip all the attributes, but you lose the ability to define positional arguments and descriptions.

## Post Scriptum

I pushed it to github for convenience of using it in my own projects. I don't expect anyone to use it, but if you do and need support, feel free to open an issue. I will try to help if I can.

## License

This project is licensed under the MIT License.