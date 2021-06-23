<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\DownloadCurl\Tests\Helper\RoboFiles;

use Robo\Tasks;
use Sweetchuck\Robo\DownloadCurl\DownloadCurlTaskLoader;
use Robo\Contract\TaskInterface;

class RoboFileAcceptance extends Tasks
{
    use DownloadCurlTaskLoader;

    public function downloadCurl(string $uri, string $dst): TaskInterface
    {
        return $this
            ->taskDownloadCurl([
                'uri' => $uri,
                'destination' => $dst,
            ]);
    }
}
