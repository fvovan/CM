<?php

require_once __DIR__ . '/../../../TestCase.php';

class CM_Model_LanguageTest extends TestCase {

	/** @var CM_Model_Language $_language */
	protected $_language;

	public function setUp() {
		$languageId = CM_Mysql::insert(TBL_CM_LANGUAGE, array('name' => 'English', 'abbreviation' => 'EN', 'enabled' => 1));
		$this->_language = new CM_Model_Language($languageId);
	}

	public function tearDown() {
		TH::clearEnv();
	}

	public function testSetGetTranslation() {
		$this->assertSame('keyFirst', $this->_language->getTranslation('keyFirst'));
		$this->assertSame(array('keyFirst' => array('value' => null, 'variables' => array())), $this->_language->getTranslations()->getAssociativeArray());

		$this->_language->getTranslation('keyFirst');	// Fill APC
		$this->_language->setTranslation('keyFirst', 'abc');
		$this->assertSame('keyFirst', $this->_language->getTranslation('keyFirst'));
		$this->assertSame(array('keyFirst' => array('value' => 'abc', 'variables' => array())), $this->_language->getTranslations()->getAssociativeArray());


		$this->assertSame('keyFirst', $this->_language->getTranslation('keyFirst', array('variable')));
		$this->assertSame(array('keyFirst' => array('value' => 'abc', 'variables' => array('variable'))), $this->_language->getTranslations()->getAssociativeArray());

		$this->_language->getTranslation('keyFirst');	// Fill APC
		$this->_language->setTranslation('keyFirst', 'xyz', array('another'));
		$this->assertSame(array('keyFirst' => array('value' => 'xyz', 'variables' => array('another'))), $this->_language->getTranslations()->getAssociativeArray());

		$this->_language->getTranslation('keyFirst');
		$this->assertSame(array('keyFirst' => array('value' => 'xyz', 'variables' => array('another'))), $this->_language->getTranslations()->getAssociativeArray());
	}

	public function testSetGetTranslationWithoutLocalCache() {
		$this->assertSame('keyFirst', $this->_language->getTranslation('keyFirst', null, true));
		$this->assertSame(array('keyFirst' => array('value' => null, 'variables' => array())), $this->_language->getTranslations()->getAssociativeArray());

		$this->_language->getTranslation('keyFirst');	// Fill APC
		$this->_language->setTranslation('keyFirst', 'abc');
		$this->assertSame('abc', $this->_language->getTranslation('keyFirst', null, true));
		$this->assertSame(array('keyFirst' => array('value' => 'abc', 'variables' => array())), $this->_language->getTranslations()->getAssociativeArray());

	}

	public function testCreate() {
		/** @var CM_Model_Language $language */
		$language = CM_Model_Language::create(array('name' => 'Deutsch', 'abbreviation' => 'de', 'enabled' => true));

		$this->assertInstanceOf('CM_Model_Language', $language);
		$this->assertSame('Deutsch', $language->getName());
		$this->assertSame('de', $language->getAbbreviation());
		$this->assertTrue($language->getEnabled());
		$this->assertNull($language->getBackup());
	}

	public function testCreateWithoutName() {
		try {
			CM_Model_Language::create(array('abbreviation' => 'de',));
			$this->fail('Could create language without name');
		} catch (CM_Exception_InvalidParam $e) {
			$this->assertContains('`name`', $e->getMessage());
		}
	}

	public function testCreateWithDuplicateAbbreviation() {
		try {
			CM_Model_Language::create(array('name' => 'Another one', 'abbreviation' => $this->_language->getAbbreviation(), 'enabled' => true));
			$this->fail('Could create language with duplicate abbreviation');
		} catch (CM_Exception $e) {
			$this->assertContains('Duplicate entry', $e->getMessage());
		}
	}

	public function testDelete() {
		/** @var CM_Model_Language $backedUpLanguage */
		$backedUpLanguage = CM_Model_Language::create(array(
			'name' => 'Backed up language',
			'abbreviation' => 'bul',
			'enabled' => true,
			'backup' => $this->_language,
		));
		$this->_language->delete();
		try {
			new CM_Model_Language($this->_language->getId());
			$this->fail('Language has not been deleted');
		} catch (CM_Exception_Nonexistent $e) {
			$this->assertContains('CM_Model_Language', $e->getMessage());
		}
		TH::reinstantiateModel($backedUpLanguage);
		$this->assertNull($backedUpLanguage->getBackup());
	}

	public function testSetData() {
		$this->_language->setData('Polish', 'pl', false);
		$this->assertSame('Polish', $this->_language->getName());
		$this->assertSame('pl', $this->_language->getAbbreviation());
		$this->assertSame(false, $this->_language->getEnabled());
	}

