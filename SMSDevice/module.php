<?

	class SMSDevice extends IPSModule {
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("PhoneNumber", "+49 0000 0000");
			
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

			$data = json_encode([
			"sender" => $phoneNumber,
			"text" => $text,
			]);
			SendDataToParent($data) ;
			
			return true;
			
			

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return false;
			}
			
		}
		
	}
?>
