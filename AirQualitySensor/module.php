<?

class AirQualitySensor extends IPSModule
{

	public function Create()
	{
		// Never delete this line
		parent::Create();

		$this->RegisterPropertyInteger('sensorInstanceId', 0);

		// Create variable profiles
		if (@IPS_GetVariableProfile('VolatileOrganicCompounds') == false)
		{
			IPS_CreateVariableProfile('VolatileOrganicCompounds', 1);
			IPS_SetVariableProfileText('VolatileOrganicCompounds', '', ' ppm');
			IPS_SetVariableProfileIcon('VolatileOrganicCompounds', 'Intensity');
			IPS_SetVariableProfileValues('VolatileOrganicCompounds', 0, 2000, 250);
		}

		if (@IPS_GetVariableProfile('AirQuality') == false)
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

		// Create event
		if ($this->getUpdateEventId() == false)
		{
			$eventId = IPS_CreateEvent(0);
			IPS_SetParent($eventId, $this->InstanceID);
			IPS_SetIdent($eventId, 'updateEvent');
			IPS_SetName($eventId, "Update values");
			IPS_SetHidden($eventId, true);
			IPS_SetPosition($eventId, 5);
		}
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
				$this->setUpdateEvent();
			}
		}
	}

	/** Processes sensor readings and updates the status variables
	  * @return bool: true if successful, false on failure
	  */
	public function Update()
	{
		$success = false;

		// Sleep for two seconds to make sure all variables of the sensor instance have been updated
		IPS_Sleep(2000);

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

			$humidity 			= $this->calculateHumidity($vdd, $vad, $temperature);
			$dewPoint 			= $this->calculateDewPoint($temperature, $humidity);
			$voc				= $this->calculateVolatileOrganicCompounds($xsens);
			$airQualityIndex	= $this->getAirQualityIndex($voc);

			SetValueFloat($this->GetIDForIdent('temperature'), round($temperature, 1));
			SetValueFloat($this->GetIDForIdent('humidity'), round($humidity, 1));
			SetValueFloat($this->GetIDForIdent('dewPoint'), round($dewPoint, 1));
			SetValueInteger($this->GetIDForIdent('voc'), round($voc));
			SetValueInteger($this->GetIDForIdent('airQuality'), $airQualityIndex);

			$success = true;
			$this->SetStatus(102);
		}
		else
		{
			// Incompatible instance
			$this->setStatus(200);
		}

		return $success;
	}

	/** Sets the source variable and action of the trigger event
	  */
	private function setUpdateEvent()
	{
		$variableId = $this->getXsensVariableId();

		if ($variableId)
		{
			$eventId = $this->getUpdateEventId();

			IPS_SetEventTrigger($eventId, 0, $variableId);
			IPS_SetEventActive($eventId, true);
			IPS_SetEventScript($eventId, "AIRQ_Update(" . $this->InstanceID . ");");
		}
	}

	/** Returns object id for update event
	 * @return int
	 */
	private function getUpdateEventId()
	{
		return @IPS_GetObjectIDByIdent('updateEvent', $this->InstanceID);
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
			$variableId = @IPS_GetObjectIdByName($variableName, $sensorId);
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
	 * @param float $vdd: sensor supply voltage
	 * @param float $vad: sensor analog voltage reading
	 * @param float $temperature: temperature reading from sensor
	 * @return float
	 */
	private function calculateHumidity($vdd, $vad, $temperature)
	{
		// Vad measurement compensation in case of Vdd under voltage
		$vad = (5 / $vdd) * $vad;

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

		// Magnus formula
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
		else
		{
			$index = 3;
		}

		return $index;
	}
}
?>