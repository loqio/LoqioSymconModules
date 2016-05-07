<?

class AirQualitySensor extends IPSModule
{

	public function Create()
	{
		// Never delete this line
		parent::Create();


		$this->RegisterPropertyInteger('sensorInstance', 0);
	}

	public function ApplyChanges()
	{
		// Never delete this line
		parent::ApplyChanges();



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

	public function Compute()
	{
		$sensorId = $this->ReadPropertyInteger('sensorInstance');

		if (is_int($sensorId))
		{
			$vadId = IPS_GetObjectIdByName('VAD', $sensorId);
			$xsensId = IPS_GetObjectIdByName('XSENS', $sensorId);

			if (is_int($vadId) && is_int($xsensId))
			{
				$vad = IPS_GetValue($vadId);
				$xsens = IPS_GetValue($xsensId);

				IPS_LogMessage('Air quality sensor', 'vad: ' . $vad . ' xsens: ' . $xsens);


			}
		}
	}
}
?>