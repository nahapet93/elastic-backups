<?php

namespace App\Services;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;

final class ElasticDump
{
    /**
     * @var string
     */
    private const ELASTICDUMP_CMD = '%s --input=%s --output=%s --quiet --overwrite';

    private string $exePath;

    public function __construct(ExecutableFinder $executableFinder)
    {
        $exePath = $executableFinder->find('elasticdump', null, [
            base_path().'/node_modules/.bin',
            base_path().'/node_modules/elasticdump/bin',
            '/usr/local/bin',
            '/opt/homebrew/bin',
            '%AppData%/Roaming/npm/node_modules/elasticdump/bin',
        ]);

        if ($exePath === null) {
            throw new RuntimeException('Can not find elasticdump on this system.');
        }

        $this->exePath = $exePath;
    }

    public function process(string $input, string $output): ProcessResult
    {
        return Process::run(sprintf(self::ELASTICDUMP_CMD, $this->exePath, $input, $output))->throw();
    }
}
