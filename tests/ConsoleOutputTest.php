<?php

namespace Tests;

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionProperty;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use TightenCo\Jigsaw\Console\ConsoleOutput;

class ConsoleOutputTest extends PHPUnitTestCase
{
    #[Test]
    public function second_section_overwrite_does_not_emit_raw_style_tags(): void
    {
        if (version_compare($this->symfonyConsoleVersion(), '8.0.0', '<')) {
            $this->markTestSkipped('Regression only applies to Symfony Console 8+.');
        }

        $output = $this->captureConsoleOutput();

        $output->setup(OutputInterface::VERBOSITY_NORMAL);
        $output->writeIntro('local');
        $output->writeTime('13.1');
        $output->writeWritingFiles();
        $output->writeConclusion();

        $content = $this->readStream($output);

        $this->assertStringContainsString('Build time:', $content);
        $this->assertStringContainsString('Site built successfully!', $content);
        $this->assertStringNotContainsString('<fg=', $content);
        $this->assertStringNotContainsString('</>', $content);
    }

    private function captureConsoleOutput(): ConsoleOutput
    {
        $stream = fopen('php://memory', 'w+');
        $output = new ConsoleOutput;
        $output->setDecorated(true);

        $streamProperty = new ReflectionProperty(StreamOutput::class, 'stream');
        $streamProperty->setAccessible(true);
        $streamProperty->setValue($output, $stream);

        return $output;
    }

    private function readStream(ConsoleOutput $output): string
    {
        $streamProperty = new ReflectionProperty(StreamOutput::class, 'stream');
        $streamProperty->setAccessible(true);
        $stream = $streamProperty->getValue($output);

        rewind($stream);

        return stream_get_contents($stream);
    }

    private function symfonyConsoleVersion(): string
    {
        return InstalledVersions::getVersion('symfony/console');
    }
}
