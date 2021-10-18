<?php
namespace SebastianBergmann\PHPCOV;

use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

final class XdebugFileDriver extends Driver
{
    public function __construct($fileName, $mapFrom = "", $mapTo = "")
    {
        $this->data = array();

        foreach(json_decode(file_get_contents($fileName), true) as $key => $value) {
            $this->data[str_replace($mapFrom, $mapTo, $key)] = $value;
        }
    }

    public function canCollectBranchAndPathCoverage(): bool
    {
        return true;
    }

    public function canDetectDeadCode(): bool
    {
        return true;
    }

    public function start(): void
    {
    }

    public function stop(): RawCodeCoverageData
    {
        if ($this->collectsBranchAndPathCoverage()) {
            return RawCodeCoverageData::fromXdebugWithPathCoverage($this->data);
        }

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($this->data);
    }

    public function nameAndVersion(): string
    {
        return 'Xdebug file driver';
    }
}
