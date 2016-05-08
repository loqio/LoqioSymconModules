<?

class AirQualitySensor extends IPSModule
{

	public function Create()
	{
		// Never delete this line
		parent::Create();


		$this->RegisterPropertyInteger('sensorInstanceId', 0);

		// Create variable profiles
		if (IPS_GetVariableProfile('VolatileOrganicCompounds') == false)
		{
			IPS_CreateVariableProfile('VolatileOrganicCompounds', 1);
			IPS_SetVariableProfileText('VolatileOrganicCompounds', '', 'ppm');
			IPS_SetVariableProfileIcon('VolatileOrganicCompounds', 'ErlenmeyerFlask');
			IPS_SetVariableProfileValues('VolatileOrganicCompounds', 0, 2000, 250);
		}

		if (IPS_GetVariableProfile('AirQuality') == false)
		{
			IPS_CreateVariableProfile('AirQuality', 1);
			IPS_SetVariableProfileIcon('AirQuality', 'Climate');
			IPS_SetVariableProfileAssociation('AirQuality', 1, 'Good', 'Climate', 0x64af3f);
			IPS_SetVariableProfileAssociation('AirQuality', 2, 'Sufficient', 'Climate', 0x93af3f);
			IPS_SetVariableProfileAssociation('AirQuality', 3, 'Bad', 'Warning', 0xaf4c3f);
		}

		// Create status variables
		$this->registerVariableFloat('temperature', 'Temperature', '~Temperature', 0);
		$this->registerVariableFloat('humidity', 'Humidity', '~Humidity.F', 1);
		$this->registerVariableFloat('dewPoint', 'Dew point', '~Temperature', 2);
		$this->registerVariableInteger('voc', 'Volatile organic compounds', 'VolatileOrganicCompounds', 3);
		$this->registerVariableInteger('airQuality', 'Air quality', 'AirQuality', 4);
	}

	public function ApplyChanges()
	{
		// Never delete this line
		parent::ApplyChanges();

		if ($sensorId = $this->ReadPropertyInteger('sensorInstanceId'))
		{
			// Validate if compatible instance id was selected and set update event
			if ($this->Update() == true)
			{
				// Set update event
				if ($this->hasUpdateEvent() == false)
				{
					$this->setUpdateEvent();
				}
			}
		}

		//$this->Compute($this->InstanceID);



		//$this->sensorInstanceId = $this->ReadPropertyString("sensorInstance");

		/*$updateClientsScript = file_get_contents(__DIR__ . "/createClientList.php");
		$scriptID = $this->RegisterScript("updateClients", "updateClients", $updateClientsScript);
		IPS_SetScriptTimer($scriptID, 60);

		$updateWLANScript = file_get_contents(__DIR__ . "/createWLANList.php");
		$scriptID = $this->RegisterScript("updateWLAN", "updateWLAN", $updateWLANScript);
		IPS_SetScriptTimer($scriptID, 60);
		$setWLANScript = file_get_contents(__DIR__ . "/setWLAN.php");
		$this->RegisterScript("setWLAN", "setWLAN", $setWLANScript);


		// Setzt den Intervall des Timers "Update" auf 5 Sekunden
		$this->SetTimerInterval("Update", 3000);*/

	}

	public function Update()
	{
		$success = false;

		$vadVariableId 			= $this->getVadVariableId();
		$vddVariableId 			= $this->getVddVariableId();
		$temperatureVariableId 	= $this->getTemperatureVariableId();
		$xsensVariableId 		= $this->getXsensVariableId();

		if ($vadVariableId && $vddVariableId && $temperatureVariableId && $xsensVariableId)
		{
			$vad 				= GetValueFloat($vadVariableId);
			$vdd 				= GetValueFloat($vddVariableId);
			$temperature 		= GetValueFloat($temperatureVariableId);
			$xsens			 	= GetValueFloat($xsensVariableId);

			$humidity 			= $this->calculateHumidity($vad, $temperature);
			$dewPoint 			= $this->calculateDewPoint($temperature, $humidity);
			$voc				= $this->calculateVolatileOrganicCompounds($xsens);
			$airQualityIndex	= $this->getAirQualityIndex($voc);
			//$airQualityDescription	= $this->getAirQualityDescription($airQualityIndex);


			// Update humidity
			IPS_LogMessage('Air quality sensor', 'instanceId: ' . $this->InstanceID . ' humidity: ' . $humidity . ' dew point: ' . $dewPoint . ' voc: ' . $voc . ' air quality index: ' . $airQualityIndex);


			SetValueFloat(GetIDForIdent('temperature'), round($temperature, 1));
			SetValueFloat(GetIDForIdent('humidity'), round($humidity, 1));
			SetValueFloat(GetIDForIdent('dewPoint'), round($dewPoint, 1));
			SetValueInteger(GetIDForIdent('voc'), round($voc));
			SetValueInteger(GetIDForIdent('airQuality'), $airQualityIndex);


			/*$this->setTemperature($temperature);
			$this->setHumidity($humidity);
			$this->setDewPoint($dewPoint);
			$this->setVolatileOrganicCompounds($voc);
			$this->setAirQualityIndex($airQualityIndex);*/

			$success = true;

			$this->SetStatus(102);
		}
		else
		{
			$this->setStatus(200);
		}



		return $success;
	}

