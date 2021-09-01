<?php

declare(strict_types=1);
    class SMSDevice extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            $this->RegisterPropertyString('PhoneNumber', '+49 0000 0000');
            $this->RegisterPropertyInteger('ReceiveObjectID', 0);
            $this->RegisterPropertyString('TestMessage', 'Test');

            $this->ConnectParent('{E524191D-102D-4619-BFEF-126A4BE49F88}');
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();
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

            if ($phoneNumber == $sender) {
                $this->SendDebug('ReceiveData()', 'ReceiveData Sender: ' . $sender . ' Text: ' . $text, 0);

                $id = $this->ReadPropertyInteger('ReceiveObjectID');

                if ($id != 0) {
                    IPS_RunScriptEx($id, ['sender' => $sender, 'text' => $text]);
                }
            }
        }

        public function DeviceSendMessage(string $text)
        {
            $phoneNumber = str_replace(' ', '', $this->ReadPropertyString('PhoneNumber'));
            $phoneNumber = str_replace('-', '', $phoneNumber);

            try {
                $data = [
                    'sender' => $phoneNumber,
                    'text'   => $text
                ];

                return $this->SendDataToParent(json_encode(['DataID' => '{9402145A-5F74-484D-8F83-4B26C3D36343}', 'Buffer' => $data]));
            } catch (Exception $e) {
                echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
                return false;
            }
        }
    }
