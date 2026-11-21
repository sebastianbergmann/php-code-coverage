<?php declare(strict_types=1);

function supportsSsl(?array $version): bool
{
    return \defined('CURL_VERSION_SSL')
        && $version !== null
        && 0 !== (\CURL_VERSION_SSL & $version['features']);
}

function canFork(): bool
{
    return \function_exists('pcntl_fork')
        && \function_exists('pcntl_waitpid')
        && \function_exists('posix_kill');
}