	private function setUpdateEvent()
	{
		$eventId = IPS_CreateEvent(0);
		$variableId = $this->getTemperatureVariableId();

		if ($eventId && $variableId)
		{
			IPS_SetEventTrigger($eventId, 0, $variableId);
			IPS_SetParent($eventId, $this->InstanceID);
			IPS_SetIdent($eventId, 'updateEvent');
			IPS_SetEventActive($eventId, true);
			IPS_SetEventScript($eventId, "AIRQ_Update(" . $this->InstanceID . ");");
			IPS_SetName($eventId, "Update event");
		}
	}

	private function getUpdateEventId()
	{
		$eventId = false;
		$objectId = IPS_GetObjectIDByIdent('updateEvent', $this->InstanceID);
		$object = IPS_GetObject($objectId);

		if ($object['ObjectType'] == 4)
		{
			$eventId = $objectId;
		}

		return $eventId;
	}

	private function hasUpdateEvent()
	{
		return $this->getUpdateEventId() ? true : false;
	}

	/** Returns the object id of variable having name $variableName and parent sensorInstanceId
	 * @param string $variableName: name of variable
	 * @return bool|int: false if variable not found, int if variable found
	 */
	private function getSensorVariableId($variableName)
	{
		$variableId = false;

		if ($sensorId = $this->ReadPropertyInteger('sensorInstanceId'))
		{
			$variableId = IPS_GetObjectIdByName($variableName, $sensorId);
		}

		return $variableId;
	}

	/** Returns the object id of the 'Temperature' variable
	 */
	private function getTemperatureVariableId()
	{
		return $this->getSensorVariableId('Temperature');
	}

	/** Returns the object id of the 'VAD' variable
	 */
	private function getVadVariableId()
	{
		return $this->getSensorVariableId('VAD');
	}

	/** Returns the object id of the 'VDD' variable
	 */
	private function getVddVariableId()
	{
		return $this->getSensorVariableId('VDD');
	}

	/** Returns the object id of the 'XSENS' variable
	 */
	private function getXsensVariableId()
	{
		return $this->getSensorVariableId('XSENS');
	}

	/** Calculates humidity using vad voltage and temperature
	 * @param float $vad: voltage reading from sensor
	 * @param float $temperature: temperature reading from sensor
	 * @return float
	 */
	private function calculateHumidity($vad, $temperature)
	{
		// Air quality sensor constants
		$offset = 0.847847; // Zero Offset V
		$slope = 29.404604; // Slope: mV/%RH
		$correction = 2;

		// Calculate air quality
		$srh = ($vad - $offset) / ($slope / 1000); // Correction factor
		$humidity = ($srh + $correction) / ((1.0305 + (0.000044 * $temperature) - (0.0000011 * pow($temperature, 2))));

		return $humidity;
	}

	/** Calculates dew point using temperature and humidity
	  * @param float $temperature: temperature reading from sensor
	  * @param float $humidity: calculated humidity
	  * @return float
	  */
	private function calculateDewPoint($temperature, $humidity)
	{
		if ($temperature >= 0)
		{
			$a = 7.5;
			$b = 237.3;
		}
		else
		{
			$a = 7.6;
			$b = 240.7;
		}

		// Magnusformel
		$sdd = 6.1078 * pow(10.0, (($a * $temperature) / ($b + $temperature))); // Saturation vapor pressure
		$dd = ($humidity / 100.0) * $sdd; // Vapor pressure
		$v = log10(($dd / 6.1078));
		$td = ($b * $v) / ($a - $v);

		// Dew point
		$dewPoint = ($td * 100 + 0.5) / 100;

		return $dewPoint;
	}

	/** Calculates volatile organic compounds level in parts per million using xsens reading
	 * @param float $xsens: xsens reading from sensor
	 * @return float: $voc
	 */
	private function calculateVolatileOrganicCompounds($xsens)
	{
		$factor = 12600; //Firmware 1.4, CO2 Umgebung = 400-420, je nach Wohnlage (Großstadt = 420, Ländlich = 400)
		$voc = ($xsens * $factor) + 400;

		return $voc;
	}

