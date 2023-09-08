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
    public static function iterateFiles(string $baseDir, string $pattern): \Generator
    {
        $it = new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($it);
        // $it = new \RegexIterator($it, $pa, \RegexIterator::MATCH);

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
