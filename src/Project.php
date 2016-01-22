<?php

namespace XTAIN\EnvironmentDetector;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class Project
{
    /**
     * @const string
     */
    const NS_PREFIX = 'app_';

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var string
     */
    protected $vendorDirectory;

    /**
     * @var object
     */
    protected $metadata;

    /**
     * Environment constructor.
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return string
     */
    public function getProjectDir()
    {
        $filesystem = new Filesystem();

        $lastPath = realpath($this->kernel->getRootDir());
        $parentCount = substr_count($lastPath, DIRECTORY_SEPARATOR);

        for ($i = 0; $i < $parentCount; $i++) {
            $lastPath = dirname($lastPath);
            if ($filesystem->exists($lastPath . DIRECTORY_SEPARATOR . 'composer.json') ||
                $filesystem->exists($lastPath . DIRECTORY_SEPARATOR . 'composer.json')) {
                return $lastPath;
            }
        }

        throw new \RuntimeException('Could not find project root');
    }

    /**
     * @return string
     */
    public function getVendorDir()
    {
        if (!isset($this->vendorDirectory)) {
            $class = new \ReflectionClass(ClassLoader::class);
            $this->vendorDirectory = realpath(
                $class->getFileName() . DIRECTORY_SEPARATOR .
                '..' . DIRECTORY_SEPARATOR .
                '..'
            );
        }

        return $this->vendorDirectory;
    }

    /**
     * @return object
     */
    public function getProjectMetadata()
    {
        if (isset($this->metadata)) {
            return $this->metadata;
        }

        $filesystem = new Filesystem();

        $composerFile = $this->getProjectDir() . DIRECTORY_SEPARATOR . 'composer.json';

        if ($filesystem->exists($composerFile)) {
            $this->metadata = json_decode(file_get_contents($composerFile));

            return $this->metadata;
        }

        throw new \RuntimeException('Could not read project metadata');
    }

    /**
     * @return string
     */
    public function getProjectFullName()
    {
        $metadata = $this->getProjectMetadata();

        if (isset($metadata->name)) {
            return $metadata->name;
        }

        throw new \RuntimeException('Could not find project name');
    }

    /**
     * @return string
     */
    public function getProjectName()
    {
        $fullname = $this->getProjectFullName();

        return substr($fullname, strpos($fullname, '/') + 1);
    }

    /**
     * @return string
     */
    public function getProjectVendor()
    {
        $fullname = $this->getProjectFullName();

        return substr($fullname, 0, strpos($fullname, '/'));
    }

    /**
     * @return string
     */
    public function getProjectHash()
    {
        return dechex(crc32($this->getProjectFullName() . $this->getProjectDir()));
    }

    /**
     * @return string
     */
    public function getProjectNamespace()
    {
        $name = self::NS_PREFIX . $this->getProjectName() . '_' . $this->getProjectHash();
        $nameClean = preg_replace('/[^a-zA-Z0-9]/', '_', $name);

        return strtolower($nameClean);
    }
}