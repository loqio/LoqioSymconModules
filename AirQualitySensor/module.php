<?

/** The AirQualitySensor module computes the CO2 equivalence from the output of the eService 1-Wire air quality sensor
  */
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
		$this->SetTimerInterval("Update", 5000);*/

	}

	/**
	  * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
	  * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
	  *
	  * ABC_MeineErsteEigeneFunktion($id);
	  *
	  */
	public function MeineErsteEigeneFunktion()
	{
		// Selbsterstellter Code
	}
}
?>