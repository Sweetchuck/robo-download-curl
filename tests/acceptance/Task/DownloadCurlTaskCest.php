<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\DownloadCurl\Tests\Acceptance\Task;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use org\bovigo\vfs\vfsStream;
use Sweetchuck\Robo\DownloadCurl\Tests\AcceptanceTester;
use Sweetchuck\Robo\DownloadCurl\Tests\Helper\RoboFiles\RoboFileAcceptance;

class DownloadCurlTaskCest
{

    /**
     * @return array<string, mixed>
     */
    public static function downloadCurlCases(): array
    {
        $dstPrefix = 'vfs://root/downloadCurl';
        $uriSuccess = 'https://file-examples.com/wp-content/storage/2017/02/file_example_JSON_1kb.json';
        $uri404 = 'https://ecb0548d9fc84c9f96905a4b352d13f3.com/not-exists.json';

        return [
            'download:curl success' => [
                'id' => 'download:curl success',
                'expectedExitCode' => 0,
                'expectedStdOutput' => '',
                'expectedStdError' => implode(PHP_EOL, [
                    " [cURL download] Downloading \"$uriSuccess\" to \"$dstPrefix/a/foo.json\"",
                    ' [notice] Pre-download checksum validation is skipped. The destination is not exists.',
                    ' [notice] Post-download checksum validation is skipped. The expected checksum is missing.',
                    '',
                ]),
                'cli' => [
                    'download:curl',
                    $uriSuccess,
                    'a/foo.json'
                ],
            ],
            'download:curl fail' => [
                'id' => 'download:curl fail',
                'expectedExitCode' => 1,
                'expectedStdOutput' => '',
                'expectedStdError' => implode(PHP_EOL, [
                    " [cURL download] Downloading \"$uri404\" to \"$dstPrefix/a/foo.json\"",
                    ' [notice] Pre-download checksum validation is skipped. The destination is not exists.',
                    " [Sweetchuck\Robo\DownloadCurl\Task\DownloadTask]  Could not download '$uri404' ",
                    " [Sweetchuck\Robo\DownloadCurl\Task\DownloadTask]  Exit code 1 ",
                    '',
                ]),
                'cli' => [
                    'download:curl',
                    $uri404,
                    'a/foo.json'
                ],
            ],
        ];
    }

    /**
     * @param \Codeception\Example<string, mixed> $example
     */
    #[DataProvider('downloadCurlCases')]
    public function downloadCurl(AcceptanceTester $tester, Example $example): void
    {
        $vfs = vfsStream::setup(
            'root',
            0777,
            [
                __FUNCTION__ => [],
            ],
        );

        $dst = $vfs->url() . '/' . __FUNCTION__ . '/' . $example['cli'][2];
        $cli = $example['cli'];
        $cli[2] = $dst;

        $tester->assertFileDoesNotExist($dst);

        $tester->wantToTest($example['id']);
        $tester->runRoboTask(
            $example['id'],
            RoboFileAcceptance::class,
            ...$cli,
        );

        $exitCode = $tester->getRoboTaskExitCode($example['id']);
        $stdOutput = $tester->getRoboTaskStdOutput($example['id']);
        $stdError = $tester->getRoboTaskStdError($example['id']);

        $tester->assertSame($example['expectedStdError'], $stdError, 'stdError');
        $tester->assertSame($example['expectedStdOutput'], $stdOutput, 'stdOutput');
        $tester->assertSame($example['expectedExitCode'], $exitCode, 'exitCode');

        if ($example['expectedExitCode'] === 0) {
            $tester->assertDirectoryExists(dirname($dst), 'MY dir exists');
            $tester->assertFileExists($dst, 'MY file exists');
        }
    }
}
