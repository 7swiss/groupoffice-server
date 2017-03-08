<?php
namespace GO\Core\Pdf\Model;

/**
 * Render a table inside a PDF
 * 
 * @example
 * `````````````````````````````````````````````````````````````````````````````

 * $pdf = new Pdf();
 * 
 * $table = new Table($pdf);
 * $table->setCol('name', .5);
 * $table->setCol('description', .5);
 * 
 *  // Print line for headers
 * 	$pdf->hr();
		
 *  // Style for headers
		$pdf->setColor('text', 110, 110, 110);
		$pdf->setColor('draw', 110, 110, 110);
		$pdf->size(8);
 * 
 *  $headers = ['name' => 'Name', 'description' => 'Description'];
 * 		
		$table->addRow($headers);
		
		$pdf->hr();
 * 
 *  // reset style to defaults
 *  $pdf->setColor('text', 0, 0, 0);
		$pdf->size();
 * 
 *  // Add data
 *  $table->addRow(['name' => 'Foo', 'description' => 'Bar']);
 * 
 * 
 * `````````````````````````````````````````````````````````````````````````````
 */
class Table {
	
	/**
	 * With in the PDF units
	 * 
	 * @var float 
	 */
	public $width;
	
	private $cols;
	
	/**
	 *
	 * @var Pdf
	 */
	private $pdf;
	
	/**
	 * {@see \TCPDF::getMargins()}
	 * 
	 * @var array 
	 */
	private $margins;
	
	/**
	 * Constructor
	 * 
	 * @param Pdf $pdf
	 */
	public function __construct(Pdf $pdf) {
		
		$this->pdf = $pdf;
		
		$this->margins = $pdf->getMargins();
		
		$this->width = $pdf->getPageWidth() - $this->margins['left'] - $this->margins['right'];			
		
		$this->pdf->setCellPaddings(0, 2, 0, 2);
	}
	
	/**
	 * Add a table column
	 * 
	 * @param string $id
	 * @param float $width percentage. 1 = 100% .5 = 50%
	 * @param string $align 'L', 'C', 'J', or 'R'
	 * @param int $border
	 */
	public function setCol($id, $width, $align='L', $border=0) {
		$this->cols[$id] = ['width' => $width, 'align' => $align, 'border'=>$border];
	}
	
	
	/**
	 * Remove table column
	 * 
	 * @param string $id
	 */
	public function removeCol($id) {
		unset($this->cols[$id]);
	}
	
	/**
	 * Add table row
	 * 
	 * @param array $data
	 */
	public function addRow($data) {
		
		$startPage = $maxPage = $this->pdf->getPage();
		$startY = $this->pdf->getY();
		$curX = $this->margins['left'];
		
		$maxY = [];
		
		foreach($this->cols as $colName => $colData) {
			
			$this->pdf->setPage($startPage);
      $this->pdf->SetXY($curX,$startY);
						
			$w = $colData['width'] * $this->width;
			
			$curX += $w;
			
			$this->pdf->MultiCell($w, $this->pdf->lh, $data[$colName], $colData['border'], $colData['align']);

			$newPage = $this->pdf->getPage();
			if(!isset($maxY[$newPage]))
					$maxY[$newPage] = 0;
			
			if($maxY[$newPage] < $this->pdf->GetY()) {
					$maxY[$newPage] = $this->pdf->GetY();
			}
						
			if($newPage > $maxPage) {
				$maxPage = $newPage;
			}
		}
		
		$this->pdf->setPage($maxPage);
    $this->pdf->SetXY($this->margins['left'], $maxY[$maxPage]);
	}
}
