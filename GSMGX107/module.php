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
            $this->RegisterPropertyInteger('UpdateInterval', 21600);
            $this->RegisterPropertyInteger('Watchdog', 43200);

            $this->ConnectParent('{E524191D-102D-4619-BFEF-126A4BE49F88}');

            $this->RegisterVariableFloat('Voltage', $this->Translate('Voltage'), '~Volt', 10);
            $this->RegisterVariableInteger('GSM', $this->Translate('GSM'), '~Intensity.100', 10);

            $this->RegisterVariableBoolean('In1', $this->Translate('Input 1'), '~Switch', 1);
            $this->RegisterVariableBoolean('Out1', $this->Translate('Output 1'), '~Switch', 1);
            $this->RegisterVariableBoolean('Out2', $this->Translate('Output 2'), '~Switch', 1);

            $this->RegisterVariableBoolean('ConnectionError', $this->Translate('Connection Error'), '~Alert', 1);

            $this->RegisterTimer('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000, 'SMS_GX107GetStatus($_IPS[\'TARGET\']);');
            $this->RegisterTimer('WatchdogTimer', $this->ReadPropertyInteger('Watchdog') * 1000, 'SMS_WatchdogEvent($_IPS[\'TARGET\']);');

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

            if ($this->ReadPropertyString('PhoneNumber') == '+49 0000 0000' || !$this->startswith($this->ReadPropertyString('PhoneNumber'), '+')) {
                $this->SetStatus(204);
            }

            $this->SetTimerInterval('Update', $this->ReadPropertyInteger('UpdateInterval') * 1000);
            $this->SetTimerInterval('WatchdogTimer', $this->ReadPropertyInteger('Watchdog') * 1000);
            $this->SetStatus(104);
        }

    public function EnableLogging()
    {
        $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        $arr = ['Voltage', 'GSM', 'In1', 'Out1', 'Out2', 'ConnectionError'];

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
        $arr = ['Voltage', 'GSM', 'In1', 'Out1', 'Out2', 'ConnectionError'];
		
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
                $text = str_replace("\r", ' ', $text);

                // Translate Status Message German => English
                $text = str_replace(' aus ', ' off ', $text);
                $text = str_replace(' ein ', ' on ', $text);
                $text = str_replace('spannung:', 'voltage:', $text);

                $this->SendDebug('ReceiveData()', 'Translated Message: ' . $text, 0);

                if (!((strpos($text, 'gsm') == false) || (strpos($text, 'voltage') == false) || (strpos($text, 'out1') == false) || (strpos($text, 'out2') == false) || (strpos($text, 'incall') == false))) {
                    $this->SendDebug('ReceiveData()', 'MessageContent Match', 0);

                    $this->SetValue('GSM', intval($this->between('gsm: ', '% akku', $text)));
                    $this->SetValue('In1', ($this->between('in1: ', 'out1:', $text) == 'high'));
                    $this->SetValue('Out1', ($this->between('out1: ', 'out2:', $text) == 'on'));
                    $this->SetValue('Out2', ($this->between('out2: ', 'incall:', $text) == 'on'));
                    $this->SetValue('Voltage', ($this->between('voltage: ', 'v adc:', $text)));

                    $this->SetValue('ConnectionError', false);
                    $this->SetTimerInterval('WatchdogTimer', $this->ReadPropertyInteger('Watchdog') * 1000);
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

        private function WatcodogEvent()
        {
            $this->SendDebug('WatcodogEvent()', 'Watchdog expired', 0);
            $this->SetValue('ConnectionError', true);
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
