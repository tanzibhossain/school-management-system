<?php

namespace App\Modules\Mark\Strategies;

use InvalidArgumentException;

class ResultStrategyFactory
{
    /** @var array<string, class-string<ResultStrategy>> */
    private const MAP = [
        'bd_national' => BdNationalStrategy::class,
        'simple_average' => SimpleAverageStrategy::class,
        'weighted_average' => WeightedAverageStrategy::class,
        'percentage_only' => PercentageOnlyStrategy::class,
    ];

    public static function make(string $name): ResultStrategy
    {
        if (! isset(self::MAP[$name])) {
            throw new InvalidArgumentException("Unknown result strategy [{$name}].");
        }

        $class = self::MAP[$name];

        return new $class;
    }
}
