<?php

/**
 *
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

namespace App\Umc\CoreBundle\Tests\Unit\Service;

use App\Umc\CoreBundle\Service\ZipArchiveFactory;
use PHPUnit\Framework\TestCase;

class ZipArchiveFactoryTest extends TestCase
{
    /**
     * @covers \App\Umc\CoreBundle\Service\ZipArchiveFactory::create()
     */
    public function testCreate()
    {
        $factory = new ZipArchiveFactory();
        $this->assertInstanceOf(\ZipArchive::class, $factory->create());
    }
}
