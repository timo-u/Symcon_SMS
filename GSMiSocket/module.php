<?php

declare(strict_types=1);


require_once(__DIR__.'/../libs/smsutilities.php');

class iSocket extends IPSModule
{
    use SmsUtilities;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->CreateVariableProfile();

        $this->RegisterPropertyString('PhoneNumber', '+49 0000 0000');
        $this->RegisterPropertyString('Pin', '');

        $this->ConnectParent('{E524191D-102D-4619-BFEF-126A4BE49F88}');

        $this->RegisterVariableInteger('GSM', $this->Translate('GSM'), 'GSM_dbm', 10);
        $this->RegisterVariableBoolean('Out1', $this->Translate('Output'), '~Switch', 1);

        $this->EnableAction("Out1");

        $this->RegisterScript('On', $this->Translate('Output ON'), '<? SMS_iSocketSetOutput(IPS_GetParent($_IPS[\'SELF\']) , true); ', 20);
        $this->RegisterScript('Off', $this->Translate('Output OFF'), '<? SMS_iSocketSetOutput(IPS_GetParent($_IPS[\'SELF\']), false); ', 21);
        $this->RegisterScript('Reset', $this->Translate('Output Reset'), '<? SMS_iSocketRestart(IPS_GetParent($_IPS[\'SELF\'])); ', 22);
        $this->RegisterScript('Status', $this->Translate('Get Status'), '<? SMS_iSocketGetStatus(IPS_GetParent($_IPS[\'SELF\'])); ', 23);




    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $phonenumber = str_replace(" ", "", $this->ReadPropertyString('PhoneNumber'));
        if ($phonenumber == '+49 0000 0000' || !$this->startswith($phonenumber, '+')) {
            $this->SetStatus(204);
        } else {
            $this->SetReceiveDataFilter(".*".str_replace("+", "\+", $phonenumber).".*");
        }
    }


    private function HandleMessage($text)
    {
        $text = strtolower($text);
        $text = preg_replace("/(\r\n)|(\r)|(\n)/u", " ", $text);

        $this->SendDebug(__FUNCTION__, 'Translated Message: ' . $text, 0);

        // Antwort auf Status
        if (!((strpos($text, 'signal') == false) || (strpos($text, 'temp') == false))) {
            $this->SendDebug(__FUNCTION__, 'MessageContent Match', 0);

            $gsm = ($this->between('signal:', 'temp:', $text));
            $gsm = $this->between('(', ' dbm)', $gsm);

            $this->SendDebug(__FUNCTION__, 'GSM'. $gsm ." dBm", 0);
            $this->SetValue('GSM', intval($gsm));

            $this->SetStatus(102);
        }
        if (str_contains($text, "power socket on")) {
            $this->SetValue('Out1', true);
            $this->SetStatus(102);
        }

        if (str_contains($text, "power socket off")) {
            $this->SetValue('Out1', false);
            $this->SetStatus(102);
        }

    }



    public function iSocketSetOutput(bool $value)
    {
        $this->SendDebug(__FUNCTION__, ' value: ' . $value, 0);

        if ($value) {
            $action = 'ON';
        } else {
            $action = 'OFF';
        }
        $pin = $this->ReadPropertyString('Pin');

        return $this->SendMessage($pin. $action);
    }

    public function iSocketRestart()
    {
        $this->SendDebug(__FUNCTION__, "", 0);
        $pin = $this->ReadPropertyString('Pin');
        $action = "OFF10";

        return $this->SendMessage($pin. $action);
    }


    public function iSocketGetStatus()
    {
        $this->SendDebug(__FUNCTION__, 'execute', 0);
        $pin = $this->ReadPropertyString('Pin');

        return $this->SendMessage($pin."STATUS");
    }

    public function RequestAction($Ident, $Value)
    {
        // RequestAction wird auch genutzt um die interenen Funtionen nach außen zu verstecken
        $this->SendDebug(__FUNCTION__, 'Ident: '. $Ident . " value: ". $Value, 0);

        switch ($Ident) {
            case "EnableLogging":
                $this->EnableLogging();
                break;
            case "DisableLogging":
                $this->DisableLogging();
                break;
            case 'Out1':
                $this->iSocketSetOutput($Value);
                break;
            case 'Restart':
                $this->iSocketRestart();
                break;
            case 'Status':
                $this->iSocketGetStatus();
                break;
        }
    }



    public function EnableLogging()
    {
        $archiveId = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];

        $arr = ['GSM', 'Out1'];

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
        $arr = ['GSM', 'Out1'];

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


    private function CreateVariableProfile()
    {
        $this->SendDebug(__FUNCTION__, '', 0);

        if (!IPS_VariableProfileExists('GSM_dbm')) {
            IPS_CreateVariableProfile('GSM_dbm', 1);
            IPS_SetVariableProfileDigits('GSM_dbm', 1);
            IPS_SetVariableProfileText('GSM_dbm', '', ' dBm');
            IPS_SetVariableProfileValues('GSM_dbm', -100, 0, 1);
        }
    }

}