	/** Returns the air quality index
	 * @param float $voc: volatile organic compouns level
	 * @return int: 1 = good, 2 = sufficient, 3 = bad
	 */
	private function getAirQualityIndex($voc)
	{
		if ($voc <= 850)
		{
			$index = 1;
		}
		elseif ($voc < 1200)
		{
			$index = 2;
		}
		else{
			$index = 3;
		}
	}

	/** Returns a human friendly description of the air quality
	 * @param int $index: air quality index
	 * @return string
	 */
	/*private function getAirQualityDescription($index)
	{
		$languageId = $this->ReadPropertyInteger('languageId');

		// Translations in languageId => index format
		$translations = array(
			0 => array(1 => 'good', 2 => 'sufficient', 	3 => 'bad'),
			1 => array(1 => 'gut', 	2 => 'ausreichend', 3 => 'schlecht'),
			3 => array(1 => 'goed', 2 => 'voldoende', 	3 => 'slecht')
		);

		return $translations[$languageId][$index];
	}*/


	/** Creates and sets the temperature status variable
	 * @param float $$temperature
	 * @return boolean: true if successful, false on failure
	 */
	private function setTemperature($temperature)
	{
		$variableId = $this->GetIDForIdent('temperature');

		/*/ Create status variable if variable not set
		if ($variableId == false)
		{
			$variableId = $this->registerVariableFloat('temperature', 'Temperature', '~Temperature', 0);
		}*/

		return SetValueFloat($variableId, round($$temperature, 1));
	}

	/** Creates and sets the humidity status variable
	  * @param float $humidity
	  * @return boolean: true if successful, false on failure
	  */
	private function setHumidity($humidity)
	{
		$variableId = $this->GetIDForIdent('humidity');

		/*/ Create status variable if variable not set
		if ($variableId == false)
		{
			$variableId = $this->registerVariableFloat('humidity', 'Humidity', '~Humidity.F', 1);
		}*/

		return SetValueFloat($variableId, round($humidity, 1));
	}

	/** Creates and sets the dew point status variable
	 * @param float $dewPoint
	 * @return boolean: true if successful, false on failure
	 */
	private function setDewPoint($dewPoint)
	{
		$variableId = $this->GetIDForIdent('dewPoint');

		/*/ Create status variable if variable not set
		if ($variableId == false)
		{
			$variableId = $this->registerVariableFloat('dewPoint', 'Dew point', '~Temperature', 2);
		}*/

		return SetValueFloat($variableId, round($dewPoint, 1));
	}

	/** Creates and sets the VOC status variable
	 * @param float $voc
	 * @return boolean: true if successful, false on failure
	 */
	private function setVolatileOrganicCompounds($voc)
	{
		$variableId = $this->GetIDForIdent('voc');

		/*/ Create status variable if variable not set
		if ($variableId == false)
		{
			// Create variable profile
			if (IPS_GetVariableProfile('VolatileOrganicCompounds') == false)
			{
				IPS_CreateVariableProfile('VolatileOrganicCompounds', 1);
				IPS_SetVariableProfileText('VolatileOrganicCompounds', '', 'ppm');
				IPS_SetVariableProfileIcon('VolatileOrganicCompounds', 'ErlenmeyerFlask');
				IPS_SetVariableProfileValues('VolatileOrganicCompounds', 0, 2000, 250);
			}

			$variableId = $this->registerVariableInteger('voc', 'Volatile organic compounds', 'VolatileOrganicCompounds', 3);
		}*/

		return SetValueInteger($variableId, round($voc));
	}

	/** Creates and sets the air quality index status variable
	 * @param int $airQualityIndex
	 * @return boolean: true if successful, false on failure
	 */
	private function setAirQualityIndex($airQualityIndex)
	{
		$variableId = $this->GetIDForIdent('airQualityIndex');

		/*/ Create status variable if variable not set
		if ($variableId == false)
		{
			// Create variable profile
			if (IPS_GetVariableProfile('AirQuality') == false)
			{
				IPS_CreateVariableProfile('AirQuality', 1);
				IPS_SetVariableProfileIcon('AirQuality', 'Climate');
				IPS_SetVariableProfileAssociation('AirQuality', 1, 'Good', 'Climate', 0x64af3f);
				IPS_SetVariableProfileAssociation('AirQuality', 2, 'Sufficient', 'Climate', 0x93af3f);
				IPS_SetVariableProfileAssociation('AirQuality', 2, 'Bad', 'Warning', 0xaf4c3f);
			}

			$variableId = $this->registerVariableInteger('airQualityIndex', 'Air quality', 'AirQuality', 4);
		}*/

		return SetValueInteger($variableId, $airQualityIndex);
	}


}
?>