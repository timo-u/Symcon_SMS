<?

	class Teltonika extends IPSModule {
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("SMSUsername", "user");
			$this->RegisterPropertyString("SMSPassword", "");
			$this->RegisterPropertyString("IPAddress", "http://192.168.1.1:80");
			$this->RegisterPropertyInteger("ReadMessagesIntervall", 10);
			
			$this->RegisterPropertyString("TestNumber", "+49 170 123456");
			$this->RegisterPropertyString("TestMessage", "Test");
			
			$this->RegisterTimer("UpdateMessages", $this->ReadPropertyInteger("ReadMessagesIntervall")*1000, 'RUT_GetMessages($_IPS[\'TARGET\']);');
		}

		public function ApplyChanges() {
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->SetTimerInterval("UpdateMessages", $this->ReadPropertyInteger("ReadMessagesIntervall")*1000);
			
		
		}
		public function SendTestMessage() {
			
			 $number = $this->ReadPropertyString("TestNumber");
			 $message = $this->ReadPropertyString("TestMessage");
			if (strlen($number)<5 || strlen($message)<3)
			{
				echo "Message or Number too short";
				return;
			}
		
			bool $result = $this->SendMessage(urlencode($number), urlencode($message));
		
		if(result == true)
		{
			echo "Message sent";
		}
		else
		{
			echo "Message not sent";
		}
		
		}
		
		
		public function GetMessages() {
			
			try
			{

			$curl = curl_init();

			curl_setopt_array($curl, array(
			CURLOPT_URL => $this->ReadPropertyString("IPAddress")."/cgi-bin/sms_list",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "username=".urlencode($this->ReadPropertyString("SMSUsername"))."&password=".urlencode($this->ReadPropertyString("SMSPassword")),
			CURLOPT_HTTPHEADER => array(
			"content-type: application/x-www-form-urlencoded"
			),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
			} else {
			echo $response;
			}

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return "";
			}
			
			
			
		}
		public function CheckConnection() {
			
			try
			{

			$curl = curl_init();

			curl_setopt_array($curl, array(
			CURLOPT_URL => $this->ReadPropertyString("IPAddress")."/cgi-bin/sms_read",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "username=".urlencode($this->ReadPropertyString("SMSUsername"))."&password=".urlencode($this->ReadPropertyString("SMSPassword")),
			CURLOPT_HTTPHEADER => array(
			"content-type: application/x-www-form-urlencoded"
			),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error:"."\r\n" . $err;
				$this->SetStatus(202);
				die;
			} 
			
			if (strpos($response, 'out of range') === 0) 
			{	
				echo "Connection Successfull";
				$this->SetStatus(102);
			}
			else 
			{
				echo "Connection failed: "."\r\n" . $response ;
				$this->SetStatus(201);
			}
			

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return false;
			}
			
			
			
		}
	
		public function DeleteMessage(integer $id) {
			
			try
			{

			$curl = curl_init();

			curl_setopt_array($curl, array(
			CURLOPT_URL => $this->ReadPropertyString("IPAddress")."/cgi-bin/sms_delete",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "username=".urlencode($this->ReadPropertyString("SMSUsername"))."&password=".urlencode($this->ReadPropertyString("SMSPassword"))."&number=".$id,
			CURLOPT_HTTPHEADER => array(
			"content-type: application/x-www-form-urlencoded"
			),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
			} else {
			echo $response;
			}

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return "";
			}
			
		}
		public function SendMessage(string $phoneNumber , string $text) {
			
			try
			{

			$curl = curl_init();

			curl_setopt_array($curl, array(
			CURLOPT_URL => $this->ReadPropertyString("IPAddress")."/cgi-bin/sms_delete",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "username=".urlencode($this->ReadPropertyString("SMSUsername"))."&password=".urlencode($this->ReadPropertyString("SMSPassword"))."&number=".urlencode($phoneNumber)."&text=".urlencode($text),
			CURLOPT_HTTPHEADER => array(
			"content-type: application/x-www-form-urlencoded"
			),
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

			if ($err) {
				echo "cURL Error #:" . $err;
			} else {
			return (strpos($response, 'OK') === 0);
			}

			}
			catch (Exception $e) {
				echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
				return "";
			}
			
		}
		
	}
?>
