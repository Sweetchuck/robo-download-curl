<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\DownloadCurl\Tests\Unit\Task;

use Sweetchuck\Robo\DownloadCurl\Tests\UnitTester;

class DownloadTaskTest extends TaskTestBase
{
    protected UnitTester $tester;

    protected function initTask(): static
    {
        $this->task = $this->taskBuilder->taskDownloadCurl();

        return $this;
    }

    public function testRunSuccess(): void
    {
        $options = [
            'uri' => 'file://' . codecept_data_dir() . '/dummy.txt',
            'destination' => (string) tempnam(sys_get_temp_dir(), 'robo-download-curl-'),
        ];

        $this->task->setOptions($options);
        $this->task->run();

        $this->tester->assertFileEquals(
            $options['uri'],
            $options['destination'],
            'downloaded file content',
        );
    }
}
