<?php

require_once "phpagi.php";

class AGI_TelegramMissed
{
	const URL_API = "https://api.telegram.org/bot";

	public $Config			   = null;
	public $AGI 			   = null;
	public $AGIDir			   = null;
	public $FreePBX			   = null;
	public $TelegramMissedCall = null;

	public $token 	 = null;
	public $chatid	 = array();

	public $cid        = null;
	public $exttolocal = null;
	public $dialstatus = null;
	public $vmstatus   = null;
	public $status 	   = null;

	public function __construct() {
		$this->FreePBX = \FreePBX::create();
		if ($this->FreePBX == null)
		{
			throw new \Exception("Not given a FreePBX Object");
		}

		$this->Config			  = $this->FreePBX->Config;

		$this->loadPHPAGI();

		$this->AGI 				  = new \AGI();
		$this->TelegramMissedCall = $this->FreePBX->Telegrammissedcall;
		$this->init();
	}

	private function loadPHPAGI()
	{
		$this->AGIDir = $this->Config->get('ASTAGIDIR');
		require_once $agidir."/phpagi.php";
		return class_exists("AGI");
	}

	private function init()
	{
		$this->cid        = $this->getRequest('agi_callerid');
		$this->exttolocal = $this->getRequest('agi_arg_6');
		$this->dialstatus = $this->getRequest('agi_arg_4');
		$this->vmstatus   = $this->getRequest('agi_arg_5');
		$this->status 	  = $this->getRequest('agi_arg_3');
	}

	public function getRequest($arg, $default = null)
	{
		$data_return = $default;
		$arg 	 	 = strtolower($arg);
		$request 	 = $this->AGI->request;

		if (array_key_exists($arg, $request))
  		{
			switch ($arg)
			{
				case "agi_arg_1":
				case "agi_arg_8":
				case "agi_arg_11":
					$data_return = $request[$arg];
					$data_return = str_replace(" ", ":", $data_return);
					break;

				case "agi_arg_2":
				case "agi_arg_9":
				case "agi_arg_12":
					$data_return = $request[$arg];
					$data_return = explode("-", $data_return);
					break;

				case "agi_arg_13":
					//$path = '/var/spool/asterisk/voicemail/default/'.$exttolocal.'/INBOX/';

					$astspooldir = $this->FreePBX->Config->get("ASTSPOOLDIR");
					$path 		 = sprintf('%s/voicemail/default/%s/INBOX/', $astspooldir, $request[$arg]);
					$data_return = $this->find_last_file($path, ".wav");
					$data_return = empty($data_return) ? '' : $path.$data_return;
					break;

				case "agi_uniqueid":
					$mixmondir   = $this->FreePBX->Config->get("MIXMON_DIR");
					$astspooldir = $this->FreePBX->Config->get("ASTSPOOLDIR");
					$monitorPath = $mixmondir ? $mixmondir : sprintf("%s/monitor", $astspooldir);
					$path 		 = sprintf("%s/%s/%s/%s/", $monitorPath, date("Y"), date("m"), date("d"));
					$filename 	 = sprintf("%s.wav", $request[$arg]);
					$data_return = $this->find_last_file($path, $filename);
					$data_return = empty($data_return) ? '' : $path.$data_return;
					break;

				default:
					$data_return = $request[$arg];
			}

			// $token = $agi->request['agi_arg_1'];
			// $token = str_replace(" ", ":", $token);

			// $chatid = $agi->request['agi_arg_2'];
			// $telegramstatus = $agi->request['agi_arg_3'];
			// $dialstatus = $agi->request['agi_arg_4'];
			// $vmstatus = $agi->request['agi_arg_5'];
			// $exttolocal = $agi->request['agi_arg_6'];
			// $callstatus = $agi->request['agi_arg_7'];

			// $calltoken = $agi->request['agi_arg_8'];
			// $token = str_replace(" ", ":", $calltoken);

			// $chatid = $agi->request['agi_arg_9'];
			// $rcstatus = $agi->request['agi_arg_10'];

			// $rctoken = $agi->request['agi_arg_11'];
			// $token = str_replace(" ", ":", $rctoken);


			// $chatid = $agi->request['agi_arg_12'];
			// $voicemail = $agi->request['agi_arg_13'];
			// $cid = $agi->request['agi_callerid'];
			
			
			// $path = '/var/spool/asterisk/monitor/'.date("Y").'/'.date("m").'/'.date("d").'/';
			// $fname = find_last_file($path, $agi->request['agi_uniqueid'].'.wav');

  		}
		return $data_return;
	}

