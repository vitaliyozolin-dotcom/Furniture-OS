<?php
// Usage: php scripts/check-coverage.php apps/api/var/coverage.xml
$report = simplexml_load_file($argv[1]);
$overall = $report->project->metrics;
$check = static function ($metrics, float|array $minimum, string $label): void {
    foreach (['statements' => 'coveredstatements', 'conditionals' => 'coveredconditionals'] as $all => $covered) {
        $total = (int) $metrics[$all];
        if ($total === 0 && $all === 'conditionals') {
            throw new RuntimeException($label.': branch coverage missing; run Xdebug with --path-coverage');
        }
        $threshold = is_array($minimum) ? $minimum[$all] : $minimum;
        $percentage = $total > 0 ? 100 * (int) $metrics[$covered] / $total : 100;
        printf("%s %s: %.2f%% (minimum %.2f%%)\n", $label, $all, $percentage, $threshold);
        if ($percentage + 0.00001 < $threshold) { throw new RuntimeException($label.' coverage below threshold'); }
    }
};
$check($overall, ['statements' => 93.42, 'conditionals' => 88.17], 'Backend');
$totals = ['statements' => 0, 'coveredstatements' => 0, 'conditionals' => 0, 'coveredconditionals' => 0];
foreach ($report->xpath('//file') as $file) {
    if (str_contains((string) $file['name'], '/Calculation/Domain/')) {
        foreach ($totals as $key => $_) { $totals[$key] += (int) $file->metrics[$key]; }
    }
}
$check($totals, ['statements' => 100.0, 'conditionals' => 90.38], 'Calculation Domain');
