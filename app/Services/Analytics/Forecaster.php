<?php

namespace App\Services\Analytics;

/**
 * Monthly forecasting.
 *
 * - With at least two full years of history: additive Holt-Winters
 *   (level + trend + 12-month seasonality). alpha/beta/gamma are chosen by a
 *   small grid search that minimises the one-step-ahead squared error.
 * - With less history: ordinary least-squares trend blended with the last
 *   three months' average (robust on short, noisy series).
 */
class Forecaster
{
    public const SEASON = 12;

    /**
     * @param  array<int, float>  $series  oldest → newest monthly values
     * @return array{method: string, values: array<int, float>, confidence: string, mape: float|null, params: array<string, float>|null}
     */
    public function forecast(array $series, int $horizon = 3): array
    {
        $series = array_values(array_map('floatval', $series));
        $history = $this->trimLeadingZeros($series);

        if (count($history) >= 2 * self::SEASON) {
            return $this->holtWinters($history, $horizon);
        }

        return $this->linear($series, $horizon);
    }

    /**
     * @param  array<int, float>  $y
     * @return array{method: string, values: array<int, float>, confidence: string, mape: float|null, params: array<string, float>}
     */
    public function holtWinters(array $y, int $horizon): array
    {
        $best = null;

        foreach ([0.1, 0.3, 0.5, 0.7, 0.9] as $alpha) {
            foreach ([0.01, 0.05, 0.1, 0.2, 0.3] as $beta) {
                foreach ([0.05, 0.1, 0.3, 0.5] as $gamma) {
                    $fit = $this->fitHoltWinters($y, $alpha, $beta, $gamma);
                    if ($best === null || $fit['sse'] < $best['sse']) {
                        $best = $fit + ['params' => compact('alpha', 'beta', 'gamma')];
                    }
                }
            }
        }

        $n = count($y);
        $values = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $season = $best['seasonals'][($n + $h - 1) % self::SEASON];
            $values[] = round(max(0, $best['level'] + $h * $best['trend'] + $season), 2);
        }

        return [
            'method' => 'holt_winters',
            'values' => $values,
            'confidence' => $this->confidence($best['mape']),
            'mape' => $best['mape'] !== null ? round($best['mape'], 1) : null,
            'params' => $best['params'],
        ];
    }

    /**
     * @param  array<int, float>  $y
     * @return array{sse: float, mape: float|null, level: float, trend: float, seasonals: array<int, float>}
     */
    private function fitHoltWinters(array $y, float $alpha, float $beta, float $gamma): array
    {
        $m = self::SEASON;
        $first = array_slice($y, 0, $m);
        $second = array_slice($y, $m, $m);

        $mean = array_sum($first) / $m;
        $trend = (array_sum($second) / $m - $mean) / $m;
        // De-trended seasonal indices; the level is moved to the end of the first season.
        $seasonals = [];
        foreach ($first as $i => $v) {
            $seasonals[$i] = $v - ($mean + $trend * ($i - ($m - 1) / 2));
        }
        $level = $mean + $trend * ($m - 1) / 2;

        $sse = 0.0;
        $apeSum = 0.0;
        $apeCount = 0;

        for ($t = $m; $t < count($y); $t++) {
            $s = $seasonals[$t % $m];
            $prediction = $level + $trend + $s;
            $error = $y[$t] - $prediction;
            $sse += $error ** 2;
            if ($y[$t] > 0) {
                $apeSum += abs($error) / $y[$t];
                $apeCount++;
            }

            $previousLevel = $level;
            $level = $alpha * ($y[$t] - $s) + (1 - $alpha) * ($level + $trend);
            $trend = $beta * ($level - $previousLevel) + (1 - $beta) * $trend;
            $seasonals[$t % $m] = $gamma * ($y[$t] - $level) + (1 - $gamma) * $s;
        }

        return [
            'sse' => $sse,
            'mape' => $apeCount > 0 ? 100 * $apeSum / $apeCount : null,
            'level' => $level,
            'trend' => $trend,
            'seasonals' => $seasonals,
        ];
    }

    /**
     * @param  array<int, float>  $y
     * @return array{method: string, values: array<int, float>, confidence: string, mape: float|null, params: null}
     */
    public function linear(array $y, int $horizon): array
    {
        $n = count($y);
        $sumX = $sumY = $sumXY = $sumXX = 0.0;
        foreach ($y as $x => $v) {
            $sumX += $x;
            $sumY += $v;
            $sumXY += $x * $v;
            $sumXX += $x * $x;
        }

        $denominator = $n * $sumXX - $sumX ** 2;
        $slope = $denominator != 0 ? ($n * $sumXY - $sumX * $sumY) / $denominator : 0.0;
        $intercept = $n > 0 ? ($sumY - $slope * $sumX) / $n : 0.0;
        $recent = $n > 0 ? array_sum(array_slice($y, -3)) / min(3, $n) : 0.0;

        $values = [];
        for ($h = 1; $h <= $horizon; $h++) {
            $values[] = round(max(0, 0.6 * ($intercept + $slope * ($n - 1 + $h)) + 0.4 * $recent), 2);
        }

        $nonZero = count(array_filter($y));

        return [
            'method' => 'linear',
            'values' => $values,
            'confidence' => $nonZero >= 6 ? 'medium' : 'low',
            'mape' => null,
            'params' => null,
        ];
    }

    private function confidence(?float $mape): string
    {
        return match (true) {
            $mape === null => 'low',
            $mape <= 15 => 'high',
            $mape <= 35 => 'medium',
            default => 'low',
        };
    }

    /**
     * @param  array<int, float>  $series
     * @return array<int, float>
     */
    private function trimLeadingZeros(array $series): array
    {
        while ($series !== [] && (float) $series[0] === 0.0) {
            array_shift($series);
        }

        return array_values($series);
    }
}
