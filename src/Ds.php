<?php

namespace IMEdge\RrdStructure;

// DS:ds-name[=mapped-ds-name[[source-index]]]:DST:dst arguments
use gipfl\Json\JsonSerialization;
use stdClass;

use function explode;

class Ds implements JsonSerialization
{
    /**
     * ds-name must be 1 to 19 characters long, allowed chars: [a-zA-Z0-9_]
     */
    protected string $name;

    /**
     * COUNTER|GAUGE|ABSOLUTE|DERIVE|DCOUNTER|DDERIVE - and COMPUTE
     */
    protected string $type;

    protected int $heartbeat;
    protected ?int $min = null;
    protected ?int $max = null;

    protected ?string $alias = null;
    protected ?string $mappedName = null;

    public function __construct(
        string $name,
        string $type,
        int $heartbeat,
        ?int $min = null, // TODO: allow floats?
        ?int $max = null,
        ?string $mappedName = null,
        ?string $alias = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->heartbeat = $heartbeat;
        $this->min  = $min;
        $this->max  = $max;
        $this->mappedName = $mappedName;
        $this->alias = $alias;
    }

    public static function fromString(string $string): Ds
    {
        $parts = explode(':', $string);
        if (count($parts) < 4) {
            throw new \InvalidArgumentException("Valid DataSource expected, got $string");
        }
        if ('DS' !== array_shift($parts)) {
            throw new \InvalidArgumentException("Valid DataSource expected, got $string");
        }
        $min = $parts[3] ?? null;
        if ($min === 'U') {
            $min = null;
        } else {
            $min = (int) $min;
        }
        $max = $parts[4] ?? null;
        if ($max === 'U') {
            $max = null;
        } else {
            $max = (int) $max;
        }

        return new Ds($parts[0], $parts[1], (int) $parts[2], $min, $max);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHeartbeat(): int
    {
        return $this->heartbeat;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function hasMin(): bool
    {
        return $this->min !== null;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function hasMax(): bool
    {
        return $this->max !== null;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function getMappedName(): ?string
    {
        return $this->mappedName;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * @param string|null $alias
     * @return Ds
     */
    public function setAlias(?string $alias): Ds
    {
        $this->alias = $alias;
        return $this;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        // U -> unknown
        $min = $this->min === null ? 'U' : $this->min;
        $max = $this->max === null ? 'U' : $this->max;
        $dsParams = "$min:$max";

        return \sprintf(
            "DS:%s:%s:%d:%s",
            $this->name . ($this->mappedName ? '=' . $this->mappedName : ''),
            $this->type,
            $this->heartbeat,
            $dsParams
        );
    }

    /**
     * @param stdClass $any
     * @return Ds
     */
    public static function fromSerialization($any): Ds
    {
        if (! is_object($any)) {
            throw new \RuntimeException('Cannot instantiate a DS from  ' . get_debug_type($any));
        }

        return new Ds(
            $any->name,
            $any->type,
            $any->heartbeat,
            $any->min ?? null,
            $any->max ?? null,
            $any->mappedName ?? null,
            $any->alias ?? null,
        );
    }

    public function jsonSerialize(): object
    {
        return (object) [
            'name'       => $this->name,
            'type'       => $this->type,
            'heartbeat'  => $this->heartbeat,
            'min'        => $this->min,
            'max'        => $this->max,
            'alias'      => $this->alias,
            'mappedName' => $this->mappedName,
        ];
    }
}
