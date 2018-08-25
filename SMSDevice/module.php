<?

	class SMSDevice extends IPSModule {
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("PhoneNumber", "+49 0000 0000");
			$this->ForceParent("{E524191D-102D-4619-BFEF-126A4BE49F88}");
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();
			
			//$phoneNumber = str_replace(' ', '', $phoneNumber);
			//$phoneNumber = str_replace('-', '', $phoneNumber);
			
		
		}
		
		
		
		public function ReceiveData($JSONString) {
 
		// Empfangene Daten vom Gateway/Splitter
		$data = json_decode($JSONString);
		
		$phoneNumber = str_replace(' ', '', $this->ReadPropertyString("PhoneNumber"));
		$phoneNumber = str_replace('-', '', $phoneNumber);
		
		$sender = $data['sender'];
		$text = $data['text'];
		IPS_LogMessage ("SMSDevice", "ReceiveData Sender: ".$sender . " Text: ".$text);
		if($phoneNumber == $sender) 
			
		IPS_LogMessage ("SMSDevice", "ReceiveData Sender: ".$sender . " Text: ".$text);
		
		}
		
		public function SendTestMessage() {
			
			 $message = "Test ";
			
		
			$result = $this->SendMessage( $message);
		
		if($result == true)
		{
			echo "Message sent";
		}
		else
		{
			echo "Message not sent";
		}
		
		}
		
	
		public function MessageReceived(string $sender , string $text) {
		IPS_LogMessage ("SMSDevice", "Sender: ".$sender . " Text: ".$text);
		}
		
		
		public function SendMessage( string $text) {
			
			
			$phoneNumber = str_replace(' ', '', $this->ReadPropertyString("PhoneNumber"));
			$phoneNumber = str_replace('-', '', $phoneNumber);
			
			try
			{

			$data = [
			"sender" => $phoneNumber,
			"text" => $text
			];
			IPS_LogMessage ("SMSDevice SendMessage", json_encode($data));
			$this->SendDataToParent(json_encode(Array("DataID" => "{9402145A-5F74-484D-8F83-4B26C3D36343}", "Buffer" => $data->Buffer)));
			//$this->SendDataToParent(json_encode($data)) ;
			
			return true;
			
			

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return false;
			}
			
		}
		
	}
?>
