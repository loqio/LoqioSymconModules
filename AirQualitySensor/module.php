<?

class AirQualitySensor extends IPSModule
{

	public function Create()
	{
		// Never delete this line
		parent::Create();


		$this->RegisterPropertyInteger('sensorInstanceId', 0);
	}

	public function ApplyChanges()
	{
		// Never delete this line
		parent::ApplyChanges();

		if ($sensorId = $this->ReadPropertyInteger('sensorInstanceId'))
		{
			if ($this->hasTriggerEvent() == false)
			{
				$this->setTriggerEvent();
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

	public function Compute($instanceId)
	{
		IPS_LogMessage('Air quality sensor', 'instanceId: ' . $this->InstanceID);
	}

	private function setTriggerEvent()
	{
		if ($triggerEventId = IPS_CreateEvent(0))
		{
			IPS_SetEventTrigger($triggerEventId, 1, 15754);        //Bei Änderung von Variable mit ID 15754
			IPS_SetParent($triggerEventId, $this->InstanceID);
			IPS_SetEventActive($triggerEventId, true);
			IPS_SetEventScript($triggerEventId, "AIRQ_Compute(" . $this->InstanceID . ")");
		}
	}

	private function getTriggerEventId()
	{
		$childrenIds = IPS_GetChildrenIDs($this->InstanceID);
		$triggerEventId = false;

		if (count($childrenIds))
		{
			foreach ($childrenIds as $childId)
			{
				$object = IPS_GetObject($childId);

				if ($object['ObjectType'] == 4)
				{
					$triggerEventId = $object['ObjectID'];
					break;
				}
			}
		}

		return $triggerEventId;
	}

	private function hasTriggerEvent()
	{
		return $this->getTriggerEventId() ? true : false;
	}

	private function getVadVariableId()
	{
		$vadVariableId = false;

		if ($sensorId = $this->ReadPropertyInteger('sensorInstanceId'))
		{
			$vadVariableId = IPS_GetObjectIdByName('VAD', $sensorId);

			/*if (is_int($vadId) && is_int($xsensId))
			{
				$vad = GetValue($vadId);
				$xsens = GetValue($xsensId);

				IPS_LogMessage('Air quality sensor', 'vad: ' . $vad . ' xsens: ' . $xsens);


			}*/
		}

		return $vadVariableId;
	}

	private function getXsensVariableId()
	{
		$xsensVariableId = false;

		if ($sensorId = $this->ReadPropertyInteger('sensorInstanceId'))
		{
			$xsensVariableId = IPS_GetObjectIdByName('XSENS', $sensorId);
		}

		return $xsensVariableId;
	}
}
?>