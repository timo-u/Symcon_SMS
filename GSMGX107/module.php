<?php

declare(strict_types=1);
    class GX107 extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('PhoneNumber', '+49 0000 0000');
            $this->RegisterPropertyString('Pin', '1513');
            $this->RegisterPropertyBoolean('AlwaysOn1', false);
            $this->RegisterPropertyBoolean('AlwaysOn2', false);

            $this->ConnectParent('{E524191D-102D-4619-BFEF-126A4BE49F88}');

            $this->RegisterVariableFloat('Voltage', $this->Translate('Voltage'), '~Volt', 10);
            $this->RegisterVariableInteger('GSM', $this->Translate('GSM'), '~Intensity.100', 10);

            $this->RegisterVariableBoolean('In1', $this->Translate('Input 1'), '~Switch', 1);
            $this->RegisterVariableBoolean('Out1', $this->Translate('Output 1'), '~Switch', 1);
            $this->RegisterVariableBoolean('Out2', $this->Translate('Output 2'), '~Switch', 1);


            $this->RegisterTimer("SetOut1Timer", 0, 'SMS_GX107SetOut1($_IPS[\'TARGET\']);');
            $this->RegisterTimer("SetOut2Timer", 0, 'SMS_GX107SetOut2($_IPS[\'TARGET\']);');

 
            $this->RegisterScript('Out1On', $this->Translate('Output 1 on'), '<? SMS_GX107SetOutput(IPS_GetParent($_IPS[\'SELF\']), 1 , true); ', 20);
            $this->RegisterScript('Out1Off', $this->Translate('Output 1 off'), '<? SMS_GX107SetOutput(IPS_GetParent($_IPS[\'SELF\']), 1 , false); ', 21);
            $this->RegisterScript('Out2On', $this->Translate('Output 2 on'), '<? SMS_GX107SetOutput(IPS_GetParent($_IPS[\'SELF\']), 2 , true); ', 22);
            $this->RegisterScript('Out2Off', $this->Translate('Output 2 off'), '<? SMS_GX107SetOutput(IPS_GetParent($_IPS[\'SELF\']), 2 , false); ', 23);

        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            if (strlen($this->ReadPropertyString('Pin')) != 4) {
                $this->SetStatus(203);
            }
			
			$phonenumber = $this->ReadPropertyString('PhoneNumber');
            if ($phonenumber == '+49 0000 0000' || !$this->startswith($phonenumber, '+')) {
                $this->SetStatus(204);
            }
			else{
				$this->SetReceiveDataFilter(".*".str_replace("+", "\+", $phonenumber).".*");
			}
           
            $this->SetStatus(104);
        }

        public function EnableLogging()
        {
            $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

            $arr = ['Voltage', 'GSM', 'In1', 'Out1', 'Out2'];

            foreach ($arr as &$ident) {
                $id = @$this->GetIDForIdent($ident);

                if ($id == 0) {
                    continue;
                }
                AC_SetLoggingStatus($archiveId, $id, true);
                AC_SetAggregationType($archiveId, $id, 0); // 0 Standard, 1 Zähler
                AC_SetGraphStatus($archiveId, $id, true);
            }

            IPS_ApplyChanges($archiveId);
        }

        public function DisableLogging()
        {
            $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
            $arr = ['Voltage', 'GSM', 'In1', 'Out1', 'Out2'];

            foreach ($arr as &$ident) {
                $id = $this->GetIDForIdent($ident);
                if ($id == 0) {
                    continue;
                }
                AC_SetLoggingStatus($archiveId, $id, false);
                AC_SetGraphStatus($archiveId, $id, false);
            }

            IPS_ApplyChanges($archiveId);
        }

        public function ReceiveData($JSONString)
        {

        // Empfangene Daten vom Gateway/Splitter
            $data = json_decode($JSONString);

            $phoneNumber = str_replace(' ', '', $this->ReadPropertyString('PhoneNumber'));
            $phoneNumber = str_replace('-', '', $phoneNumber);
            $data = $data->Buffer;

            $sender = $data->sender;
            $text = $data->text;
            $this->SendDebug('ReceiveData()', 'ReceiveData Sender: ' . $sender . ' Text: ' . $text, 0);

            if ($phoneNumber == $sender) {
                $this->SendDebug('ReceiveData()', 'phonenumber match', 0);

                $text = strtolower($text);
				$text = preg_replace("/(\r\n)|(\r)|(\n)/u"," ", $text); 

                // Translate Status Message German => English
                $text = str_replace(' aus ', ' off ', $text);
                $text = str_replace(' ein ', ' on ', $text);
                $text = str_replace('spannung:', 'voltage:', $text);

                $this->SendDebug('ReceiveData()', 'Translated Message: ' . $text, 0);

                if (!((strpos($text, 'gsm') == false) || (strpos($text, 'voltage') == false) || (strpos($text, 'out1') == false) || (strpos($text, 'out2') == false) || (strpos($text, 'incall') == false))) {
                    $this->SendDebug('ReceiveData()', 'MessageContent Match', 0);

                    $this->SetValue('GSM', intval($this->between('gsm: ', '% akku', $text)));
                    $this->SetValue('In1', ($this->between('in1: ', 'out1:', $text) == 'high'));
					$out1 = ($this->between('out1: ', 'out2:', $text) == 'on');
                    $this->SetValue('Out1', $out1);
					
					if(!$out1 && $this->ReadPropertyBoolean('AlwaysOn1')){
						$this->SendDebug('ReceiveData()', '(Out 1 ==  Off  && AlwaysOn active) ==> SetOut1Timer = 10sek', 0);
						$this->SetTimerInterval("SetOut1Timer", 10* 1000); // nach 10 sek automatisiert wieder einschalten
					}
					$out2 =($this->between('out2: ', 'incall:', $text) == 'on');
                    $this->SetValue('Out2',$out2 );
					
					if(!$out2 && $this->ReadPropertyBoolean('AlwaysOn2')){
						$this->SendDebug('ReceiveData()', '(Out 2 ==  Off  && AlwaysOn active) ==> SetOut2Timer = 10sek', 0);
						$this->SetTimerInterval("SetOut2Timer", 10* 1000);// nach 10 sek automatisiert wieder einschalten
					}
					
                    $this->SetValue('Voltage', ($this->between('voltage: ', 'v adc:', $text)));

                    $this->SetStatus(102);
                } else {
                    $this->SendDebug('ReceiveData()', 'Messagecontent does not match!. Message: ' . $text, 0);
                }
            }
        }

        public function GX107SetOutput(int $number, bool $value)
        {
            $this->SendDebug('GX107SetOutput()', 'number: ' . $number . ' value: ' . $value, 0);

            if ($number > 2 || $number < 1) {
                return false;
            }

            if ($value) {
                $action = 'set';
            } else {
                $action = 'reset';
            }

            $pin = $this->ReadPropertyString('Pin');

            return $this->GX107SendMessage($action . ' out' . $number . ' #' . $pin);
        }

        public function GX107RestartOutput(int $number, int $time)
        {
            $this->SendDebug('GX107RestartOutput()', 'number: ' . $number . ' time: ' . $time. " s", 0);

            if ($number > 2 || $number < 1) {
                return false;
            }

            if ($number ==1) {
                $this->SetTimerInterval("SetOut1Timer", $time * 1000);
            }
            if ($number ==2) {
                $this->SetTimerInterval("SetOut2Timer", $time * 1000);
            }
             return $this->GX107SetOutput($number,false);
        }

        public function GX107GetStatus()
        {
            $this->SendDebug('GX107GetStatus()', 'execute', 0);
            $pin = $this->ReadPropertyString('Pin');

            return $this->GX107SendMessage('Status #' . $pin);
        }

        public function GX107SendMessage(string $text)
        {
            $this->SendDebug('GX107SendMessage()', 'text: "' . $text . '"', 0);
            $phoneNumber = str_replace(' ', '', $this->ReadPropertyString('PhoneNumber'));
            $phoneNumber = str_replace('-', '', $phoneNumber);

            try {
                $data = [
                    'sender' => $phoneNumber,
                    'text'   => $text
                ];
                $this->SendDebug('GX107SetOutput()', 'SendDataToParent: ' . json_encode(['Buffer' => $data]), 0);
                return $this->SendDataToParent(json_encode(['DataID' => '{9402145A-5F74-484D-8F83-4B26C3D36343}', 'Buffer' => $data]));
            } catch (Exception $e) {
                $this->SendDebug('GX107SetOutput()', 'Exception: ' . $e->getMessage(), 0);
                return false;
            }
        }

        public function GX107SetOut1()
        {
            $this->SendDebug('SetOut1()', '(TimerEvent)', 0);
            $this->SetTimerInterval("SetOut1Timer", 0);
            return $this->GX107SetOutput(1,true);
        }

        public function GX107SetOut2()
        {
            $this->SendDebug('SetOut2()', '(TimerEvent)', 0);
            $this->SetTimerInterval("SetOut2Timer", 0);
            return $this->GX107SetOutput(2,true);
        }

    
        private function startswith($haystack, $needle)
        {
            return strpos($haystack, $needle) === 0;
        }

        private function between($start, $end, $content)
        {
            if (strpos($content, $start) == false) {
                return '';
            }

            $return = substr($content, stripos($content, $start) + strlen($start));

            if (strlen($end) != 0) {
                $return = substr($return, 0, stripos($return, $end));
            }

            return trim(preg_replace('#\r|\n#', '', $return), ' ');
        }
    }
