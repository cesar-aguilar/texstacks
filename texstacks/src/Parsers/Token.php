<?php

namespace TexStacks\Parsers;

class Token {

    public readonly string $type;
    public readonly string $command_name;
    public readonly string $command_content;
    public readonly array $command_args;
    public readonly string $command_options;
    public readonly string $command_src;
    public readonly string $body;
    public readonly int $line_number;

    public function __construct($args) {
        $this->type = $args['type'];
        $this->command_name = $args['command_name'] ?? '';
        $this->command_content = $args['command_content'] ?? '';
        $this->command_args = $args['command_args'] ?? [];
        $this->command_options = $args['command_options'] ?? '';
        $this->command_src = $args['command_src'] ?? '';
        $this->body = $args['body'] ?? '';
        $this->line_number = $args['line_number'];
    }

    public function __toString(): string {

        $as_array = (array) $this;

        $as_array = array_filter($as_array);

        $array = [];

        foreach ($as_array as $key => $value) {
            $array[] = $key . ': ' . $value;
        }

        $str = implode(' | ', $array);

        return "<span style=\"font-size:0.8rem\">$str</span>";
    }
}