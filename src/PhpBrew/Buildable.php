<?php

namespace PhpBrew;

interface Buildable
{
    /**
     * @return path return source directory
     */
    public function getSourceDirectory();

    /**
     * @return bool
     */
    public function isBuildable();

    /**
     * @return string return build log file path.
     */
    public function getBuildLogPath();
}
