<?php

declare(strict_types = 1);

namespace Sweetchuck\Robo\DownloadCurl\Task;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Robo\Contract\BuilderAwareInterface;
use Robo\Result;
use Robo\Task\BaseTask;
use Robo\TaskAccessor;
use Robo\TaskInfo;
use Sweetchuck\Robo\Hash\HashTaskLoader;

class DownloadTask extends BaseTask implements BuilderAwareInterface
{
    use TaskAccessor;
    use HashTaskLoader;

    protected string $taskName = 'cURL download';

    //region uri
    protected string $uri = '';

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setUri(string $value): static
    {
        $this->uri = $value;

        return $this;
    }
    //endregion

    //region destination
    /**
     * @var null|resource|string
     */
    protected $destination = null;

    /**
     * @return null|resource|string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * @param null|resource|string $value
     */
    public function setDestination($value): static
    {
        $this->destination = $value;

        return $this;
    }
    //endregion

    // region hashChecksum
    protected string $hashChecksum = '';

    public function getHashChecksum(): string
    {
        return $this->hashChecksum;
    }

    public function setHashChecksum(string $hashChecksum): static
    {
        $this->hashChecksum = $hashChecksum;

        return $this;
    }
    // endregion

    // region hashOptions
    /**
     * @var array
     * @phpstan-var roboDownloadCurlHashOptions
     */
    protected array $hashOptions = [];

    /**
     * @phpstan-return roboDownloadCurlHashOptions
     */
    public function getHashOptions(): array
    {
        return $this->hashOptions;
    }

    /**
     * @param array $hashOptions
     * @phpstan-param roboDownloadCurlHashOptions $hashOptions
     */
    public function setHashOptions(array $hashOptions): static
    {
        $this->hashOptions = $hashOptions;

        return $this;
    }
    // endregion

    // region curlOptions
    /**
     * @var array<int, mixed>
     */
    protected array $curlOptions = [];

