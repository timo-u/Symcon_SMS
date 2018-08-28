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

            $this->RegisterTimer('UpdateMessages', $this->ReadPropertyInteger('ReadMessagesIntervall') * 1000, 'RUT_GetMessages($_IPS[\'TARGET\']);');
        }

        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();

            $this->SetTimerInterval('UpdateMessages', $this->ReadPropertyInteger('ReadMessagesIntervall') * 1000);
        }

        public function ForwardData($JSONString)
        {

        // Empfangene Daten vom Device
            $data = json_decode($JSONString);

            $data = $data->Buffer;
            $sender = $data->sender;

            $text = $data->text;
            return $this->SendMessage($sender, $text);
        }

        public function SendTestMessage()
        {
            $number = $this->ReadPropertyString('TestNumber');
            $message = $this->ReadPropertyString('TestMessage');
            if (strlen($number) < 5 || strlen($message) < 3) {
                echo 'Message or Number too short';
                return;
            }

            $result = $this->SendMessage($number, $message);

            if ($result == true) {
                echo 'Message sent';
            } else {
                echo 'Message not sent';
            }
        }

        public function GetMessages()
        {
            try {
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
                        $this->DeleteMessage($index);
                    }
                }
            } catch (Exception $e) {
                echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
                return '';
            }
        }

        private function MessageReceived(string $sender, string $text)
        {
            $data = [
            'sender' => $sender,
            'text'   => $text
        ];

            //$this->SendDataToChildren($data);
            $this->SendDataToChildren(json_encode(['DataID' => '{C78CF679-C945-463E-9F2C-10A9FAD05DD3}', 'Buffer' => $data]));
            IPS_LogMessage('TeltonikaSMSGateway', 'Sender: ' . $sender . ' Text: ' . $text);
        }

        public function CheckConnection()
        {
            try {
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
            ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo 'cURL Error:' . "\r\n" . $err;
                    $this->SetStatus(202);
                    die;
                }

                if (strpos($response, 'out of range') === 0) {
                    echo 'Connection Successfull';
                    $this->SetStatus(102);
                } else {
                    echo 'Connection failed: ' . "\r\n" . $response;
                    $this->SetStatus(201);
                }
            } catch (Exception $e) {
                echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
                return false;
            }
        }

        private function DeleteMessage(int $id)
        {
            try {
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
            ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo 'cURL Error #:' . $err;
                } else {
                    IPS_LogMessage('TeltonikaSMSGateway', 'DeleteMessage(' . $id . ')');
                }
            } catch (Exception $e) {
                echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
                return '';
            }
        }

        public function SendMessage(string $phoneNumber, string $text)
        {
            $phoneNumber = str_replace(' ', '', $phoneNumber);
            $phoneNumber = str_replace('-', '', $phoneNumber);

            if ($this->ReadPropertyBoolean('DisableSending') == true) {
                IPS_LogMessage('TeltonikaSMSGateway', 'Sending Disabled: try to send message to ' . $phoneNumber . ' with contet: ' . $text);
                return true;
            }

            try {
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
            ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);

                if ($err) {
                    echo 'cURL Error #:' . $err;
                    $this->SetStatus(202);
                } else {
                    if (strpos($response, 'OK') === 0) {
                        IPS_LogMessage('TeltonikaSMSGateway', 'Send Message to ' . $phoneNumber . ' was successfull');
                        return true;
                    } else {
                        IPS_LogMessage('TeltonikaSMSGateway', $response . '  Parameter: ' . 'number=' . urlencode($phoneNumber) . '&text=' . urlencode($text));
                        return false;
                    }
                }
            } catch (Exception $e) {
                echo 'Exception abgefangen: ',  $e->getMessage(), "\n";
                return false;
            }
        }
    }
