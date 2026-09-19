<?php

namespace QUI\Tests\Matomo\Unit;

use PHPUnit\Framework\TestCase;

class DataLayerTest extends TestCase
{
    public function testJavaScriptTrackingBehavior(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('The JavaScript regression tests require proc_open and Node.js.');
        }

        $process = proc_open(
            ['node', '--test', dirname(__DIR__, 3) . '/javascript/dataLayer.test.cjs'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        self::assertIsResource($process);

        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode === 127) {
            self::markTestSkipped('Node.js is required to execute the browser JavaScript regression tests.');
        }

        self::assertSame(0, $exitCode, $output . $errors);
    }
}
