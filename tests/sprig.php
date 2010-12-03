<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Sprig Tests.
 *
 * @package    Sprig
 * @author     Marcus Cobden
 * @license    http://kohanaphp.com/license.html
 * @group modules.sprig
 */
class UnitTest_Sprig extends PHPUnit_Framework_TestCase {

	public function setup()
	{
		if ( ! Kohana::config('database')->default['profiling'])
			$this->fail('Must have profiling on to use these tests!');
	}

	/**
	 * Assert some tables with data into the database before we start testing
	 * 
	 */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		$queries = array(
			'DROP TABLE IF EXISTS `test_users`;',
			'DROP TABLE IF EXISTS `test_names`;',
			'DROP TABLE IF EXISTS `test_tags`;',
			'DROP TABLE IF EXISTS `test_tags_test_users` ;',
			
			'CREATE TABLE `test_users` (
				`id` INT PRIMARY KEY AUTO_INCREMENT,
				`title` VARCHAR(20),
				`year` INT,
				`joined` INT,
				`last_online` INT,
				`last_breathed` TIMESTAMP
			)',
			'CREATE TABLE `test_names` (
				`test_user_id` INT PRIMARY KEY AUTO_INCREMENT,
				`name` VARCHAR(20)
			)',
			'CREATE TABLE `test_tags` (
				`id` INT PRIMARY KEY AUTO_INCREMENT,
				`name` VARCHAR(20)
			)',
			'CREATE TABLE `test_tags_test_users` (
				`test_user_id` INT,
				`test_tag_id` INT
			)',
			
			"INSERT INTO `test_users` VALUES
				(1, 'Mr' , 1991, 1, 10, FROM_UNIXTIME(10)),
				(2, 'Mrs', 1992, 3, 12, FROM_UNIXTIME(12)),
				(3, 'Dr' , 1993, 5, 15, FROM_UNIXTIME(15)),
				(4, 'Ms' , 1994, 5, 15, FROM_UNIXTIME(20))",
			"INSERT INTO `test_names` VALUES (1, 'one'), (2, 'two'), (3, 'three')",
			"INSERT INTO `test_tags`  VALUES (1, 'abc'), (2, 'def'), (3, 'ghi'), (9, '01234')",
			'INSERT INTO `test_tags_test_users` VALUES (1,1), (2,2), (3,3), (1,2), (1,3), (2,1), (2,3)',
		);
		
		$db = Database::instance();
		$db->connect();
		
		foreach ($queries as $query) {
			$result = mysql_query($query);
			if ($result === FALSE)
				throw new Exception(mysql_error());
		}
	}

	/**
	 * Test __get and __set
	 *
	 */
	public function testGetAndSet()
	{
		$q_before = $this->getQueries();
		
		$user = Sprig::factory('Test_User');
		$user->title = 'foo';
		$this->assertEquals('foo', $user->title);
		$user->title = 'bar';
		$this->assertEquals('bar', $user->title);
		
		$this->assertQueryCountIncrease(0, $q_before, $this->getQueries());
	}
	
	/**
	 * Test __get and __set to ensure you can set a field back to it's default value
	 * @ticket 52
	 */
	public function testSetDefault()
	{
		$q_before = $this->getQueries();
		
		$user = Sprig::factory('Test_User');
		$user->title = 'foo';
		$this->assertEquals('foo', $user->title);
		// Test setting to the default value
		$default = $user->field('title')->default;
		$user->title = $default;
		$this->assertEquals($default, $user->title);
		
		$this->assertQueryCountIncrease(0, $q_before, $this->getQueries());
	}

	/**
	 * Test that __set properly clears old cache entries when a BelongsTo
	 * relation is replaced with a fixed foreign key ID.
	 * This is an attempt to address a possibly shortcoming of the original fix
	 * for issue 52.
	 * @ticket 52
	 */
	public function testClearRelatedCache()
	{
		$q_before = $this->getQueries();

		$name = Sprig::factory('Test_Name');

		// First, assign a full object
		$user1 = Sprig::factory('Test_User', array('id' => 1));
		$name->test_user = $user1;
		$this->assertEquals(1, $name->test_user->id);

		// Now, replace with a fixed ID value
		$name->test_user = 2;
		$this->assertEquals(2, $name->test_user->id);

		$this->assertQueryCountIncrease(0, $q_before, $this->getQueries());
	}

	/**
	 * Test the getting the default value of a field.
	 * @ticket 53
	 */
	public function testGetDefault()
	{
		$q_before = $this->getQueries();
		
		$user = Sprig::factory('Test_User');
		$this->assertEquals('Sir', $user->title);
		
		$this->assertQueryCountIncrease(0, $q_before, $this->getQueries());
	}

	/**
	 * Test the getting the default value of a field after modifying another field.
	 * @ticket 53
	 */
	public function testGetDefaultAfterOtherSet()
	{
		$q_before = $this->getQueries();
		
		$user = Sprig::factory('Test_User');
		$user->year = 2009;
		$this->assertEquals('Sir', $user->title);
		
		$user = Sprig::factory('Test_User');
		$user->id = 1;
		$this->assertEquals('Sir', $user->title);
		$this->assertEquals(null, $user->year);
		
		$this->assertQueryCountIncrease(0, $q_before, $this->getQueries());
	}

	/**
	 * Test __get and __set
	 *
	 */
	public function testSetBlank()
	{
		$user = Sprig::factory('Test_User');
		$user->title = 'foo';
		$this->assertEquals('foo', $user->title);
		// Test blank setting
		$user->title = '';
		$this->assertEquals('', $user->title);
	}

	/**
	 * Test loading of a single object from the database
	 *
	 */
	public function testLoadSingle()
	{
		$q_before = $this->getQueries();

		$user = Sprig::factory('Test_User');
		$user = $user->load(DB::select());
		$this->assertEquals(1, $user->id);

		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());

		$user = Sprig::factory('Test_User', array('id' => 1));
		$user = $user->load();
		$this->assertEquals(1, $user->id);

		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
	}

	/**
	 * Tests loading an empty PK returns an unloaded model
	 *
	 * @return null
	 */
	public function testLoadEmptyPK()
	{
		$user = Sprig::factory('Test_User');
		$this->assertFalse($user->loaded());
		$user->load();
		$this->assertFalse($user->loaded());
	}

	/**
	 * Test the loading of multiple objects but with a limit to the number of results
	 *
	 */
	public function testLoadLimit()
	{
		$q_before = $this->getQueries();
		$users = Sprig::factory('Test_User');
		$users = $users->load(NULL, 2);
		
		$this->assertEquals(2, count($users));
		$this->assertEquals('Mr' ,$users[0]->title);
		$this->assertEquals('Mrs',$users[1]->title);
		
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
	}
	
	/**
	 * Test the loading of all objects from a single table.
	 * 
	 */
	public function testLoadAll()
	{
		$q_before = $this->getQueries();
		$users = Sprig::factory('Test_User');
		$users = $users->load(NULL, FALSE);
		
		$this->assertEquals(4, count($users));
		$this->assertEquals('Mr' , $users[0]->title);
		$this->assertEquals('Mrs', $users[1]->title);
		$this->assertEquals('Dr' , $users[2]->title);
		
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
	}

	/**
	 * Test lazy loading via a HasOne relationship
	 *
	 */
	public function testHasOne()
	{
		$q_before = $this->getQueries();
		$user = Sprig::factory('Test_User');
		$user->id = 1;
		$user = $user->load();
		
		$this->assertEquals('Mr', $user->title);
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
		
		$user->name->load();
		$this->assertEquals('one', $user->name->name);
		$this->assertEquals(1, $user->name->test_user->id);
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
	
	}
	
	/**
	 * Test lazy loading via a BelongsTo relationship
	 * 
	 */
	public function testBelongsTo()
	{
		$q_before = $this->getQueries();
		$name = Sprig::factory('Test_Name');
		$name->test_user = 1;
		$name = $name->load();
		
		$this->assertEquals('one', $name->name);
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
		
		$name->test_user->load();
		$this->assertEquals(1, $name->test_user->id);
		$this->assertEquals('Mr', $name->test_user->title);
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
	}
	
	/**
	 * Test a many to many relationship
	 * 
	 */
	public function testManyMany()
	{
		$q_before = $this->getQueries();
		$user = Sprig::factory('Test_User');
		$user->id = 1;
		// $user->load(); Not needed!

		$this->assertEquals(3, count($user->tags));
		$this->assertEquals(1, $user->tags[0]->id);
		$this->assertEquals(2, $user->tags[1]->id);
		$this->assertEquals(3, $user->tags[2]->id);
		$this->assertEquals('abc', $user->tags[0]->name);
		$this->assertEquals('def', $user->tags[1]->name);
		$this->assertEquals('ghi', $user->tags[2]->name);
		unset($user);
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());

		$tag = Sprig::factory('Test_Tag');
		$tag->id = 1;
		// $tag->load(); Not needed!

		$this->assertEquals(2, count($tag->users));
		$this->assertEquals(1, $tag->users[0]->id);
		$this->assertEquals(2, $tag->users[1]->id);
		$this->assertEquals('Mr' , $tag->users[0]->title);
		$this->assertEquals('Mrs', $tag->users[1]->title);
		
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
	}
	
	/**
	 * Load a number of items via a many-many link, then test retrieving their related items.
	 * 
	 */
	public function testRelatedViaManyMany()
	{
		$q_before = $this->getQueries();

		$tag = Sprig::factory('Test_Tag');
		$tag->id = 1;
		// $tag->load(); Not needed!

		$this->assertEquals(2, count($tag->users));

		// we expect 2 results, but don't know which order they'll be in
		$user0 = $tag->users[0]->id == 1 ? $tag->users[0] : $tag->users[1];
		$user1 = $tag->users[1]->id == 2 ? $tag->users[1] : $tag->users[0];

		$this->assertEquals(1, $user0->id);
		$this->assertEquals(2, $user1->id);
		$this->assertEquals('Mr' , $user0->title);
		$this->assertEquals('Mrs', $user1->title);
		
		// intersperse the checks so we can be sure where the queries happen.
		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
		$this->assertTrue((bool)$user0->name->changed());
		$user0->name->load();
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
		$this->assertEquals('one', $user0->name->name);
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
		$this->assertEquals('one', $user0->name->name);
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
		$this->assertTrue((bool)$user1->name->changed());
		$user1->name->load();
		$this->assertQueryCountIncrease(3, $q_before, $this->getQueries());
		$this->assertEquals('two', $user1->name->name);
		$this->assertQueryCountIncrease(3, $q_before, $this->getQueries());
		
		$user1->name->name = 'foo';
		$this->assertEquals('foo', $user1->name->name);
		$this->assertEquals(2, $user1->name->test_user->id);
		$this->assertQueryCountIncrease(3, $q_before, $this->getQueries());
		
		$user0->name->test_user = 12;
		$this->assertEquals(12, $user0->name->test_user->id);
		$this->assertEquals('one', $user0->name->name);
		$this->assertQueryCountIncrease(3, $q_before, $this->getQueries());
	}

	/**
	 * Ensure that loading a sprig by way of a char field retains its string
	 * type
	 *
	 * @return null
	 *
	 * @group ticket61
	 */
	public function testCharViaChar()
	{
		$tag = Sprig::Factory('Test_Tag', array('name' => '01234'))->load();
		$this->assertTrue($tag->loaded());
		$this->assertSame('01234', $tag->name);
	}

	/**
	 * Ensure that loading a sprig by way of a char field with an int value
	 * retains its string type
	 *
	 * @return null
	 *
	 * @group ticket61
	 */
	public function testCharViaInt()
	{
		$tag = Sprig::Factory('Test_Tag', array('name' => 1234))->load();
		$this->assertTrue($tag->loaded());
		$this->assertSame('01234', $tag->name);
	}

	/**
	 * Ensure that when a Sprig is destroyed, that any memory leak is within
	 * acceptable levels.
	 *
	 * Note: PHP is succeptable to memory leaks when two objects have
	 * references to each other.
	 *
	 * @return null
	 *
	 * @link http://github.com/shadowhand/sprig/issues/#issue/74
	 */
	public function testMemoryLeakage()
	{
		if ( ! function_exists('memory_get_usage') )
		{
			$this->markTestSkipped(
				'Your ancient PHP version doesn\'t support memory_get_usage()'
			);
		}

		$before = $usage = 0;
		$before = $usage = memory_get_usage();

		// Load one Sprig to cheat
		$this->exerciseUserRetrieval();
		$usage = memory_get_usage();
		$this->assertLessThan(500000, $usage-$before, 'used '.$usage.' memory');

		// Load another to test for leakage
		$before = $usage;
		$this->exerciseUserRetrieval();
		$usage = memory_get_usage();
		$this->assertLessThan(15000, $usage-$before, 'used '.$usage.' memory');
	}

	/**
	 * Retrieve and walk through one full User object.  Used by
	 * testMemoryLeakage() to test reclaiming used memory space.
	 *
	 * @return null
	 */
	public function exerciseUserRetrieval()
	{
		$user = Sprig::factory('Test_User', array('id' => 1))->load();
		$this->assertEquals(3, count($user->tags));
		$this->assertEquals(3, count($user->tags->as_array()));
		$user->name->load();
		$this->assertTrue($user->name->loaded());
	}

	/**
	 * Get the currently logged set of queries from the database profiling.
	 *
	 * @param string $database The database the queries will be logged under.
	 * @return array Map of queries from the Profiler class
	 * @author Marcus Cobden
	 */
	public function getQueries($database = 'default')
	{
		$database = "database ($database)";
		
		$groups = Profiler::groups();
		if (! array_key_exists($database, $groups))
			return array();

		return $groups[$database];
	}
	
	/**
	 * Find the difference between two different query profiles
	 *
	 * @param array $before The queries before
	 * @param array $after  The queries after
	 * @return array(int, array) Total number of new queries and a map of query => increase.
	 * @author Marcus Cobden
	 */
	public function queryDiff(array $before, array $after)
	{
		$added = 0;
		$diff = array();

		foreach ($after as $query => $ids) {
			if (! array_key_exists($query, $before))
			{
				$cmp = count($ids);
				$added += $cmp;
				$diff[$query] = $cmp;
			}
			else
			{
				$cmp = count($ids) - count($before[$query]);
				if ($cmp == 0)
					continue;
					
				$added += $cmp;
				$diff[$query] = $cmp;
			}
		}
			
		return array($added, $diff);
	}
	
	/**
	 * Assert that the number of queries should have increased by a certain amount.
	 *
	 * @param int   $increase Expected increase in number of queries
	 * @param array $before   Queries before the tests
	 * @param array $after    Queries after the tests
	 * @return void
	 * @author Marcus Cobden
	 */
	public function assertQueryCountIncrease($increase, array $before, array $after)
	{
		list($added, $new_queries) = $this->queryDiff($before, $after);
		
		$this->assertEquals($increase, $added, "Expected to have $increase more queries, actual increase was $added.");
	}
	
	public function testCreateAndDelete()
	{
		$q_before = $this->getQueries();

		$user = Sprig::factory('Test_User');
		$user->title = 'Bacon';
		$user->year  = 1999;
		$user->last_online = 99;
		$user->last_breathed = 101;
		$user->create();

		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());

		$user2 = Sprig::factory('Test_User', array('id'=>$user->id))->load();
		$this->assertEquals($user->title, $user2->title);
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
		
		$user2->delete();
		$this->assertQueryCountIncrease(3, $q_before, $this->getQueries());
		
		$user3 = Sprig::factory('Test_User', array('id'=>$user->id))->load();
		$this->assertEquals(FALSE, $user3->loaded());
		$this->assertQueryCountIncrease(4, $q_before, $this->getQueries());
	}
	
	
	public function testCreateUpdateDelete()
	{
		$q_before = $this->getQueries();

		$user = Sprig::factory('Test_User');
		$user->title = 'Bacon';
		$user->year  = 1999;
		$user->last_online = 99;
		$user->last_breathed = 101;
		$user->create();

		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());

		$user2 = Sprig::factory('Test_User', array('id'=>$user->id))->load();
		$this->assertEquals($user->title, $user2->title);
		$this->assertQueryCountIncrease(2, $q_before, $this->getQueries());
		
		$user2->title = 'Madam';
		$this->assertEquals($user->last_online,$user2->last_online);
		$this->assertEquals($user->last_breathed,$user2->last_breathed);
		$user2->update();
		$this->assertGreaterThan($user->last_online, $user2->last_online);
		$this->assertGreaterThan($user->last_breathed, $user2->last_breathed);
		$this->assertQueryCountIncrease(3, $q_before, $this->getQueries());
		
		$user3 = Sprig::factory('Test_User', array('id'=>$user->id))->load();

		$this->assertEquals($user2->title, $user3->title);
		$this->assertQueryCountIncrease(4, $q_before, $this->getQueries());
		
		$user3->delete();
		$this->assertQueryCountIncrease(5, $q_before, $this->getQueries());
		
		$user4 = Sprig::factory('Test_User', array('id'=>$user->id))->load();
		$this->assertEquals(FALSE, $user4->loaded());
		$this->assertQueryCountIncrease(6, $q_before, $this->getQueries());
	}

	/**
	 * Tests new object relationships
	 *
	 * @return null
	 */
	public function test_new_relate()
	{
		$user = Sprig::factory('Test_User', array('id' => 4))->load();
		$user->relate('tags', 1)->update();
		$this->assertEquals(1, count($user->tags));

		$user->unrelate('tags', 1)->update();
	}

	/**
	 * Tests creating and removing relationships
	 *
	 * @return null
	 */
	public function testAddRemoveRelationships()
	{
		$user = Sprig::factory('Test_User', array('id' => 4))->load();

		// We should have no tags
		$this->assertEquals(0, count($user->tags));

		// Add with an integer
		$user->relate('tags', 1)->update();

		// We should have 1 tags
		$this->assertEquals(1, count($user->tags));

		// Add with an object
		$tag = Sprig::factory('Test_Tag', array('id' => 2))->load();
		$user->relate('tags', $tag)->update();

		// We should have 2 tags
		$this->assertEquals(2, count($user->tags));

		// Add with an array
		$user->relate('tags', array(3))->update();

		// We should have 3 tags
		$this->assertEquals(3, count($user->tags));

		// Remove with an integer
		$user->unrelate('tags', 1)->update();

		// We should have 2 tags
		$this->assertEquals(2, count($user->tags));

		// Remove with an object
		$tag = Sprig::factory('Test_Tag', array('id' => 2))->load();
		$user->unrelate('tags', $tag)->update();

		// We should have 1 tags
		$this->assertEquals(1, count($user->tags));

		// Remove with an array
		$user->unrelate('tags', array(3));

		// We should have 0 tags
		$this->assertEquals(0, count($user->tags));
	}

} // End Sprig