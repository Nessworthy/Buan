<?php
/*
# $Id$
*/

class TestSuite {

	private $scriptFolders = array();

	private $testStack = array();

	private $results = array();

	public function addScriptsFolder($folder) {
		$this->scriptFolders[] = $folder;
	}

	public function startTest($title=NULL) {
		$test = new UnitTest($this);
		$test->title = $title===NULL ? md5(uniqid(rand())) : $title;
		$this->testStack[$test->getId()] = $test;
		return $test;
	}

	public function endTest($test) {
		$testResults = $test->getResults();
		$resultsString = '';
		foreach($testResults as $r) {
			if($r->result===TRUE) {
				$resultsString .= ".";
			}
			else {
				$resultsString .= "X({$r->line}) ";
				$this->results[$test->getId()] = FALSE;
			}
		}
		echo '['.($this->hasFailures() ? 'X' : ' ')."] {$test->title}: ".$resultsString;
		echo "\n";
		unset($this->testStack[$test->getId()]);
		ob_flush();
	}

	public function start() {
		foreach($this->scriptFolders as $folder) {
			$scripts = new DirectoryIterator($folder);
			foreach($scripts as $script) {
				if($script->isDot() || !$script->isFile()) {
					continue;
				}

				echo "Running '{$script->getFileName()}' ...\n--(start)----------------------------------------------\n";
				include_once($script->getPathName());
				echo "\n\n";
			}
		}
	}

	public function hasFailures() {
		return in_array(FALSE, $this->results, TRUE);
	}
}
?>