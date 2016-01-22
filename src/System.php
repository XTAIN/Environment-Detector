<?php

namespace XTAIN\EnvironmentDetector;

/**
 * Class System
 * @package XTAIN\EnvironmentDetector
 */
class System
{
    /**
     * @const string
     */
    const PLATFORM_WINDOWS = 'WIN';

    /**
     * @const string
     */
    const PLATFORM_CYGWIN = 'CYGWIN';

    /**
     * @const string
     */
    const PLATFORM_POSIX = 'POSIX';

    /**
     * @var Project
     */
    protected $project;

    /**
     * System constructor.
     * @param Project $project
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->getTempDir();
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        $os = strtolower(PHP_OS);

        if (strpos($os, 'win') === 0) {
            return self::PLATFORM_WINDOWS;
        } else if (strpos($os, 'cygwin') === 0) {
            return self::PLATFORM_CYGWIN;
        }

        return self::PLATFORM_POSIX;
    }

    /**
     * @return string
     */
    public function getTempDir()
    {
        /**
         * @return null|string
         */
        $findTempInUserProfile = function() {
            $dirs = ['Local Settings' . DIRECTORY_SEPARATOR . 'Temp'];

            foreach ($dirs as $dir) {
                $dir = $_SERVER['USERPROFILE'] . DIRECTORY_SEPARATOR . $dir;
                if (isset($dir) && is_writable($dir)) {
                    return $dir;
                }
            }

            return null;
        };

        switch ($this->getPlatform()) {
            case self::PLATFORM_WINDOWS:
                if (isset($_SERVER['TMP']) && is_writable($_SERVER['TMP'])) {
                    return $_SERVER['TMP'];
                }

                if (isset($_SERVER['TEMP']) && is_writable($_SERVER['TEMP'])) {
                    return $_SERVER['TEMP'];
                }

                $tmpInProfile = $findTempInUserProfile();
                if ($tmpInProfile !== null) {
                    return $tmpInProfile;
                }
                break;
            case self::PLATFORM_CYGWIN:
                $tmpInProfile = $findTempInUserProfile();
                if ($tmpInProfile !== null) {
                    return $tmpInProfile;
                }
                break;
            case self::PLATFORM_POSIX:

                if (is_dir('/dev/shm') && is_writable('/dev/shm')) {
                    return '/dev/shm';
                }
                break;
        }

        $var = $this->project->getProjectDir() . DIRECTORY_SEPARATOR . 'var';
        if (is_dir($var) && is_writable($var)) {
            return $var . DIRECTORY_SEPARATOR . 'cache';
        }

        return sys_get_temp_dir();
    }

    /**
     * @param string $path
     * @return string
     */
    public function normalizePath($path)
    {
        $ds = DIRECTORY_SEPARATOR;

        if ($this->getPlatform() == self::PLATFORM_CYGWIN) {
            $path = preg_replace_callback('/^([a-z])\:(\\\\|\/)/i', function($match) {
                return '/cygdrive/' . strtolower($match[1]) . '/';
            }, $path);
        }

        return preg_replace('/(\\\\|\/)+/', DIRECTORY_SEPARATOR, $path . $ds);
    }

    /**
     * @return string
     */
    public function getApplicationTempDir()
    {
        $namespace = $this->project->getProjectNamespace();
        $dir = $this->getTempDir();

        if (strpos(
                $this->normalizePath($dir),
                $this->normalizePath($this->project->getProjectDir())
            ) !== 0) {
            $dir = $this->normalizePath($dir . DIRECTORY_SEPARATOR . $namespace);
        }

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new \RuntimeException('Could not create application temp dir');
            }
        }

        return $this->normalizePath($dir . DIRECTORY_SEPARATOR);
    }
}