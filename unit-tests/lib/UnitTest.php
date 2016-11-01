<?php
class UnitTest {
	private $id;

	private $testSuite;

	private $results = array();

	public $title;

	public function __construct($testSuite) {
		$this->id = spl_object_hash($this);
		$this->testSuite = $testSuite;
	}

	public function getId() {
		return $this->id;
	}

	public function end() {
		return $this->testSuite->endTest($this);
	}

	public function addResult($result) {
		$bt = debug_backtrace();
		$r = new \StdClass();
		$r->result = $result;
		$r->line = $bt[0]['line'];
		$this->results[] = $r;
	}

	public function getResults() {
		return $this->results;
	}
}
?>