<?php
namespace IFW;

class Locale {
	
	private $decimalPoint = ',';
	private $thousandsSeparator = '.';
	
	public function __construct() {
		
	}
	
	public function formatNumber($number, $decimals=2) {
		return number_format($number, $decimals, $this->decimalPoint, $this->thousandsSeparator);
	}
	
	
}
