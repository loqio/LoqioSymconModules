<?

class AirQualitySensor extends IPSModule
{

	public function Create()
	{
		// Never delete this line
		parent::Create();


		$this->RegisterPropertyInteger("sensorInstance", 0);
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
}
?>