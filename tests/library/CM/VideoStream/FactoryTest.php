<?php

class CM_VideoStream_FactoryTest extends CMTest_TestCase {

    public function testCreateService() {
        $adapterClass = $this->mockClass('CM_VideoStream_Adapter_Abstract');
        $adapterConstructor = $adapterClass->mockMethod('__construct')->set(function ($argument) {
            $this->assertSame('foo', $argument);
        });
        $adapterClassName = $adapterClass->getClassName();

        $factory = new CM_VideoStream_Factory();
        $service = $factory->createService($adapterClassName, ['foo']);
        $this->assertInstanceOf('CM_VideoStream_Service', $service);
        $this->assertSame(1, $adapterConstructor->getCallCount());
    }
}