<?php
namespace GO\Core\Templates\Model;

use Exception;
use GO\Core\Pdf\Model\Pdf as PdfModel;
use IFW\Template\VariableParser;

/**
 * Renders a PDF from template
 * 
 * @example
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * $template = Pdf::findByPk(1);
 * 
 * $models = ['foo' => $record];
 * 
 * $pdf = new PdfRenderer($template, $models);
 * 
 * GO()->getResponse()->setHeader('Content-Type', 'application/pdf');
		GO()->getResponse()->setHeader('Content-Disposition', 'inline; filename="' . $template->name . '.pdf"');
		GO()->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
		
 * echo $pdf->render();
 * 
 * `````````````````````````````````````````````````````````````````````````````
 */
class PdfRenderer extends PdfModel {
	
	/**
	 *
	 * @var Pdf 
	 */
	protected $template;
	
	/**
	 *
	 * @var \IFW\Template\VariableParser;
	 */
	protected $variableParser;
	
	public $previewMode = false;
	
	/**
	 * Constructor
	 * 
	 * @param \GO\Core\Templates\Model\Pdf $template
	 * @param array $templateModels Key value array that will be used to parse templates. eg. ['invoice' => $invoice] {@see VariableParser::addModel()}
	 */
	public function __construct(Pdf $template, $templateModels = []) {	
	
		$this->template = $template;		
		
		$orientation = $this->template->landscape ? 'L' : 'P';		
		
		
		$this->variableParser = new VariableParser();
		foreach($templateModels as $name => $model) {
			$this->variableParser->addModel($name, $model);
		}
		
		
		parent::__construct($orientation, $this->template->measureUnit, $this->template->pageSize);
		
		$this->SetTopMargin($this->template->marginTop);
		$this->SetLeftMargin($this->template->marginLeft);
		$this->SetRightMargin($this->template->marginRight);
		$this->SetAutoPageBreak(true, $this->template->marginBottom);	
		
		
		// Set the source PDF file		
		if(isset($template->stationaryPdfBlob)) {
			$numberOfPages = $this->setSourceFile($template->stationaryPdfBlob->getPath());

			// Import the first page of the template PDF
			for($i = 1; $i <= $numberOfPages; $i++) {
				$this->tplIdx[$i] = $this->importPage($i);			
			}
		}
	}	
	
	/**
	 * Set in constructor when the PDF has a stationary PDF
	 * 
	 * @var int[] 
	 */
	private $tplIdx;
	
	public function Header() {
		
		//use stationary PDF
		if(count($this->tplIdx)) {
			
			//use every page of the template. If the invoice has more pages use the last page.
			$tplIdx = isset($this->tplIdx[$this->page]) ? $this->tplIdx[$this->page] : $this->tplIdx[count($this->tplIdx)];			
			$this->useTemplate($tplIdx);
		}
	}
	
	public function render() {
		
		$this->AddPage();
		
		$currentX = $this->getX();
		$currentY = $this->getY();
			
			
		foreach($this->template->blocks as $block) {
			
			$this->normal();		
		
			if(isset($block->x)) {
				$this->setX($block->x);
			}
			
			if(isset($block->y)) {
				$this->setY($block->y);
			}
			
			if(!isset($block->width)) {
				$block->width = $this->w-$this->lMargin - $this->rMargin;
			}
			
			$method = 'renderBlock'.$block->type;
			
			if(!method_exists($this, $method)) {
				throw new Exception("Invalid block tag ".$block->type);
			}
			
			$this->setCellPaddings($block->marginLeft, $block->marginTop, $block->marginRight, $block->marginBottom);
			$this->normal();
		
			$this->$method($block);
			
			if(isset($block->x)) {
				$this->setX($currentX);
			}else
			{
				$currentX = $this->getX();			
			}
			
			if(isset($block->y)) {
				$this->setY($currentY);
			}else
			{
				$currentY = $this->getY();
			}
		}
		return parent::render();
	}
	
	
	private function renderBlockText(PdfBlock $block) {
				
		if(!isset($block->height)) {
			$block->height = $this->lh;		
		}
		
		$data = $this->previewMode ? $block->data : $this->variableParser->parse($block->data);
		
		$this->MultiCell(
						$block->width, 
						$block->height, 
						$data, 
						0, //border 
						$block->align, 
						false, //fill
						1,  //Line break
						isset($block->x) ? $block->x : '', 
						isset($block->y) ? $block->y : ''
						);
		
		$this->setLastH($this->lh);
		
	}
	private function renderBlockHtml(PdfBlock $block) {
		
		if(isset($block->height)) {
			$y = $block->height + $this->getY();
		}
		
		$data = $this->previewMode ? $block->data : $this->variableParser->parse($block->data);
		
		$this->writeHTMLCell(	
						$block->width, 
						$block->height, 
						isset($block->x) ? $block->x : '', 
						isset($block->y) ? $block->y : '',
						$data,
						0,//border
						1 //ln
						);		
		
		if(isset($y)) {
			$this->setY($y);
		}
	}
}