    /**
     * @return array<int, mixed>
     */
    public function getDefaultCurlOptions(): array
    {
        return [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 100,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_SSL_VERIFYPEER => 1,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    /**
     * @param array<int, mixed> $curlOptions
     */
    public function setCurlOptions(array $curlOptions): static
    {
        $this->curlOptions = $curlOptions;

        return $this;
    }

    /**
     * @param array<int, mixed> $curlOptions
     */
    public function addCurlOptions(array $curlOptions): static
    {
        foreach ($curlOptions as $key => $value) {
            $this->curlOptions[$key] = $value;
        }

        return $this;
    }
    // endregion

    // region skipDownloadIf
    /**
     * @phpstan-var roboDownloadCurlSkipDownloadIfEnum
     */
    protected string $skipDownloadIf = 'checksumMatches';

    /**
     * @phpstan-return roboDownloadCurlSkipDownloadIfEnum
     */
    public function getSkipDownloadIf(): string
    {
        return $this->skipDownloadIf;
    }

    /**
     * @param string $skipDownloadIf
     *   Allowed values: exists, checksumMatches, never.
     * @phpstan-param roboDownloadCurlSkipDownloadIfEnum $skipDownloadIf
     */
    public function setSkipDownloadIf(string $skipDownloadIf): static
    {
        $this->skipDownloadIf = $skipDownloadIf;

        return $this;
    }

    public function skipDownloadIfExists(): static
    {
        $this->skipDownloadIf = 'exists';

        return $this;
    }

    public function skipDownloadIfChecksumMatches(): static
    {
        $this->skipDownloadIf = 'checksumMatches';

        return $this;
    }

    public function skipDownloadIfNever(): static
    {
        $this->skipDownloadIf = 'never';

        return $this;
    }
    // endregion

    /**
     * @param array<string, mixed> $options
     * @phpstan-param taskDownloadCurlOptions $options
     */
    public function setOptions(array $options): static
    {
        if (array_key_exists('uri', $options)) {
            $this->setUri($options['uri']);
        }

        if (array_key_exists('destination', $options)) {
            $this->setDestination($options['destination']);
        }

        if (array_key_exists('hashOptions', $options)) {
            if (isset($options['hashOptions']['checksum'])
                && !isset($options['hashChecksum'])
            ) {
                $options['hashChecksum'] = $options['hashOptions']['checksum'];
                unset($options['hashOptions']['checksum']);
            }

            $this->setHashOptions($options['hashOptions']);
        }

        if (array_key_exists('hashChecksum', $options)) {
            $this->setHashChecksum($options['hashChecksum']);
        }

        if (array_key_exists('curlOptions', $options)) {
            $this->setCurlOptions($options['curlOptions']);
        }

        if (array_key_exists('skipDownloadIf', $options)) {
            $this->setSkipDownloadIf($options['skipDownloadIf']);
        }

        return $this;
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger ?: new NullLogger();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Robo\Result<string, mixed>
     */
    public function run()
    {
        return $this
            ->runHeader()
            ->runPrepareDstDir()
            ->runDownload();
    }

    protected function runHeader(): static
    {
        $this->printTaskInfo('');

        return $this;
    }

    protected function runPrepareDstDir(): static
    {
        $dst = $this->getDestination();
        if (is_resource($dst) || $dst === null || $dst === '') {
            return $this;
        }

        $dstDir = dirname($dst);
        if ($dstDir && !file_exists($dstDir)) {
            $result = mkdir($dstDir, 0777 - umask(), true);
            if (!$result) {
                $this->getLogger()->error(
                    'directory <info>{dir}</info> could not be created',
                    [
                        'dir' => $dstDir,
                    ],
                );
            }
        }

        return $this;
    }

    /**
     * @return \Robo\Result<string, mixed>
     */
    protected function runDownload(): Result
    {
        $dst = $this->getDestination();
        if ($dst === null || $dst === '') {
            return Result::error($this, 'destination is required');
        }

        $expectedChecksum = $this->getHashChecksum();
        if ($this->preDownloadCheck()) {
            $fileName = (string) $dst;
            $hashOptions = $this->getHashOptions();

            return Result::success(
                $this,
                sprintf(
                    'destination %s is already exists and matches to %s:%s',
                    $fileName,
                    $hashOptions['hashAlgorithm'] ?? '',
                    $expectedChecksum,
                ),
            );
        }

        $isDstResource = is_resource($dst);
        $isDstExists = !$isDstResource && file_exists($dst);
        $dstHandler = $isDstResource ? $dst : fopen((string) $dst, 'w+');
        if (!$isDstResource && !$dstHandler) {
            return Result::error($this, "Could not open target file '$dst'");
        }

        $uri = $this->getUri();

        $curlOptions = $this->getCurlOptions() + $this->getDefaultCurlOptions();
        $curlOptions[CURLOPT_URL] = $uri;
        $curlOptions[CURLOPT_FILE] = $dstHandler;

        $curlHandler = curl_init();
        foreach ($curlOptions as $key => $value) {
            curl_setopt($curlHandler, $key, $value);
        }
        $result = curl_exec($curlHandler);
        if ($dstHandler) {
            fclose($dstHandler);
        }

        // @todo Support for \CURLOPT_RETURNTRANSFER.
        $details = curl_getinfo($curlHandler);
        curl_close($curlHandler);

        if (!$details || !$this->isSuccess($result, $details)) {
            if (!$isDstResource && !$isDstExists) {
                unlink($this->destination);
            }

            return Result::error($this, "Could not download '$uri'");
        }

        $dstFileName = (string) $dst;
        if (!$this->postDownloadCheck()) {
            return Result::error(
                $this,
                sprintf(
                    'URI %s downloaded to %s, but broken. Expected: %s;',
                    $uri,
                    $dstFileName,
                    $expectedChecksum,
                ),
            );
        }

        return Result::success($this, "URI $uri downloaded to $dstFileName");
    }

    /**
     * @phpstan-param array{
     *     scheme?: string,
     *     http_code?: int,
     * } $details
     */
    protected function isSuccess(string|bool $result, array $details): bool
    {
        if ($result === false) {
            return false;
        }

        $scheme = $details['scheme'] ?? '';
        if ($scheme === 'HTTP' || $scheme === 'HTTPS') {
            return ($details['http_code'] ?? 0) === 200;
        }

        return true;
    }

    public function getTaskName(): string
    {
        return $this->taskName ?: TaskInfo::formatTaskName($this);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $text
     * @param null|array<string, mixed> $context
     *
     * @return void
     */
    protected function printTaskInfo($text, $context = null)
    {
        parent::printTaskInfo(
            $text ?: $this->getTaskInfoPattern(),
            $context ?: $this->getTaskInfoContext(),
        );
    }

    protected function getTaskInfoPattern(): string
    {
        return 'Downloading <info>"{uri}"</info> to <info>"{dst}"</info>';
    }

    /**
     * @return null|array<string, mixed>
     */
    protected function getTaskInfoContext(): ?array
    {
        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param null|array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    protected function getTaskContext($context = null)
    {
        $context = parent::getTaskContext($context);
        $context['name'] = $this->getTaskName();
        $context['uri'] = $this->getUri();
        $context['dst'] = $this->getDestination();

        return $context;
    }

    /**
     * @return bool
     *   FALSE means it has to be downloaded.
     */
    protected function preDownloadCheck(): bool
    {
        $skipDownloadIf = $this->getSkipDownloadIf();
        if ($skipDownloadIf === 'never') {
            return false;
        }

        $logger = $this->getLogger();

        $dst = $this->getDestination();
        if (is_resource($dst)) {
            // @todo Actually it is possible.
            $logger->notice('Pre-download checksum validation is skipped. The given destination is a resource.');

            return false;
        }

        $isDstExists = file_exists($dst);
        if ($skipDownloadIf === 'exists') {
            return $isDstExists;
        }

        if (!$isDstExists) {
            $logger->notice('Pre-download checksum validation is skipped. The destination is not exists.');

            return false;
        }

        $expectedChecksum = $this->getHashChecksum();
        $hashOptions = $this->getHashOptions();
        if ($expectedChecksum === '' || !isset($hashOptions['hashAlgorithm'])) {
            $logger->notice('Pre-download checksum validation is skipped. The expected checksum is missing.');

            return false;
        }

        $hashOptions['fileName'] = $dst;
        $result = $this->taskHash($hashOptions)->run();
        if (!$result->wasSuccessful()) {
            $logger->warning('Pre-download checksum calculation failed.');

            return false;
        }

        if ($expectedChecksum !== $result['hash']) {
            $logger->warning('Pre-download checksum mismatch.');

            return false;
        }

        $logger->notice('Pre-download checksum validation success.');

        return true;
    }

    protected function postDownloadCheck(): bool
    {
        $logger = $this->getLogger();
        $dst = $this->getDestination();
        if (is_resource($dst)) {
            // @todo Actually it is possible.
            $logger->notice('Post-download checksum validation is skipped. The given destination is a resource.');

            return true;
        }

        $expectedChecksum = $this->getHashChecksum();
        $hashOptions = $this->getHashOptions();
        if ($expectedChecksum === '' || !isset($hashOptions['hashAlgorithm'])) {
            $logger->notice('Post-download checksum validation is skipped. The expected checksum is missing.');

            return true;
        }

        $hashOptions['fileName'] = $dst;
        $result = $this
            ->taskHash($hashOptions)
            ->run();

        if (!$result->wasSuccessful()) {
            // @todo Maybe false.
            return true;
        }

        $isOk = $expectedChecksum === ($result['hash'] ?? null);
        if ($isOk) {
            $logger->notice('Post-download checksum validation success.');
        }

        return $isOk;
    }
}
