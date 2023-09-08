<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden\Sites;

/**
 * @internal
 */
final class FileUtils
{
    /**
     * Iterate through all file in a directory matching a specific pattern.
     * This iterator yields full file paths.
     *
     * @param string $baseDir
     * @param non-empty-string $pattern
     *
     * @return \Generator
     */
    public static function iterateFiles(string $baseDir, string $pattern): \Generator
    {
        $it = new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($it);

        /**
         * @var \SplFileInfo $file
         */
        foreach ($it as $file) {
            $pathname = $file->getPathname();
            if (preg_match($pattern, $pathname)) {
                yield $pathname;
            }
        }
    }
}
