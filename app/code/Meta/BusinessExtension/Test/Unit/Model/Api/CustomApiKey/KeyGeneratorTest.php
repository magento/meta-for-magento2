<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model\Api\CustomApiKey;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Meta\BusinessExtension\Model\Api\CustomApiKey\KeyGenerator;

class KeyGeneratorTest extends TestCase
{
    /**
     * Class setUp function
     * 
     * @return void
     */
    protected function setUp(): void
    {
        $object = new ObjectManager($this);
        $this->keyGenerator = $object->getObject(KeyGenerator::class);
    }

    public function testGenerate()
    {
        $this->assertIsString($this->keyGenerator->generate());
    }
}