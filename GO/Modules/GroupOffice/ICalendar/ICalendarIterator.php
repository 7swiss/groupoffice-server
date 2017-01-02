<?php

namespace GO\Modules\GroupOffice\ICalendar;

use Exception;
use Iterator;

class ICalendarIterator implements Iterator {

	private $_type;
	private $_fp;
	private $_current = false;
	private $_key = 0;
	private $_header = false;

	public function __construct($fp, $type = "VEVENT") {
		$this->_fp = $fp;
		$this->_type = $type;
	}

	public function rewind() {

		$this->_current = false;
		$this->_key = -1;
		$this->_header = false;

		$this->next();
	}

	public function current() {
		return $this->_current;
	}

	public function key() {
		return $this->_key;
	}

	public function next() {
		$buffer = "";
		$found = false;
		$count = 0;

		$buildHeader = empty($this->_header);

		while ($line = fgets($this->_fp)) {
			$count++;

			if ($buildHeader && trim($line) != "BEGIN:" . $this->_type) {
				$this->_header .= $line;
			} else {

				$buildHeader = false;

				$buffer .= $line;
				if (trim($line) == "END:" . $this->_type) {
					$found = true;
					break;
				}
			}

			if ($count == 50000) {
				//var_dump($buffer);
				throw new Exception("Reached 50000 lines for one event. Aborting!");
			}
		}
		
		$this->_current = $found && !empty($buffer) ? $this->_header . $buffer . "END:VCALENDAR" : false;
	
	}

	public function valid() {
		$ret = $this->_current != false;
		return $ret;
	}

}
