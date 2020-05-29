<?php

/**
 * UMC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MIT License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/mit-license.php
 *
 * @copyright Marius Strajeru
 * @license   http://opensource.org/licenses/mit-license.php MIT License
 * @author    Marius Strajeru <ultimate.module.creator@gmail.com>
 *
 */

declare(strict_types=1);

namespace App\Umc\CoreBundle\Service;

use App\Umc\CoreBundle\Config\Loader\VersionAwareFactory;
use App\Umc\CoreBundle\Model\Platform\Version;
use App\Umc\CoreBundle\Repository\Module;
use App\Umc\CoreBundle\Service\Cs\Executor;
use App\Umc\CoreBundle\Service\Generator\Pool;
use Symfony\Component\Filesystem\Filesystem;

class Builder
{
    /**
     * @var \App\Umc\CoreBundle\Model\Module\Factory\Locator
     */
    private $factoryLocator;
    /**
     * @var Pool\Locator
     */
    private $generatorPoolLocator;
    /**
     * @var Module
     */
    private $repository;
    /**
     * @var VersionAwareFactory
     */
    private $configLoaderFactory;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var Archiver
     */
    private $archiver;
    /**
     * @var Executor
     */
    private $csExecutor;

    /**
     * Builder constructor.
     * @param \App\Umc\CoreBundle\Model\Module\Factory\Locator $factoryLocator
     * @param Pool\Locator $generatorPoolLocator
     * @param Module $repository
     * @param VersionAwareFactory $configLoaderFactory
     * @param Filesystem $filesystem
     * @param Archiver $archiver
     * @param Executor $executor
     */
    public function __construct(
        \App\Umc\CoreBundle\Model\Module\Factory\Locator $factoryLocator,
        Pool\Locator $generatorPoolLocator,
        Module $repository,
        VersionAwareFactory $configLoaderFactory,
        Filesystem $filesystem,
        Archiver $archiver,
        Executor $executor
    ) {
        $this->factoryLocator = $factoryLocator;
        $this->generatorPoolLocator = $generatorPoolLocator;
        $this->repository = $repository;
        $this->configLoaderFactory = $configLoaderFactory;
        $this->filesystem = $filesystem;
        $this->archiver = $archiver;
        $this->csExecutor = $executor;
    }

    /**
     * @param Version $version
     * @param array $data
     * @return \App\Umc\CoreBundle\Model\Module
     * @throws \Exception
     */
    public function build(Version $version, array $data)
    {
        $factory = $this->factoryLocator->getFactory($version);
        $module = $factory->create($data);
        $loader = $this->configLoaderFactory->create($version);
        $generatorPool = $this->generatorPoolLocator->getGeneratorPool($version);
        $files = [];
        foreach ($loader->getConfig() as $value) {
             $newFiles = $generatorPool->getGenerator($value['scope'])->generateContent($module, $value);
             $files = array_merge($files, $newFiles);
        }
        $repoRoot = $this->repository->getRoot($version);
        $root = $repoRoot . '/' . $module->getExtensionName();
        $this->writeFiles($files, $root);
        //run coding standards
        $csResult = $this->csExecutor->run($version->getCodingStandards(), $root);
        $this->writeFiles($csResult, $root);
        //archive;
        $this->archiver->createZip($root, $module->getExtensionName(), $repoRoot);
        $this->filesystem->remove($root);
        $this->repository->save($module, $version);
        return $module;
    }

    /**
     * @param $files
     * @param $root
     */
    private function writeFiles($files, $root): void
    {
        foreach ($files as $file => $content) {
            $filePath = $root . '/' . $file;
            $this->filesystem->dumpFile($filePath, $content);
        }
    }
}