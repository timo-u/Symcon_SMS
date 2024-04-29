<?php

declare(strict_types=1);
class TeltonikaSMSGateway extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyString('SMSUsername', 'user');
        $this->RegisterPropertyString('SMSPassword', '');
        $this->RegisterPropertyString('IPAddress', 'http://192.168.1.1:80');
        $this->RegisterPropertyInteger('ReadMessagesIntervall', 10);
        $this->RegisterPropertyBoolean('DisableSending', false);
        $this->RegisterPropertyString('TestNumber', '');
        $this->RegisterPropertyString('TestMessage', 'Test');

        $this->RegisterPropertyBoolean('VerifyHost', true);
        $this->RegisterPropertyBoolean('VerifyPeer', true);

        $this->RegisterVariableInteger('receivedMessages', $this->Translate('received messages'), '', 0);
        $this->RegisterVariableInteger('sendMessages', $this->Translate('send messages'), '', 0);

        $this->RegisterTimer('UpdateMessages', $this->ReadPropertyInteger('ReadMessagesIntervall') * 1000, 'SMS_GetMessages($_IPS[\'TARGET\']);');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->MaintainVariable('receivedMessages', $this->Translate('received messages'), 1, '', 1, true);
        $this->MaintainVariable('sendMessages', $this->Translate('send messages'), 1, '', 2, true);
        $this->SetTimerInterval('UpdateMessages', $this->ReadPropertyInteger('ReadMessagesIntervall') * 1000);
    }

    public function ForwardData($JSONString)
    {

        // Empfangene Daten vom Device
        $this->SendDebug('ForwardData()', ' $JSONString: ' . $JSONString, 0);

        $data = json_decode($JSONString);

        $data = $data->Buffer;
        $sender = $data->sender;

        $text = $data->text;
        return $this->SendMessageTo($sender, $text);
    }

    public function GetMessages()
    {
        try {
            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => $this->ReadPropertyString('IPAddress') . '/cgi-bin/sms_list',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'username=' . urlencode($this->ReadPropertyString('SMSUsername')) . '&password=' . urlencode($this->ReadPropertyString('SMSPassword')),
                CURLOPT_HTTPHEADER     => [
                    'content-type: application/x-www-form-urlencoded'
                ],
            CURLOPT_SSL_VERIFYHOST => $verifyhost,
            CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:' . $err;
                $this->SetStatus(202);
                return;
            } else {
                //echo $response;

                if (strpos($response, 'Bad username or password') === 0) {
                    $this->SetStatus(201);
                    return;
                }
                $this->SetStatus(102);

                $messages = explode('------------------------------', $response);

                foreach ($messages as $message) {
                    if (strpos($message, 'Text:') == false) {
                        continue;
                    }
                    $index = substr($message, strpos($message, 'Index:') + 7, strpos($message, 'Date:') - strpos($message, 'Index:') - 8);
                    $sender = substr($message, strpos($message, 'Sender:') + 8, strpos($message, 'Text:') - strpos($message, 'Sender:') - 9);
                    $text = substr($message, strpos($message, 'Text:') + 6, strpos($message, 'Status:') - strpos($message, 'Text:') - 7);

                    $this->MessageReceived($sender, $text);
                    $this->DeleteMessage(intval($index));
                }
            }
        } catch (Exception $e) {
            echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
            return '';
        }
    }

    public function MessageReceived(string $sender, string $text)
    {
        $data = [
            'sender' => $sender,
            'text'   => utf8_encode($text)
        ];
        $this->SetValue('receivedMessages', $this->GetValue('receivedMessages') + 1);

        $this->SendDataToChildren(json_encode(['DataID' => '{C78CF679-C945-463E-9F2C-10A9FAD05DD3}', 'Buffer' => $data]));
        $this->SendDebug('MessageReceived()', 'Sender: ' . $sender . ' Text: ' . $text, 0);
    }

    public function CheckConnection()
    {
        try {
            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => $this->ReadPropertyString('IPAddress') . '/cgi-bin/sms_read',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'username=' . urlencode($this->ReadPropertyString('SMSUsername')) . '&password=' . urlencode($this->ReadPropertyString('SMSPassword')),
                CURLOPT_HTTPHEADER     => [
                    'content-type: application/x-www-form-urlencoded'
                ],
            CURLOPT_SSL_VERIFYHOST => $verifyhost,
            CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error:' . "\r\n" . $err;
                $this->SendDebug('CheckConnection()', 'cURL Error:', 0);
                $this->SetStatus(202);
                exit;
            }

            if (strpos($response, 'out of range') === 0) {
                echo 'Connection Successfull';
                $this->SendDebug('CheckConnection()', 'Connection Successfull', 0);
                $this->SetStatus(102);
            } else {
                echo 'Connection failed: ' . "\r\n" . $response;
                $this->SendDebug('CheckConnection()', 'Connection failed: ' . "\r\n" . $response, 0);
                $this->SetStatus(201);
            }
        } catch (Exception $e) {
            echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
            $this->SendDebug('CheckConnection()', 'Exception: ', $e->getMessage(), 0);
            return false;
        }
    }

    private function DeleteMessage(int $id)
    {
        try {
            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => $this->ReadPropertyString('IPAddress') . '/cgi-bin/sms_delete',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'username=' . urlencode($this->ReadPropertyString('SMSUsername')) . '&password=' . urlencode($this->ReadPropertyString('SMSPassword')) . '&number=' . $id,
                CURLOPT_HTTPHEADER     => [
                    'content-type: application/x-www-form-urlencoded'
                ],
            CURLOPT_SSL_VERIFYHOST => $verifyhost,
            CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:' . $err;
            } else {
                $this->SendDebug('DeleteMessage()', 'ID: ' . $id, 0);
            }
        } catch (Exception $e) {
            echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
            return '';
        }
    }

    public function SendMessageTo(string $phoneNumber, string $text)
    {
        $phoneNumber = str_replace(' ', '', $phoneNumber);
        $phoneNumber = str_replace('-', '', $phoneNumber);

        if (strlen($phoneNumber) == 0 || strlen($text) == 0) {
            $this->SendDebug('SendMessageTo()', 'Error: PhoneNumber or text is empty' . $text, 0);
            return false;
        }

        if ($this->ReadPropertyBoolean('DisableSending') == true) {
            $this->SendDebug('SendMessageTo()', 'Sending Disabled: try to send message to ' . $phoneNumber . ' with contet: ' . $text, 0);
            return true;
        }

        try {
            if ($this->ReadPropertyBoolean('VerifyHost')) {
                $verifyhost = 2;
            } else {
                $verifyhost = 0;
            }
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL            => $this->ReadPropertyString('IPAddress') . '/cgi-bin/sms_send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING       => '',
                CURLOPT_MAXREDIRS      => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => 'username=' . urlencode($this->ReadPropertyString('SMSUsername')) . '&password=' . urlencode($this->ReadPropertyString('SMSPassword')) . '&number=' . urlencode($phoneNumber) . '&text=' . urlencode($text),
                CURLOPT_HTTPHEADER     => [
                    'content-type: application/x-www-form-urlencoded'
                ],
            CURLOPT_SSL_VERIFYHOST => $verifyhost,
            CURLOPT_SSL_VERIFYPEER => $this->ReadPropertyBoolean('VerifyPeer'),
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo 'cURL Error #:' . $err;
                $this->SetStatus(202);
            } else {
                $this->SetValue('sendMessages', $this->GetValue('sendMessages') + 1);
                if (strpos($response, 'OK') === 0) {
                    $this->SendDebug('SendMessageTo()', 'Send Message to ' . $phoneNumber . ' was successfull', 0);
                    return true;
                } else {
                    $this->SendDebug('SendMessageTo()', 'Sending failed: ' . $response . '  Parameter: ' . 'number=' . urlencode($phoneNumber) . '&text=' . urlencode($text), 0);
                    return false;
                }
            }
        } catch (Exception $e) {
            $this->SendDebug('SendMessageTo()', 'Exception abgefangen: ', $e->getMessage(), 0);
            return false;
        }
    }
}
