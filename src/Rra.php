<?php

namespace IMEdge\RrdStructure;

use InvalidArgumentException;

use function substr;

abstract class Rra
{
    protected const BYTES_PER_DATAPOINT = 8;

    protected string $consolidationFunction;
    protected ?int $currentRow = null;
    protected int $rows;

    protected function __construct(string $consolidationFunction)
    {
        $this->consolidationFunction = $consolidationFunction;
    }

    abstract public function toString(): string;

    /**
     * Data Size used on disk
     */
    abstract public function getDataSize(): int;

    public function getRows(): int
    {
        return $this->rows;
    }

    public function getConsolidationFunction(): string
    {
        return $this->consolidationFunction;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function getCurrentRow(): ?int
    {
        return $this->currentRow;
    }

    /**
     * <code>
     * [
     *     'cf'          => 'AVERAGE',
     *     'rows'        => 2800,
     *     'cur_row'     => 359,
     *     'pdp_per_row' => 1,
     *     'xff'         => 0.5
     * ]
     * </code>
     *
     * TODO: What does this look like for non-compute RRAs?
     *
     * @param array{cf: string, rows: int, xff: float, pdp_per_row: int, cur_row: ?int} $info
     * @return RraAggregation
     */
    public static function fromRraInfo(array $info): RraAggregation
    {
        $cf = $info['cf'];
        if (RraAggregation::isKnown($cf)) {
            $rra = new RraAggregation($cf);
            $rra->setArgumentsFromInfo($info);
        } else {
            throw new InvalidArgumentException(
                "'$cf' is not a known consolidation function"
            );
        }

        $rra->currentRow = $info['cur_row'];

        return $rra;
    }

    /**
     * 'RRA:MIN:0.5:21600:5840'
     */
    public static function fromString(string $str): Rra
    {
        if (substr($str, 0, 4) !== 'RRA:') {
            throw new InvalidArgumentException(
                "An RRA must be prefixed with 'RRA:', got '$str'"
            );
        }
        $pos = \strpos($str, ':', 4);
        if ($pos === false) {
            throw new InvalidArgumentException(
                "An RRA must have the form 'RRA:CF:cf_arguments', got '$str'"
            );
        }

        $cf = substr($str, 4, $pos - 4);
        $args = substr($str, $pos + 1);
        if (RraAggregation::isKnown($cf)) {
            $rra = new RraAggregation($cf);
            $rra->setArgumentsFromString($args);

            return $rra;
        } else {
            throw new InvalidArgumentException(
                "'$cf' is not a known consolidation function"
            );
        }
    }
}
