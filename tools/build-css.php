<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$cssDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'jobboard' . DIRECTORY_SEPARATOR . 'css';

$targets = [
    'candidate-bundle.min.css' => [
        'candidate-pages.css',
        'hirematrix-style.css',
    ],
    'hirematrix-style.min.css' => [
        'hirematrix-style.css',
    ],
    'responsive.min.css' => [
        'responsive.css',
    ],
    'candidatedark.min.css' => [
        'candidatedark.css',
    ],
];

$minify = static function (string $css): string {
    $css = preg_replace('~/\*[^!*][\s\S]*?\*/~', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;
    $css = preg_replace('/\s*([{}:;,>~])\s*/', '$1', $css) ?? $css;
    $css = preg_replace('/;}/', '}', $css) ?? $css;

    return trim($css);
};

foreach ($targets as $output => $sources) {
    $parts = [];

    foreach ($sources as $source) {
        $sourcePath = $cssDir . DIRECTORY_SEPARATOR . $source;
        if (!is_file($sourcePath)) {
            fwrite(STDERR, "Missing CSS source: {$source}\n");
            exit(1);
        }

        $parts[] = "/* {$source} */\n" . file_get_contents($sourcePath);
    }

    $outputPath = $cssDir . DIRECTORY_SEPARATOR . $output;
    file_put_contents($outputPath, $minify(implode("\n", $parts)) . "\n");
    echo $output . ' ' . number_format(filesize($outputPath)) . " bytes\n";
}