	public function testSetDataDuplicateAbbreviation() {
		CM_Model_Language::create(array(
			'name' => 'Another',
			'abbreviation' => 'pl',
			'enabled' => true
		));
		try {
			$this->_language->setData('Polish', 'pl', false);
			$this->fail('Could set language with duplicate abbreviation');
		} catch (CM_Exception $e) {
			$this->assertContains('Duplicate entry', $e->getMessage());
		}
	}

	public function testFindByAbbreviation() {
		$this->assertModelEquals($this->_language, CM_Model_Language::findByAbbreviation($this->_language->getAbbreviation()));
		$this->assertNull(CM_Model_Language::findByAbbreviation('random-not-existing-abbreviation'));
	}

	public function testDeleteKey() {
		$key = 'languageKey';
		$this->_language->setTranslation($key, 'abc');
		$languageKeyId = CM_Mysql::select(TBL_CM_LANGUAGEKEY, 'id', array('name' => $key))->fetchOne();
		$this->assertSame(array($key => array('value' => 'abc', 'variables' => array())), $this->_language->getTranslations()->getAssociativeArray());

		CM_Model_Language::deleteKey($key);
		$this->assertSame(array(), $this->_language->getTranslations()->getAssociativeArray());
		$this->assertSame(0, CM_Mysql::count(TBL_CM_LANGUAGEKEY, array('name' => $key)));
		$this->assertSame(0, CM_Mysql::count(TBL_CM_LANGUAGEVALUE, array('languageKeyId' => $languageKeyId, 'languageId' => $this->_language->getId())));
	}

	public function testUnsetTranslation() {
		$this->_language->setTranslation('phrase', 'abc');
		$this->assertSame('abc', $this->_language->getTranslation('phrase', null, true));
		$this->_language->unsetTranslation('phrase');
		$this->assertSame('phrase', $this->_language->getTranslation('phrase', null, true));
	}

	public function testGetTranslationWithBackup() {
		/** @var CM_Model_Language $backedUpLanguage */
		$backedUpLanguage = CM_Model_Language::create(array(
			'name' => 'Backed up language',
			'abbreviation' => 'bul',
			'enabled' => true,
			'backup' => $this->_language
		));
		$this->_language->setTranslation('phrase', 'abc');
		$this->assertSame('abc', $backedUpLanguage->getTranslation('phrase'));
	}

	public function testIsBackingUp() {
		/** @var CM_Model_Language $backedUpLanguage */
		$backedUpLanguage = CM_Model_Language::create(array(
			'name' => 'Backed up language',
			'abbreviation' => 'bul',
			'enabled' => true,
			'backup' => $this->_language
		));
		$this->assertTrue($this->_language->isBackingUp($backedUpLanguage));
		$this->assertFalse($backedUpLanguage->isBackingUp($this->_language));
	}

	public function testFindDefault() {
		$this->assertModelEquals($this->_language, CM_Model_Language::findDefault());

		$this->_language->setData($this->_language->getName(), $this->_language->getAbbreviation(), false);
		$this->assertModelEquals($this->_language, CM_Model_Language::findDefault());

		CM_CacheLocal::flush();
		$this->assertNull(CM_Model_Language::findDefault());
	}

	public function testGetTranslationWithDifferentVariableNamesLoop() {
		$this->_language->getTranslation('sameKey', array('oneVariable'), true);
		try {
			for ($i = 0; $i < 5; $i++) {
				$this->_language->getTranslation('sameKey', array('oneVariable', 'secondOne'), true);
				$this->_language->getTranslation('sameKey', array('oneVariable'), true);
			}
			$this->fail('Did not throw exception after ' . ($i * 2) . ' changes');
		} catch (CM_Exception_Invalid $e) {
			$this->assertContains('`sameKey`', $e->getMessage());
		}
	}

	public function testGetTranslationWithDifferentVariableNamesAndKeysLoop() {
		for ($i = 0; $i < 5; $i++) {
			$this->_language->getTranslation('myKey', array('oneVariable'), true);
			$this->_language->getTranslation('MyKey', array('oneVariable', 'secondOne'), true);
			$this->_language->getTranslation('myKéy', array('oneVariable', 'thirdOne'), true);
		}
		$this->assertTrue(true);
	}

	public function testGetTranslationDuplicateVariableNames() {
		try {
			$this->_language->getTranslation('someKey', array('foo', 'bar', 'foo'));
			$this->fail('Should throw exception on duplicate key add');
		} catch (CM_Exception $e) {
			$this->assertContains('Duplicate', $e->getMessage());
		}
	}
}
