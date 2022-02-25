<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\DownloadCurl;

use Robo\Collection\CollectionBuilder;
use Sweetchuck\Robo\DownloadCurl\Task\DownloadTask;

trait DownloadCurlTaskLoader
{
    /**
     * @param array<string, mixed> $options
     * @phpstan-param taskDownloadCurlOptions $options
     *
     * @return \Sweetchuck\Robo\DownloadCurl\Task\DownloadTask|\Robo\Collection\CollectionBuilder
     */
    protected function taskDownloadCurl(array $options = []): CollectionBuilder
    {
        /** @var \Sweetchuck\Robo\DownloadCurl\Task\DownloadTask $task */
        $task = $this->task(DownloadTask::class);
        $task->setOptions($options);

        return $task;
    }
}
