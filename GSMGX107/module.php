<?php
declare(strict_types=1);


require_once(__DIR__.'/../libs/smsutilities.php');

    class GX107 extends IPSModule
    {
		use SmsUtilities;
		
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


            $this->RegisterTimer("SetOut1Timer", 0, 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "SetOut1Timer");');
            $this->RegisterTimer("SetOut2Timer", 0, 'IPS_RequestAction($_IPS["TARGET"], "TimerCallback", "SetOut2Timer");');

 
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


        private function HandleMessage($text)
        {
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

            return $this->SendMessage($action . ' out' . $number . ' #' . $pin);
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

            return $this->SendMessage('Status #' . $pin);
        }
	
		public function RequestAction($Ident, $Value) {
			// RequestAction wird genutzt um die interenen Funtionen nach außen zu verstecken 
			switch($Ident) {
				case "TimerCallback":
					$this->TimerCallback($Value);
					break;
				case "EnableLogging":
					$this->EnableLogging();
					break;
				case "DisableLogging":
					$this->DisableLogging();
					break;	
				case 'Out1':
					$this->GX107SetOutput(1,$value);
					break;
				case 'Out2':
					$this->GX107SetOutput(2,$value);
					break;	
			}
		}
		
		private function TimerCallback(string $TimerID){
		
		$this->SetTimerInterval($TimerID, 0);	//den Timer stoppen => Einmaliges Ereignis

			switch($TimerID){ 					// Prüfen, welcher Timer ausgelöst wurde.				
				case "SetOut1Timer":
					$this->SendDebug('SetOut1()', '(TimerEvent)', 0);
					$this->GX107SetOutput(1,true);
					break;
							
				case "SetOut2Timer":
					$this->SendDebug('SetOut2()', '(TimerEvent)', 0);
					$this->GX107SetOutput(2,true);
					break;
			}				
		}
		
		
        private function EnableLogging()
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

        private function DisableLogging()
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
		
    }
