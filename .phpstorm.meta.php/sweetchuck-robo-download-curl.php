<?php

/**
 * @file
 * PhpStorm meta.
 */

namespace PHPSTORM_META {

    registerArgumentsSet(
        'robo-download-curl.skipDownloadIf',
        'exists',
        'checksumMatches',
        'never',
    );

    expectedArguments(
        \Sweetchuck\Robo\DownloadCurl\Task\DownloadTask::setSkipDownloadIf(),
        0,
        argumentsSet('robo-download-curl.skipDownloadIf'),
    );

    expectedReturnValues(
        \Sweetchuck\Robo\DownloadCurl\Task\DownloadTask::getSkipDownloadIf(),
        argumentsSet('robo-download-curl.skipDownloadIf'),
    );
}
