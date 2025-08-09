<?php

declare(strict_types=1);
class TeltonikaSMSGateway extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterVariableProfiles();
        $this->RegisterPropertyBoolean('Active', true);


        $this->RegisterPropertyString('Primary_Host', '192.168.2.1');
        $this->RegisterPropertyInteger('Primary_Port', 443);
        $this->RegisterPropertyString('Primary_Username', '');
        $this->RegisterPropertyString('Primary_Password', '');
        $this->RegisterPropertyInteger('Primary_Timeout', 5000);
        $this->RegisterPropertyString('Primary_Modem', '1-1');
        $this->RegisterPropertyBoolean('Primary_Ssl', true);
        $this->RegisterPropertyBoolean('Primary_VerifyHost', false);
        $this->RegisterPropertyBoolean('Primary_VerifyPeer', false);

        $this->RegisterPropertyBoolean('Secondary_Enabled', false);
        $this->RegisterPropertyString('Secondary_Host', '192.168.2.2');
        $this->RegisterPropertyInteger('Secondary_Port', 443);
        $this->RegisterPropertyString('Secondary_Username', '');
        $this->RegisterPropertyString('Secondary_Password', '');
        $this->RegisterPropertyInteger('Secondary_Timeout', 5000);
        $this->RegisterPropertyString('Secondary_Modem', '1-1');
        $this->RegisterPropertyBoolean('Secondary_Ssl', true);
        $this->RegisterPropertyBoolean('Secondary_VerifyHost', false);
        $this->RegisterPropertyBoolean('Secondary_VerifyPeer', false);

        $this->RegisterPropertyInteger('Mode', 0);
        $this->RegisterPropertyInteger('Retry', 3);

        $this->RegisterPropertyString('WebHookToken', $this->GenerateRandomString());

        $this->RegisterPropertyBoolean('DisableSending', false);

        $this->RegisterPropertyString('TestNumber', '');
        $this->RegisterPropertyString('TestMessage', 'Test');

        $this->RegisterPropertyInteger('ReadMessagesIntervall', 300);


        $this->RegisterTimer('UpdateMessages', $this->ReadPropertyInteger('ReadMessagesIntervall') * 1000, 'IPS_RequestAction($_IPS[\'TARGET\'],\'GetMessages\',\'\');');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->MaintainVariable('Primary_Connection', $this->Translate('primary connection'), 0, 'TR_Online', 1, true);
        $this->MaintainVariable('Primary_ReceivedMessages', $this->Translate('primary received messages'), 1, '', 10, true);
        $this->MaintainVariable('Primary_SendMessages', $this->Translate('primary send messages'), 1, '', 11, true);

        $Secondary_Enabled = $this->ReadPropertyBoolean('Secondary_Enabled');
        $this->MaintainVariable('Secondary_Connection', $this->Translate('secondary connection'), 0, 'TR_Online', 2, $Secondary_Enabled);
        $this->MaintainVariable('Secondary_ReceivedMessages', $this->Translate('secondary received messages'), 1, '', 20, $Secondary_Enabled);
        $this->MaintainVariable('Secondary_SendMessages', $this->Translate('secondary send messages'), 1, '', 21, $Secondary_Enabled);


        $this->SetTimerInterval('UpdateMessages', $this->ReadPropertyInteger('ReadMessagesIntervall') * 1000);


        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->RegisterHook('/hook/' . 'TeltonikaSMSGateway');
        }
    }

    private function RegisterHook($WebHook)
    {
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $WebHook) {
                    if ($hook['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $hooks[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }
    private function GenerateRandomString()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 30; $i++) {
            if ($i % 5 == 0 && $i != 0) {
                $randomString .= '-';
            }
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public function ForwardData($JSONString)
    {

        // Empfangene Daten vom Device
        $this->SendDebug(__FUNCTION__, ' $JSONString: ' . $JSONString, 0);

        $data = json_decode($JSONString);

        $data = $data->Buffer;
        $sender = $data->sender;

        $text = $data->text;
        return $this->GatewaySendMessage($sender, $text);
    }

    protected function ProcessHookData()
    {

        $this->SendDebug(__FUNCTION__, '$_SERVER[\'SCRIPT_NAME\']: ' .$_SERVER['SCRIPT_NAME'], 0);
        $this->SendDebug(__FUNCTION__, 'Array GET: ' . print_r($_GET, true), 0);

        if ($_SERVER['SCRIPT_NAME'] != '/hook/TeltonikaSMSGateway') {
            http_response_code(404);
            echo 'Wrong URL';
            $this->SendDebug(__FUNCTION__, 'Error Response: Wrong URL ("/hook/TeltonikaSMSGateway")', 0);
            return;
        }
        if (!array_key_exists("token", $_GET)) {
            http_response_code(401);
            echo 'Unauthorized';
            $this->SendDebug(__FUNCTION__, 'Error Response: Unauthorized (missing get paramerte "token")', 0);
            return;
        }

        if ($_GET['token'] != $this->ReadPropertyString('WebHookToken')) {
            http_response_code(403);
            echo 'Forbidden';
            $this->SendDebug(__FUNCTION__, 'Error Response: Forbidden (wrong value for parameter "token") token='.$_GET['token'], 0);
            return;
        }

        if (!array_key_exists("device", $_GET)) {
            http_response_code(404);
            echo 'Missing Device Parameter';
            $this->SendDebug(__FUNCTION__, 'Error Response: Missing Device Parameter (missing get paramerte "device")', 0);
            return;
        }
        $device = intval($_GET['device']);
        if ($device == 1 || $device == 2) {
            $this->SendDebug(__FUNCTION__, 'GetMessagesFromGateway('.$device.')', 0);
            $this->GetMessagesFromGateway($device);
            http_response_code(200);
            echo 'GetMessages for device'. $device;
            return;
        } else {
            http_response_code(404);
            echo 'Wrong device';
            $this->SendDebug(__FUNCTION__, 'Error Response: Wrong device (wrong value for parameter "device") device='.$device, 0);
            return;

        }

        http_response_code(501);
        echo 'Not Implemented';


    }

    private function GetMessages()
    {

        $this->SendDebug(__FUNCTION__, 'aufgerufen', 0);

        if ($this->ReadPropertyBoolean('Secondary_Enabled')) {
            $gateways = 2;
        } else {
            $gateways = 1;
        }

        $success = true;

        for ($device = 1; $device <= $gateways; $device++) {

            if (!$this->GetMessagesFromGateway($device)) {
                $success = false;
            }

        }

        if ($success) {
            $this->SetStatus(102); //Staus: Aktiv
        } else {
            $this->SetStatus(201);
        }

        return $success;


    }

    private function GetMessagesFromGateway(int $device)
    {

        $this->SendDebug(__FUNCTION__, 'device: ' . $device, 0);
        $success = true;
        $parameter = array( "method" => "GET",
                                "subpath" => "/api/messages/status",
                                "getparameter" => array()   );

        $response = ($this->ApiCall($device, $parameter));
        if ($response == false) {
            return false;
        }

        $data = json_decode($response);
        if ($data == false) {
            return false;
        }

        if ($data->apidata->success) {

            foreach ($data->apidata->data as $message) {

                $id = $message->id;
                $modemid = $message->modem_id;
                $sender = $message->sender;
                $text = $message->message;
                $date = $message->date;
                $this->SendDebug(__FUNCTION__, 'device: ' . $device.' sender: ' . $sender.' text: ' . $text, 0);
                $this->MessageReceived($sender, $text);
                $this->DeleteMessage($device, $modemid, intval($id));
                $this->SetValue($this->GetDevicePrefix($device).'ReceivedMessages', $this->GetValue($this->GetDevicePrefix($device).'ReceivedMessages') + 1);
            }

        } else {
            $success = false;
        }

        return $success;
    }



    private function MessageReceived(string $sender, string $text)
    {
        $data = [
            'sender' => $sender,
            'text'   => utf8_encode($text)
        ];


        $this->SendDataToChildren(json_encode(['DataID' => '{C78CF679-C945-463E-9F2C-10A9FAD05DD3}', 'Buffer' => $data]));
        $this->SendDebug(__FUNCTION__, 'Sender: ' . $sender . ' Text: ' . $text, 0);
    }

    private function DeleteMessage(int $device, string $modemId, int $smsid)
    {

        $this->SendDebug(__FUNCTION__, 'Device: ' . $device . ' modem_id: ' . $modemId. ' sms_id: ' . $smsid, 0);
        $postfield = json_encode(array("data" => array("modem_id" => $modemId,"sms_id" => array($smsid))));


        $parameter = array( "method" => "POST",
                            "postfield" => $postfield,
                            "subpath" => "/api/messages/actions/remove_messages",
                            "getparameter" => array());

        $response = ($this->ApiCall($device, $parameter));
        if ($response == false) {
            return false;
        }
        $data = json_decode($response);

        return $data;
    }
    private function GetDevicePrefix(int $device)
    {
        if ($device == 1) {
            return "Primary_";

        } else {
            return "Secondary_";
        }

    }

    private function SendMessageToGateway(int $device, string $phoneNumber, string $text)
    {
        $this->SendDebug(__FUNCTION__, 'Device: ' . $device . ' phoneNumber: ' . $phoneNumber. ' text: ' . $text, 0);

        $devicePrefix = $this->GetDevicePrefix($device);

        $modemId = $this->ReadPropertyString($devicePrefix.'Modem');

        try {
            $postfield = json_encode(array("data" => array("modem" => $modemId,"number" => $phoneNumber,"message" => $text),));

            $parameter = array( "method" => "POST",
                                "postfield" => $postfield,
                                "subpath" => "/api/messages/actions/send",
                                "getparameter" => array());

            $response = ($this->ApiCall($device, $parameter));
            if ($response == false) {
                return false;
            }

            $data = json_decode($response);
            if ($data->apidata->success) {
                //Anzahl der gesendeten SMS auslesen
                $this->SetValue($devicePrefix.'SendMessages', $this->GetValue($devicePrefix.'SendMessages') + $data->apidata->data->sms_used);
                return true;
            }

            return false;
        } catch (Exception $e) {
            $this->SendDebug(__FUNCTION__, 'Exception abgefangen: ', $e->getMessage(), 0);
            return false;
        }

    }

    public function GatewaySendMessage(string $phoneNumber, string $text)
    {

        return $this->GatewaySendMessageEx($phoneNumber, $text, -1, -1);
    }

    public function GatewaySendMessageEx(string $phoneNumber, string $text, int $mode, int $retry)
    {
        $this->SendDebug(__FUNCTION__, 'Aufruf mit Parameter:  phoneNumber: ' . $phoneNumber.' Text: ' . $text.' mode: ' . $mode.' retry: ' . $retry, 0);
        $phoneNumber = str_replace(' ', '', $phoneNumber);
        $phoneNumber = str_replace('-', '', $phoneNumber);

        if (strlen($phoneNumber) == 0 || strlen($text) == 0) {
            $this->SendDebug(__FUNCTION__, 'Error: PhoneNumber or text is empty' . $text, 0);
            return false;
        }

        if ($this->ReadPropertyBoolean('DisableSending') == true) {
            $this->SendDebug(__FUNCTION__, 'Sending Disabled: try to send message to ' . $phoneNumber . ' with contet: ' . $text, 0);
            return true;
        }

        
        if ($mode < 0) {
            $mode = $this->ReadPropertyInteger('Mode');
        }

        // Wenn kein 2. Gateway verwendet wird immer nur das 1. verweden. 
        if(!$this->ReadPropertyBoolean('Secondary_Enabled'))
        {
            $mode = 1; 
        }


        if ($retry < 1) {
            $retry = $this->ReadPropertyInteger('Retry');
        } // Versuche bis

        $this->SendDebug(__FUNCTION__, 'Gewählter Modus: ' . $mode, 0);
        $this->SendDebug(__FUNCTION__, 'Anzahl möglicher Versuche: ' . $retry, 0);

        $count = 0;

        for ($count = 1; $count <= $retry; $count++) {
            $this->SendDebug(__FUNCTION__, 'Sendeversuch Nummer: ' . $count, 0);
            if ($mode == 1) {
                // 1 ==>  Nur Primäres Gateway
                if ($this->SendMessageToGateway(1, $phoneNumber, $text)) {
                    return true;
                }
            } elseif ($mode == 2) {
                // 2 ==> Nur Sekrundäres Gateway
                if ($this->SendMessageToGateway(2, $phoneNumber, $text)) {
                    return true;
                }
            } elseif ($mode == 3) {
                //3 ==>  Round Robin => Gateway wird zufällig ausgewählt
                if ($this->SendMessageToGateway(rand(1, 2), $phoneNumber, $text)) {
                    return true;
                }
            } else {
                // 0 oder anderer Wert ==> Primär und Sekrundär abwechelnd beginnend mit Primär
                ($count % 2 == 1) ? $device = 1 : $device = 2;
                if ($this->SendMessageToGateway($device, $phoneNumber, $text)) {
                    return true;
                }

            }


        }

        return false;

    }

    public function RequestAction($Ident, $Value)
    {
        // RequestAction wird genutzt um die interenen Funtionen nach außen zu verstecken
        switch ($Ident) {
            case 'Reboot':
                $this->Reboot(intval($Value));
                break;
            case "Login":
                return $this->Login(intval($Value), true);
                break;
            case "ResetMessageCounter":
                return $this->ResetMessageCounter();
                break;
            case "GetMessages":
                return $this->GetMessages();
                break;
        }
    }


    public function GetConfiguratationPage(int $device)
    {
        $devicePrefix = $this->GetDevicePrefix($device);
        $url = $this->ReadPropertyString($devicePrefix.'Host').":".$this->ReadPropertyInteger($devicePrefix.'Port');

        if ($this->ReadPropertyBoolean($devicePrefix.'Ssl')) {
            $url = "https://".$url;
        } else {
            $url = "http://".$url;
        }
        
        return $url;

    }

    private function Login(int $device, bool $force = false)
    {
        $devicePrefix = $this->GetDevicePrefix($device);


        if (!$this->ReadPropertyBoolean('Active')) {
            return false;
        }

        if ($this->GetBuffer($devicePrefix.'Authentication') == 'failed' && !$force) {
            $this->SendDebug(__FUNCTION__, $devicePrefix.'Authentication has failed - Login is blocked!', 0);
            return false;
        }


        $this->SendDebug(__FUNCTION__, 'Try to log in', 0);

        $username = $this->ReadPropertyString($devicePrefix.'Username');
        $password = $this->ReadPropertyString($devicePrefix.'Password');

        $url = $this->ReadPropertyString($devicePrefix.'Host').":".$this->ReadPropertyInteger($devicePrefix.'Port');

        if ($this->ReadPropertyBoolean($devicePrefix.'Ssl')) {
            $url = "https://".$url;
        } else {
            $url = "http://".$url;
        }


        $post = json_encode(array( "username" => $username ,"password" => $password));

        if ($this->ReadPropertyBoolean($devicePrefix.'VerifyHost')) {
            $verifyhost = 2;
        } else {
            $verifyhost = 0;
        }

        $curl = curl_init();


        curl_setopt($curl, CURLOPT_URL, $url.'/api/login');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $this->ReadPropertyInteger($devicePrefix.'Timeout'));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $verifyhost);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->ReadPropertyBoolean($devicePrefix.'VerifyPeer'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        $this->SendDebug(__FUNCTION__, 'Response:' . $response, 0);

        curl_close($curl);
        if ($err) {
            $this->SetStatus(201);
            $this->SendDebug(__FUNCTION__, 'Error:' . $err, 0);
            $this->SetBuffer($devicePrefix.'SessionId', '');
            $this->SetValue($devicePrefix.'Connection', false);
            return false;
        }

        $data = json_decode($response, false);


        $success = ($data->success);

        if ($success) {
            if (property_exists($data->data, 'token')) {
                $this->SetStatus(102);
                $this->SetBuffer($devicePrefix.'SessionId', $data->data->token);
                $this->SetBuffer($devicePrefix.'Authentication', '');
                $this->SetValue($devicePrefix.'Connection', true);
            }

            if (property_exists($data, 'ubus_rpc_session')) {
                $this->SetBuffer($devicePrefix.'Authentication', 'failed');
                $this->SendDebug(__FUNCTION__, 'Falsche API-Version! Update erforderlich', 0);
                $this->SetValue($devicePrefix.'Connection', false);
                $this->SetStatus(203); // Version Conflict
                $success = false;
            }

        } else {
            $this->SendDebug(__FUNCTION__, 'Authentication failed', 0);
            $this->SendDebug(__FUNCTION__, 'Code:' . $data->errors[0]->code, 0);
            if ($data->errors[0]->code == 120) {
                $this->SendDebug(__FUNCTION__, 'Login Failed.', 0);
            }

            $this->LogMessage('Teltonika SMS Gateway =>Login() => Code:' . $data->errors[0]->code, KL_ERROR);

            $this->SetBuffer($devicePrefix.'Authentication', 'failed');
            $this->SetValue($devicePrefix.'Connection', false);
            $this->SetStatus(202); // Authentication failed
        }

        return $success;
    }
    private function ApiCall(int $device, array $parameter)
    {
        /*
        $device == 1 => Primary Garetway
        $device == 2 => Secondary Garetway


        $parameter = array( "method"=>"get",
                           "timeout"=>1000, /* übschchreibt das in der Instanz gesetze Timeout. z.B. wichtig bei Updates, die mit der voreingestellen Zeit nicht auskommen.
                           "subpath" => "/webapi/entry.cgi",
                           "getparameter"=> array( "id=1")
                           );

        */


        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SendDebug(__FUNCTION__, 'Active == false', 0);
            return false;
        }

        if (!($device == 1 || ($device == 2 && $this->ReadPropertyBoolean('Secondary_Enabled')))) {
            $this->SendDebug(__FUNCTION__, 'device '.$device. ' not allowed.', 0);
        }


        $devicePrefix = $this->GetDevicePrefix($device);

        if ($parameter == null || !array_key_exists('subpath', $parameter)) {
            $this->SendDebug(__FUNCTION__, 'Fehlerhafte Parameter', 0);
            return false;
        }

        if (array_key_exists('method', $parameter)) {
            $method = strtoupper($parameter['method']);

            if (!($method == "GET" || $method == "POST" || $method == "PUT" || $method == "DELETE")) {
                $this->SendDebug(__FUNCTION__, 'Methode nicht erlaubt', 0);
                return false;
            }
        } else {
            $method = "GET"; // Standard Methode
        }

        $postfield = "";
        if (array_key_exists('postfield', $parameter)) {
            $postfield = $parameter['postfield'];
        }


        $timeout = $this->ReadPropertyInteger($devicePrefix.'Timeout'); // Standard-Timeout setzen
        if (array_key_exists('timeout', $parameter)) {
            $timeout = $parameter['timeout'];
        }

        $subpath = $parameter['subpath'];


        $GetParameter = "";
        if (array_key_exists('getparameter', $parameter)) {
            $GetParameter = $parameter['getparameter'];
        }

        if (!$this->ReadPropertyBoolean('Active')) {
            return false;
        }


        // Überprüfung ob sessionkey vorhanden ist => Sonst Login
        $sessionId = $this->GetBuffer($devicePrefix.'SessionId');
        $this->SendDebug(__FUNCTION__, $devicePrefix.'SessionId: '.$sessionId, 0);
        if ($sessionId == "") {
            $this->SendDebug(__FUNCTION__, '->Login', 0);
            if ($this->Login($device)) {
                $this->SendDebug(__FUNCTION__, 'Login Succsessfull', 0);
                $sessionId = $this->GetBuffer($devicePrefix.'SessionId');
            } else {
                return false; // wenn login fehlerhaft abbrechen.

            }
        }

        if ($this->ReadPropertyBoolean($devicePrefix.'VerifyHost')) {
            $verifyhost = 2;
        } else {
            $verifyhost = 0;
        }

        $url = $this->ReadPropertyString($devicePrefix.'Host').":".$this->ReadPropertyInteger($devicePrefix.'Port');

        if ($this->ReadPropertyBoolean($devicePrefix.'Ssl')) {
            $url = "https://".$url;
        } else {
            $url = "http://".$url;
        }

        if ($GetParameter != "") {
            $url = $url.$subpath. "?".  implode("&", $GetParameter);
        } else {
            $url = $url.$subpath;
        }

        $this->SendDebug(__FUNCTION__, 'URL:' . $url. ' Method:' . $method, 0);
        $this->SendDebug(__FUNCTION__, 'SessionID:' . $sessionId, 0);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        if ($postfield != "") {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postfield);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeout);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $verifyhost);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->ReadPropertyBoolean($devicePrefix.'VerifyPeer'));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json',"Authorization: Bearer $sessionId"));


        $response = curl_exec($curl);
        $err = curl_error($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $this->SendDebug(__FUNCTION__, 'CURL Response: ' . $response, 0);
        $this->SendDebug(__FUNCTION__, 'CURL Statuscode: ' . $httpcode, 0);

        curl_close($curl);

        if ($err) {
            $this->SendDebug(__FUNCTION__, 'CURL Error: ' . $err, 0);
            return false;
        }

        if ($response == null || $response == "") {
            return false;
        }


        $data = json_decode($response);

        if (property_exists($data, 'errors') && property_exists($data->errors[0], 'code')) {
            if ($data->errors[0]->code == 123) { // Invalid session
                $this->SetBuffer($devicePrefix.'SessionId', ""); // Session-ID entfernen, dadurch wird beim nächsten versuch neu angemeldet
                $this->SendDebug(__FUNCTION__, 'Invalid session', 0);
                // wenn API aufruf an abgelaufenem Token gescheitert
                if ($this->Login($device)) { 	//Login
                    return $this->ApiCall($device, $parameter); //Funktion rekusiv aufrufen.
                }

                return false;

            }
            if ($data->errors[0]->code == 121) { // Invalid session
                $this->SendDebug(__FUNCTION__, 'Login failed for any reason', 0);
                if (property_exists($data->errors[0], 'error')) {
                    $this->SendDebug(__FUNCTION__, 'Error: '. $data->errors[0]->error, 0);
                }
                $this->SendDebug(__FUNCTION__, 'From firmware version 7.12 on RUTX family devices, HTTPS is enforced by default.', 0);
                $this->SetStatus(204); // Login failed for any reason
                return false;

            }
        }

        $data = json_encode(array("apidata" => $data, "apierror" => $err, "apiparameter" => $parameter, "url" => $url ));

        $this->SendDebug(__FUNCTION__, 'Response:' . $data, 0);
        $this->SetValue($devicePrefix.'Connection', true);

        return $data;
    }

    private function ResetMessageCounter()
    {
        $this->SetValue($this->GetDevicePrefix(1).'ReceivedMessages', 0);
        $this->SetValue($this->GetDevicePrefix(1).'SendMessages', 0);

        if ($this->ReadPropertyBoolean('Secondary_Enabled')) {
            $this->SetValue($this->GetDevicePrefix(2).'ReceivedMessages', 0);
            $this->SetValue($this->GetDevicePrefix(2).'SendMessages', 0);
        }


    }

    private function Reboot(int $device)
    {

        $parameter = array( "method" => "POST",
                            "subpath" => "/api/system/actions/reboot",
                            "getparameter" => array() );
        $data =  $this->ApiCall($device, $parameter);
        if ($data != false) {
            $this->SendDebug(__FUNCTION__, "DATA: ". $data, 0);
            if ($data == false) {
                return false;
            }
            $data = json_decode($data);
           /* if ($data->apidata->success) {
                echo "Neustart erfolgreich ausgelöst";
            }
                */
            return $data->apidata->success;

        }
    }


    private function RegisterVariableProfiles()
    {
        $this->SendDebug(__FUNCTION__, 'RegisterVariableProfiles()', 0);

        if (!IPS_VariableProfileExists('TR_Online')) {
            IPS_CreateVariableProfile('TR_Online', 0);
            IPS_SetVariableProfileAssociation('TR_Online', 0, $this->Translate('Offline'), 'Warning', 0xFF0000);
            IPS_SetVariableProfileAssociation('TR_Online', 1, $this->Translate('Online'), 'Ok', 0x00FF00);
        }
    }
}
