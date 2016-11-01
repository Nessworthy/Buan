<?php
/*
# $Id$
*/

/*
# @class SqlDumpIterator
# A wrapper for iterating through a list of SQL statements contained within a
# standard SQL dump file.
*/
class SqlDumpIterator implements Iterator, ArrayAccess, Countable {

	private $queries = array();

	public function __construct($dump) {

		// Parse dump into an array of separate queries
		// It is assumed that all statements are on new lines and each
		// terminated with a semi-colon.
		if(preg_match_all("/(.*?);[\r\n]+/ims", $dump."\n", $m)) {
			$this->queries = $m[1];
		}
	}

	public function current() {
		return current($this->queries);
	}

	public function key() {
		return key($this->queries);
	}

	public function next() {
		return next($this->queries);
	}

	public function rewind() {
		return reset($this->queries);
	}

	public function valid() {
		return current($this->queries);
	}

	public function offsetExists($o) {
		return isset($this->queries[$o]);
	}

	public function offsetGet($o) {
		return $this->queries[$o];
	}

	public function offsetSet($o, $v) {
		return $this->queries[$o] = $v;
	}

	public function offsetUnset($o) {
		unset($this->queries[$o]);
	}

	public function count() {
		return count($this->queries);
	}
}
?>