<?php

/**
 * Class helper_plugin_deletehistory
 */
class helper_plugin_deletehistory extends DokuWiki_Plugin
{

    /**
     * @var array
     */
    protected $dirs = [];

    public function __construct()
    {
        global $conf;
        $this->dirs = [
            'pages' => DOKU_INC . $conf['savedir'] . '/attic',
            'media' => DOKU_INC . $conf['savedir'] . '/media_attic',
        ];
    }

    /**
     * Delete old revisions and logged changes
     */
    public function deleteAllHistory()
    {
        foreach ($this->dirs as $dir => $attic) {
            $this->clearAttic($attic);
            $this->deleteChanges($dir);
        }
        $this->cleanupChangelogs();
    }

    /**
     * Delete everything in the given directory
     *
     * @param string $dir
     */
    protected function clearAttic($dir)
    {
        if (!is_readable($dir) || !is_dir($dir)) {
            return;
        }

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::CHILD_FIRST);

        // delete all files
        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if (!$file->isDir()) {
                unlink($file->getPathname());
            }
        }

        // delete all the emptied directories
        $rii->rewind();
        foreach ($rii as $file) {
            if ($file->isDir() && !in_array($file->getFilename(), ['.', '..'])) {
                rmdir($file->getPathname());
            }
        }
    }

    /**
     * Recursively find all .changes files in a directory and truncate them
     * leaving only the first line (create event).
     *
     * @param string $dir "pages" or "media"
     */
    protected function deleteChanges($dir)
    {
        global $conf;
        $metaDir = ($dir === 'media') ? 'media_meta' : 'meta';
        $path = DOKU_INC . $conf['savedir'] . '/' . $metaDir;
        if (!file_exists($path) || !is_dir($path)) return;

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if (!$file->isDir() && $file->getExtension() === 'changes' && $file->getFilename()[0] !== '_') {
                $currentLog = @file($file->getPathname());
                if (!$currentLog) {
                    continue;
                }
                $updatedLog = substr_replace($currentLog[0], filemtime($file->getPathname()), 0, 10);
                io_saveFile($file->getPathname(), $updatedLog);
            }
        }
    }

    /**
     * Delete from global changelogs all changes except create
     */
    protected function cleanupChangelogs()
    {
        global $conf;

        // filter lines on "C"
        $pattern = "/^\d{10}\t[0-9\.]*\t[^C]\t/";
        io_replaceInFile($conf['changelog'], $pattern, '', true);
        io_replaceInFile($conf['media_changelog'], $pattern, '', true);
    }
}