	private function find_last_file($path, $s)
	{
        $l = 0;
        $r = '';
        foreach( new DirectoryIterator($path) as $file )
		{
            $ctime = $file->getCTime();
            $fname = $file->getFileName();
            if( $ctime > $l )
			{
                $r = $fname;
                $pos = strpos($r, $s);
                
                if ($pos !== false)
				{
                    $l = $ctime;
                }
				else
				{
                    $r = '';
                }
            }
        }
        return $r;
    }


	private function parseMess($mess)
	{
		// public $chatid	 = array();
		$replace = array(
			'%%__TOKEN__%%' 	 => $this->token,
			'%%__CID__%%' 		 => $this->cid,
			'%%__EXTTOLOCAL__%%' => $this->exttolocal,
			'%%__DIALSTATUS__%%' => $this->dialstatus,
			'%%__VMSTATUS__%%' 	 => $this->vmstatus,
			'%%__STATUS__%%' 	 => $this->status,
			'%%__DATETIME__%%' 	 => date("d.m.Y H:i:s"),
			'%%__CALLSTATUS__%%' => $this->getRequest('agi_arg_7'),
			'%%__CALLTOKEN__%%'  => $this->getRequest('agi_arg_8'),
			'%%__RCSTATUS__%%' 	 => $this->getRequest('agi_arg_10'),
			'%%__RCTOKEN__%%' 	 => $this->getRequest('agi_arg_11'),
		);

		foreach ($replace as $key => $value)
		{
			if ($value === null)
			{
				$value = '';
			}
			$mess = str_replace($key, $value, $mess);
		}
		return $mess;
	}

	public function sendDocumentTel($mess, $fname)
	{
		$mess = $this->parseMess($mess);
        foreach($this->chatid as $c)
		{
			$url = sprintf("%s%s/sendDocument?chat_id=%s&caption=%s", self::URL_API, $this->token, $c, urlencode($mess));
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
			// curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Establecer un tiempo de espera de 10 segundos
            $finfo = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $fname);
            $cFile = new CURLFile($fname, $finfo);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                "document" => $cFile
            ]);
            $result = curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			if ($result === false)
			{
				fatal(sprintf("%s error: %s", __FUNCTION__, curl_error($ch)));
			}
			elseif ($responseCode !== 200)
			{
				fatal(sprintf("%s error (%s): %s", __FUNCTION__, $responseCode, curl_strerror($responseCode)));
			}
			else
			{
				dbug(sprintf("%s OK!", __FUNCTION__));
				// var_dump($result);
			}
            curl_close($ch);
            sleep(3);
        }
    }

    public function sendMessageTel($mess)
	{
        $mess = $this->parseMess($mess);
        foreach($this->chatid as $c)
		{
			$url = sprintf("%s%s/sendMessage?chat_id=%s&text=%s", self::URL_API, $this->token, $c, urlencode($mess));
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
			// curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Establecer un tiempo de espera de 10 segundos
            $result = curl_exec($ch);
			$responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
			if ($result === false)
			{
				fatal(sprintf("%s error: %s", __FUNCTION__, curl_error($ch)));
			}
			elseif ($responseCode !== 200)
			{
				fatal(sprintf("%s error (%s): %s", __FUNCTION__, $responseCode, curl_strerror($responseCode)));
			}
			else
			{
				dbug(sprintf("%s OK!", __FUNCTION__));
				// var_dump($result);
			}
            curl_close($ch);
            sleep(3);
        }
    }
}