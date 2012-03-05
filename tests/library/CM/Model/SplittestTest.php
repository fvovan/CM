<?php
require_once __DIR__ . '/../../../TestCase.php';

class CM_Model_SplittestTest extends TestCase {

	public function testCreate() {
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		$this->assertInstanceOf('CM_Model_Splittest', $test);

		try {
			$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
			$this->fail('Could create duplicate splittest');
		} catch (CM_Exception $e) {
			$this->assertTrue(true);
		}

		$test->delete();
	}

	public function testConstruct() {
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		$test2 = new CM_Model_Splittest('foo');
		$this->assertModelEquals($test, $test2);

		$test->delete();
	}

	public function testGetId() {
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		$this->assertGreaterThanOrEqual(1, $test->getId());

		$test->delete();
	}

	public function testGetCreated() {
		$time = time();
		/** @var CM_Model_Splittest $test */
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		$this->assertGreaterThanOrEqual($time, $test->getCreated());

		$test->delete();
	}

	public function testGetVariations() {
		/** @var CM_Model_Splittest $test */
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		$this->assertInstanceOf('CM_Paging_SplittestVariation_Splittest', $test->getVariations());

		$test->delete();
	}

	public function testGetVariationFixture() {
		$user1 = TH::createUser();
		/** @var CM_Model_Splittest $test */
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));

		for ($i = 0; $i < 2; $i++) {
			$variationUser1 = $test->getVariationFixture($user1);
			$this->assertContains($variationUser1, array('v1', 'v2'));
			$this->assertSame($variationUser1, $test->getVariationFixture($user1));
		}

		$test->delete();
	}

	public function testGetVariationFixtureDisabledVariation() {
		/** @var CM_Model_Splittest $test */
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		/** @var CM_Model_SplittestVariation $variation1 */
		$variation1 = $test->getVariations()->getItem(0);
		/** @var CM_Model_SplittestVariation $variation2 */
		$variation2 = $test->getVariations()->getItem(1);

		$variation1->setEnabled(false);
		for ($i = 0; $i < 10; $i++) {
			$user = TH::createUser();
			$this->assertSame($variation2->getName(), $test->getVariationFixture($user));
		}

		$test->delete();
	}

	public function testGetVariationFixtureCount() {
		$user1 = TH::createUser();
		$user2 = TH::createUser();
		/** @var CM_Model_Splittest $test */
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1')));
		/** @var CM_Model_SplittestVariation $variation */
		$variation = $test->getVariations()->getItem(0);

		$this->assertSame(0, $test->getVariationFixtureCount($variation));

		$test->getVariationFixture($user1);
		$this->assertSame(1, $test->getVariationFixtureCount($variation));
		$test->getVariationFixture($user1);
		$this->assertSame(1, $test->getVariationFixtureCount($variation));
		$test->getVariationFixture($user2);
		$this->assertSame(2, $test->getVariationFixtureCount($variation));

		$test->delete();
	}

	public function testGetConversionCount() {
		$user1 = TH::createUser();
		/** @var CM_Model_Splittest $test */
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1')));
		/** @var CM_Model_SplittestVariation $variation */
		$variation = $test->getVariations()->getItem(0);

		$test->getVariationFixture($user1);
		$this->assertSame(0, $test->getConversionCount($variation));

		$test->setConversion($user1);
		$this->assertSame(1, $test->getConversionCount($variation));

		$test->delete();
	}

	public function testDelete() {
		$test = CM_Model_Splittest::create(array('name' => 'foo', 'variations' => array('v1', 'v2')));
		$test->delete();
		try {
			new CM_Model_Splittest($test->getId());
			$this->fail('Splittest not deleted.');
		} catch (CM_Exception_Nonexistent $e) {
			$this->assertTrue(true);
		}
	}

}
