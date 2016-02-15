<?php

class CM_Paging_LogTest extends CMTest_TestCase {

    public function tearDown() {
        CMTest_TH::clearEnv();
    }

    public function testConstructorValidation() {
        $exception = $this->catchException(function () {
            new CM_Paging_Log(1);
        });

        $this->assertInstanceOf('CM_Exception_Invalid', $exception);
        $this->assertSame('Log level `1` does not exist.', $exception->getMessage());
    }

    public function testAddGet() {
        $handler = new CM_Log_Handler_MongoDb(CM_Paging_Log::COLLECTION_NAME);
        $user = CMTest_TH::createUser();
        $record1 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'foo', new CM_Log_Context($user, null, null, ['bar' => 'quux']));
        $record2 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'baz', new CM_Log_Context());

        $handler->handleRecord($record1);
        $handler->handleRecord($record2);

        $paging = new CM_Paging_Log(CM_Log_Logger::DEBUG);

        $items = $paging->getItems();
        $this->assertSame(2, count($items));

        $this->assertSame('baz', $items[0]['message']);
        $this->assertSame(CM_Log_Logger::DEBUG, $items[0]['level']);

        $this->assertSame('foo', $items[1]['message']);
        $this->assertSame(CM_Log_Logger::DEBUG, $items[1]['level']);
        $this->assertSame($user->getDisplayName(), $items[1]['context']['user']['name']);
        $this->assertSame(['bar' => 'quux'], $items[1]['context']['extra']);

        $age = 86400;
        CMTest_TH::timeForward($age);
        CMTest_TH::timeForward($age);
        $record3 = new CM_Log_Record(CM_Log_Logger::CRITICAL, 'bar', new CM_Log_Context());
        $handler->handleRecord($record3);

        $paging2 = new CM_Paging_Log(CM_Log_Logger::CRITICAL, false, $age + 1);
        $items = $paging2->getItems();
        $this->assertSame(1, count($items));

        $this->assertSame('bar', $items[0]['message']);
        $this->assertSame(CM_Log_Logger::CRITICAL, $items[0]['level']);
    }

    public function testCleanUp() {
        $handler = new CM_Log_Handler_MongoDb(CM_Paging_Log::COLLECTION_NAME);
        $record1 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'foo', new CM_Log_Context(null, null, null, ['bar' => 'quux']));
        $record2 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'baz', new CM_Log_Context());
        $record3 = new CM_Log_Record(CM_Log_Logger::CRITICAL, 'bar', new CM_Log_Context());

        $paging = new CM_Paging_Log(CM_Log_Logger::DEBUG);

        $this->assertSame(0, $paging->getCount());

        $handler->handleRecord($record1);
        $handler->handleRecord($record2);
        $handler->handleRecord($record3);
        $paging->_change();

        $this->assertSame(2, $paging->getCount());

        $age = 7 * 86400 + 1;
        CMTest_TH::timeForward($age);
        $paging->cleanUp();
        $this->assertSame(0, $paging->getCount());
    }

    public function testFlush() {
        $handler = new CM_Log_Handler_MongoDb(CM_Paging_Log::COLLECTION_NAME);
        $record1 = new CM_Log_Record(CM_Log_Logger::INFO, 'foo', new CM_Log_Context(null, null, null, ['bar' => 'quux']));
        $record2 = new CM_Log_Record(CM_Log_Logger::INFO, 'baz', new CM_Log_Context());

        $paging = new CM_Paging_Log(CM_Log_Logger::INFO);

        $this->assertSame(0, $paging->getCount());

        $handler->handleRecord($record1);
        $handler->handleRecord($record2);
        $paging->_change();
        $this->assertSame(2, $paging->getCount());

        $paging->flush();
        $this->assertSame(0, $paging->getCount());
    }

    public function testAggregate() {
        $handler = new CM_Log_Handler_MongoDb(CM_Paging_Log::COLLECTION_NAME);
        $exception = new CM_Exception_Invalid('Bad news', CM_Exception::WARN, ['baz' => 'bar']);
        $record1 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'foo', new CM_Log_Context(null, null, null, ['bar' => 'quux']));
        $record2 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'baz', new CM_Log_Context());
        $record3 = new CM_Log_Record_Exception($exception, new CM_Log_Context());

        //they will not be found
        $handler->handleRecord($record1);
        $handler->handleRecord($record2);
        $handler->handleRecord($record3);

        CMTest_TH::timeDaysForward(2);

        //recreate records to correctly set up CM_Log_Record::createdAt
        $record1 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'foo', new CM_Log_Context(null, null, null, ['bar' => 'quux']));
        $record3 = new CM_Log_Record_Exception($exception, new CM_Log_Context());

        $handler->handleRecord($record1);
        $handler->handleRecord($record3);
        $handler->handleRecord($record3);

        CMTest_TH::timeDaysForward(1);
        //recreate records to correctly set up CM_Log_Record::createdAt
        $record1 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'foo', new CM_Log_Context(null, null, null, ['bar' => 'quux']));
        $record2 = new CM_Log_Record(CM_Log_Logger::DEBUG, 'baz', new CM_Log_Context());
        $record3 = new CM_Log_Record_Exception($exception, new CM_Log_Context(), CM_Log_Logger::DEBUG);

        $handler->handleRecord($record2);
        $handler->handleRecord($record2);
        $handler->handleRecord($record3);
        $handler->handleRecord($record1);
        $handler->handleRecord($record1);

        $paging = new CM_Paging_Log(CM_Log_Logger::DEBUG, true, 2 * 86400);
        $this->assertSame(3, $paging->getCount());
        $foundRecord1 = $paging->getItem(0);
        $foundRecord2 = $paging->getItem(1);
        $foundRecord3 = $paging->getItem(2);
        $this->assertSame(3, $foundRecord1['count']);
        $this->assertSame(2, $foundRecord2['count']);
        $this->assertSame(1, $foundRecord3['count']);

        $this->assertSame('foo', $foundRecord1['message']);
        $this->assertSame('baz', $foundRecord2['message']);
        $this->assertSame('CM_Exception_Invalid: ' . $exception->getMessage(), $foundRecord3['message']);
    }
}