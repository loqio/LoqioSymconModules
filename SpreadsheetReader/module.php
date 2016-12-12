<?php

/**
 * Created by PhpStorm.
 * User: femme
 * Date: 07/12/2016
 * Time: 20:07
 *
 * Class SpreadsheetReader
 *
 * @author Femme Taken <femme@loqio.nl>
 * @copyright Copyright (c) 2016 Femme Taken, Loqio Building Controls
 */


class SpreadsheetReader extends IPSModule
{
	/**
	 * Registers module properties on creation of the module
	 */
	public function create()
	{
		parent::create();

		// Create properties
		$this->RegisterPropertyString('spreadsheetUrl', '');
		$this->RegisterPropertyInteger('updateInterval', 30);

		// Create variables
		$this->registerVariableString('output', 'Output', '~HTMLBox', 0);
	}

	/**
	 * Applies changes to the configuration and validates the properties of the configuration model
	 */
	public function applyChanges()
	{
		parent::applyChanges();

		//$this->getModel()->applyChanges();
		//$this->getModel()->validateProperties();
	}

	public function getSpreadsheet()
	{
		require_once('PhpSpreadsheet/src/Autoloader.php');

		$output 		= '';
		$tempFilename 	= tempnam(sys_get_temp_dir(), 'spreadsheet-' . time());
		$url 			= IPS_GetProperty($this->InstanceID, 'spreadsheetUrl');

		file_put_contents($tempFilename, file_get_contents($url));


		//	'https://www.apxgroup.com/wp-content/uploads/marketdata/powernl/public/results_dam_nl/APX_Daily_Market_Results.xls';

		// Store locally

		//$inputFileName = './sampleData/example1.xls';

		/** Load $inputFileName to a Spreadsheet Object  **/
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempFilename);

		/*$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
		$objReader->setReadDataOnly(TRUE);
		$spreadsheet = $objReader->load("test.xlsx");*/

		$objWorksheet = $spreadsheet->getActiveSheet();

		foreach ($objWorksheet->getRowIterator() as $r => $row)
		{
			$cellIterator = $row->getCellIterator();
			//$cellIterator->setIterateOnlyExistingCells(false); // This loops through all cells,
			//    even if a cell value is not set.
			// By default, only cells that have a value
			//    set will be iterated.
			foreach ($cellIterator as $i => $cell)
			{
				$value = $cell->getValue();

				if (!empty($value))
				{
					$output .= $r . '-' . $i . ': ' . $value . "\n";
				}
			}
		}

		// Store output in local value
		if ($variableId = $this->GetIDForIdent('output'))
		{
			SetValue($variableId, $output);
		}

		echo $output;
	}
}