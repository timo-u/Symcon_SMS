<?php

declare(strict_types=1);

trait SMSUtilities
{
    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();


        $phonenumber = $this->ReadPropertyString('PhoneNumber');
        if ($phonenumber == '+49 0000 0000' || !$this->startswith($phonenumber, '+')) {
            $this->SetStatus(204);
        } else {
            $this->SetReceiveDataFilter(".*".str_replace(array("+"," ","-"), array("\+","",""), $phonenumber).".*");
        }

        $this->SetStatus(104);
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
            $this->HandleMessage($text);
        }
    }



    public function SendMessage(string $text)
    {
        $this->SendDebug('SendMessage()', 'text: "' . $text . '"', 0);
        $phoneNumber = str_replace(' ', '', $this->ReadPropertyString('PhoneNumber'));
        $phoneNumber = str_replace('-', '', $phoneNumber);

        try {
            $data = [
                    'sender' => $phoneNumber,
                    'text'   => $text
                ];
            $this->SendDebug('SendMessage()', 'SendDataToParent: ' . json_encode(['Buffer' => $data]), 0);
            return $this->SendDataToParent(json_encode(['DataID' => '{9402145A-5F74-484D-8F83-4B26C3D36343}', 'Buffer' => $data]));
        } catch (Exception $e) {
            $this->SendDebug('SendMessage()', 'Exception: ' . $e->getMessage(), 0);
            return false;
        }
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

        if (strlen($end) != false) {

            $return = substr($return, 0, stripos($return, $end));
        }

        return trim(preg_replace('#\r|\n#', '', $return), ' ');
    }

}
