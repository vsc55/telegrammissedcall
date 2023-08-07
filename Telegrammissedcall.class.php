<?php
namespace FreePBX\modules;
use BMO;
use FreePBX_Helpers;
use PDO;
class Telegrammissedcall extends FreePBX_Helpers implements BMO
{
    const ASTERISK_SECTION = 'sub-telegrammissedcall';

    public    $FreePBX  = null;
    protected $db       = null;
    protected $astman   = null;
    protected $config   = null;

    public function __construct($freepbx = null)
	{
		if ($freepbx == null) {
			throw new \Exception("Not given a FreePBX Object");
		}
		$this->FreePBX 	= $freepbx;
		$this->db       = $freepbx->Database;
        $this->astman 	= $freepbx->astman;
        $this->config 	= $freepbx->Config;
		// $this->Userman 	= $freepbx->Userman;
	}
    
	public function install() { }
    public function uninstall() { }
    public function backup() {}
	public function restore($backup) {}
   

    /**
	 * Returns bool permissions for AJAX commands
	 * https://wiki.freepbx.org/x/XoIzAQ
	 * @param string $command The ajax command
	 * @param array $setting ajax settings for this command typically untouched
	 * @return bool
	 */
	public function ajaxRequest($req, &$setting)
	{
		// ** Allow remote consultation with Postman **
		// ********************************************
		// $setting['authenticate'] = false;
		// $setting['allowremote'] = true;
		// return true;
		// ********************************************
		switch($req)
		{
			// case "":
			// 	return true;
			// 	break;

			default:
				return false;
		}
		return false;
	}


	/**
	 * Handle Ajax request
	 */
	public function ajaxHandler()
	{
		$command = $this->getReq("command", "");
		$data_return = false;

		switch ($command)
		{
			// case '':
			// 	break;

			default:
				$data_return = array("status" => false, "message" => _("Command not found!"), "command" => $command);
		}
		return $data_return;
	}



    public static function myConfigPageInits()
    {
        // We only want to hook 'users' or 'extensions' pages.
        return array("extensions", "users"); 
    }

    /**
	 * Processes form submission and pre-page actions.
	 *
	 * @param string $page Display name
	 * @return void
	 */
    public function doConfigPageInit($page)
    {
        /** getReq provided by FreePBX_Helpers see https://wiki.freepbx.org/x/0YGUAQ */
        $action	       = $this->getReq('action', null);
        $extdisplay	   = $this->getReq('extdisplay', null);
        $extension	   = $this->getReq('extension', null);
        $tech_hardware = $this->getReq('tech_hardware', null);
        
        switch($page)
        {
            case 'users':
            case 'extensions':
                global $currentcomponent;

                // On a 'new' user, 'tech_hardware' is set, and there's no extension. Hook into the page.
                if ($tech_hardware != null)
                {
                    $this->missedcall_applyhooks();
                    $currentcomponent->addprocessfunc('telegrammissedcall_configprocess', 8);
                }
                elseif ($action=="add")
                {
                    // We don't need to display anything on an 'add', but we do need to handle returned data.
                    $currentcomponent->addprocessfunc('telegrammissedcall_configprocess', 8);
                }
                elseif ($extdisplay != '')
                {
                    // We're now viewing an extension, so we need to display _and_ process.
                    $this->missedcall_applyhooks();
                    $currentcomponent->addprocessfunc('telegrammissedcall_configprocess', 8);
                }
            break;

            default:
                return true;
            break;
        }
    }

    //Dialplan hooks
    public function myDialplanHooks()
    {
        // Need fix , https://issues.freepbx.org/browse/FREEPBX-24328
        return array(100, 600);
    }

    public function doDialplanHook(&$ext, $engine, $priority)
    {
        if ($engine != "asterisk") { return; }

        $section = self::ASTERISK_SECTION;
        $exten = 's';

        if ($priority == 100)
        {
            //$exten0 = "exten";

            error_log(sprintf('telegrammissedcall - doDialplanHook - %s - triggered', $priority));
            $ext->add($section, $exten, '', new \ext_noop('CALLERID(number): ${CALLERID(number)}'));
            $ext->add($section, $exten, '', new \ext_noop('CALLERID(name): ${CALLERID(name)}'));
            $ext->add($section, $exten, '', new \ext_noop('DialStatus: ${DIALSTATUS}'));
            $ext->add($section, $exten, '', new \ext_noop('VMSTATUS: ${VMSTATUS}'));
    
            $token1             = '${DB(AMPUSER/${EXTTOCALL}/telegrammissedcall/bot1)}';
            $token2             = '${DB(AMPUSER/${EXTTOCALL}/telegrammissedcall/bot2)}';
            $token              = sprintf("%s %s", $token1, $token2);
            $chatid             = '${DB(AMPUSER/${EXTTOCALL}/telegrammissedcall/telegram)}';
            $telegramstatus     = '${DB(AMPUSER/${EXTTOCALL}/telegrammissedcall/status)}';
            $telegramsvoicemail = '${DB(AMPUSER/${EXTTOCALL}/telegrammissedcall/voicemail)}';
            
            $dialstatus         = '${DIALSTATUS}';
            $vmstatus           = '${VMSTATUS}';
            $exttolocal         = '${EXTTOCALL}';
            
            $callstatus         = '${DB(AMPUSER/${EXTTOCALL}/telegramcall/status_call)}';
            $calltoken1         = '${DB(AMPUSER/${EXTTOCALL}/telegramcall/bot1_call)}';
            $calltoken2         = '${DB(AMPUSER/${EXTTOCALL}/telegramcall/bot2_call)}';
            $calltoken          = sprintf("%s %s", $calltoken1, $calltoken2);
            $callchatid         = '${DB(AMPUSER/${EXTTOCALL}/telegramcall/telegram_call)}';
            
            $rcstatus           = '${DB(AMPUSER/${EXTTOCALL}/telegramrecordcall/status_rc)}';
            $rctoken1           = '${DB(AMPUSER/${EXTTOCALL}/telegramrecordcall/bot1_rc)}';
            $rctoken2           = '${DB(AMPUSER/${EXTTOCALL}/telegramrecordcall/bot2_rc)}';
            $rctoken            = sprintf("%s %s", $rctoken1, $rctoken2);
            $rchatid            = '${DB(AMPUSER/${EXTTOCALL}/telegramrecordcall/telegram_rc)}';
            
            $agi_cmd = sprintf('telegram_missed_call.php, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s', $token, $chatid, $telegramstatus, $dialstatus, $vmstatus, $exttolocal, $callstatus, $calltoken, $callchatid, $rcstatus, $rctoken, $rchatid, $telegramsvoicemail);
            $ext->add($section, $exten, '', new \ext_AGI($agi_cmd));
            // $ext->add($section, $exten, '', new \ext_AGI('telegram_missed_call.php,'.$token.','.$chatid.','.$telegramstatus.','.$dialstatus.','.$vmstatus.','.$exttolocal.','.$callstatus.','.$calltoken.','.$callchatid.','.$rcstatus.','.$rctoken.','.$rchatid.','.$telegramsvoicemail));     
        }
        elseif ($priority == 600)
        {
            $newsplice=0;
            error_log(sprintf('telegrammissedcall - doDialplanHook - %s - triggered', $priority));
            
            if($newsplice){ # Method fpr splicing using modified splice code yet not implemented in 2.10.0.2
                $ext->splice('macro-hangupcall', $exten, 'theend', new \ext_gosub(1, $exten, $section), 'theend', false, true);
            }
            else
            { # Custom method to splice in correct code prior to hangup
                
                $padextfix = $this->missedcall_padextfix($exten);
                // hook all extens
                $spliceext=array(
                    'basetag' => 'n',
                    'tag'     => '',
                    'addpri'  => '',
                    'cmd'     => new \ext_execif('$["${ORIGEXTTOCALL}"==""]','Set','__ORIGEXTTOCALL=${ARG2}')
                );
                array_splice($ext->_exts['macro-exten-vm'][$padextfix], 2, 0, array($spliceext));
            
                // hook on hangup
                $spliceext=array(
                    'basetag' => 'n',
                    'tag'     => 'theend',
                    'addpri'  => '',
                    'cmd'     => new \ext_gosub(1, $exten, $section)
                );
                foreach($ext->_exts['macro-hangupcall'][$padextfix] as $_ext_k=>&$_ext_v)
                {
                    if($_ext_v['tag']!='theend') { continue; }
                    $_ext_v['tag']='';
                    array_splice($ext->_exts['macro-hangupcall'][$padextfix], $_ext_k, 0, array($spliceext));
                    break;
                }
            }
        }

       
    }


    
    /* fix to pad exten if framework ver is >=2.10 */
    public function missedcall_padextfix($ext)
    {
        global $version;
        if(version_compare(get_framework_version(), "2.10.1.4", ">="))
        {
            $ext = sprintf(' %s ', $ext);
        }
        return $ext;
    }


    // public function missedcall_hookGet_config($engine)
    // {
    //     global $ext;
    //     global $version;

    //     if ($engine != "asterisk") { return; }

    //     $section = self::ASTERISK_SECTION;
    //     $exten = 's';
    //     $newsplice=0;

    //     error_log('telegrammissedcall_hookGet_config - triggered');
        
    //     if($newsplice){ # Method fpr splicing using modified splice code yet not implemented in 2.10.0.2
    //         $ext->splice('macro-hangupcall', $exten, 'theend', new \ext_gosub(1, $exten, $section), 'theend', false, true);
    //     }
    //     else
    //     { # Custom method to splice in correct code prior to hangup
            
    //         $padextfix = $this->missedcall_padextfix($exten);
    //         // hook all extens
    //         $spliceext=array(
    //             'basetag' => 'n',
    //             'tag'     => '',
    //             'addpri'  => '',
    //             'cmd'     => new \ext_execif('$["${ORIGEXTTOCALL}"==""]','Set','__ORIGEXTTOCALL=${ARG2}')
    //         );
    //         array_splice($ext->_exts['macro-exten-vm'][$padextfix], 2, 0, array($spliceext));
        
    //         // hook on hangup
    //         $spliceext=array(
    //             'basetag' => 'n',
    //             'tag'     => 'theend',
    //             'addpri'  => '',
    //             'cmd'     => new \ext_gosub(1, $exten, $section)
    //         );
    //         foreach($ext->_exts['macro-hangupcall'][$padextfix] as $_ext_k=>&$_ext_v)
    //         {
    //             if($_ext_v['tag']!='theend') { continue; }
    //             $_ext_v['tag']='';
    //             array_splice($ext->_exts['macro-hangupcall'][$padextfix], $_ext_k, 0, array($spliceext));
    //             break;
    //         }
    //     }
    // }

    public function missedcall_applyhooks()
    {
        global $currentcomponent;
    
        $currentcomponent->addoptlistitem('telegrammissedcall_status', 'disabled', _('Disabled'));
        $currentcomponent->addoptlistitem('telegrammissedcall_status', 'enabled', _('Enabled'));
        $currentcomponent->setoptlistopts('telegrammissedcall_status', 'sort', false);
    
        $currentcomponent->addguifunc('telegrammissedcall_configpageload');
    }

    public function missedcall_configpageload()
    {
        global $amp_conf;
        global $currentcomponent;
        
        // Init vars from $_REQUEST[]
        $action     = isset($_REQUEST['action'])    ? $_REQUEST['action']     : null;
        $extdisplay = isset($_REQUEST['extdisplay'])? $_REQUEST['extdisplay'] : null;
        
        $mcn0                        = $this->call_getall($extdisplay);
        $section0                    = _('Call Notifications');
        $telegramcall_label          = _("Notifications");
        $telegramcall_telegram_label = _("Telergram ID");
        $telegramcall_bot_label      = _("Telegram Bot token");
        $telegramcall_tt             = _("Enable notification of calls");
        $telegramcall_pt             = _("Here you can specify the telegram user ID (personal) or the general chat ID. You can specify multiple separated dashes (111-222-333).");
        
        $currentcomponent->addguielem($section0, new \gui_selectbox('telegramcall_status', $currentcomponent->getoptlist('telegrammissedcall_status'), $mcn0['telegramcall_status'], $telegramcall_label, $telegramcall_tt, '', false));
        $currentcomponent->addguielem($section0, new \gui_textbox('telegramcall_bot', $mcn0['telegramcall_bot'],$telegramcall_bot_label, '', '' , false));
        $currentcomponent->addguielem($section0, new \gui_textbox('telegramcall_telegram', $mcn0['telegramcall_telegram'],$telegramcall_telegram_label, $telegramcall_pt, '' , false));
        

        $mcn = $this->missedcall_getall($extdisplay);
        $section                            = _('Missed Call Notifications');
        $telegrammissedcall_label           = _("Notifications");
        $telegrammissedcall_telegram_label  = _("Telergram ID");
        $telegrammissedcall_bot_label       = _("Telegram Bot token");
        $telegrammissedcall_voicemail_label = _("VoiceMail Number Extension");
        $telegrammissedcall_tt              = _("Enable notification of missed calls");
        $telegrammissedcall_pt              = _("Here you can specify the telegram user ID (personal) or the general chat ID. You can specify multiple separated dashes (111-222-333).");
        
        $currentcomponent->addguielem($section, new \gui_selectbox('telegrammissedcall_status', $currentcomponent->getoptlist('telegrammissedcall_status'), $mcn['telegrammissedcall_status'], $telegrammissedcall_label, $telegrammissedcall_tt, '', false));
        $currentcomponent->addguielem($section, new \gui_textbox('telegrammissedcall_bot', $mcn['telegrammissedcall_bot'],$telegrammissedcall_bot_label, '', '' , false));
        $currentcomponent->addguielem($section, new \gui_textbox('telegrammissedcall_telegram', $mcn['telegrammissedcall_telegram'],$telegrammissedcall_telegram_label, $telegrammissedcall_pt, '' , false));
        $currentcomponent->addguielem($section, new \gui_textbox('telegrammissedcall_voicemail', $mcn['telegrammissedcall_voicemail'],$telegrammissedcall_voicemail_label, '', '' , false));

        
        $mcn2 = $this->recordcall_getall($extdisplay);
        $section2                          = _('Call recording notifications');
        $telegramrecordcall_label          = _("Notifications");
        $telegramrecordcall_telegram_label = _("Telergram ID");
        $telegramrecordcall_bot_label      = _("Telegram Bot token");
        $telegramrecordcall_tt             = _("Enable notification of record calls");
        $telegramrecordcall_pt             = _("Here you can specify the telegram user ID (personal) or the general chat ID. You can specify multiple separated dashes (111-222-333).");
        
        $currentcomponent->addguielem($section2, new \gui_selectbox('telegramrecordcall_status', $currentcomponent->getoptlist('telegrammissedcall_status'), $mcn2['telegramrecordcall_status'], $telegramrecordcall_label, $telegramrecordcall_tt, '', false));
        $currentcomponent->addguielem($section2, new \gui_textbox('telegramrecordcall_bot', $mcn2['telegramrecordcall_bot'],$telegramrecordcall_bot_label, '', '' , false));
        $currentcomponent->addguielem($section2, new \gui_textbox('telegramrecordcall_telegram', $mcn2['telegramrecordcall_telegram'],$telegramrecordcall_telegram_label, $telegramrecordcall_pt, '' , false));
    }

    public function missedcall_configprocess()
    {
        global $amp_conf;
        
        $action = isset($_REQUEST['action'])    ? $_REQUEST['action']    :null;
        $ext    = isset($_REQUEST['extdisplay'])? $_REQUEST['extdisplay']:null;
        $extn   = isset($_REQUEST['extension']) ? $_REQUEST['extension'] :null;
        
        $mcn0=array();
        $mcn0['status_call']   = isset($_REQUEST['telegramcall_status'])   ? $_REQUEST['telegramcall_status']               : 'disabled';
        $mcn0['telegram_call'] = isset($_REQUEST['telegramcall_telegram']) ? $_REQUEST['telegramcall_telegram']             : 'enabled';
        $mcn0['bot_call']      = isset($_REQUEST['telegramcall_bot'])      ? $_REQUEST['telegramcall_bot']                  : 'enabled';
        $mcn0['bot1_call']     = isset($_REQUEST['telegramcall_bot'])      ? explode(":", $_REQUEST['telegramcall_bot'])[0] : 'enabled';
        $mcn0['bot2_call']     = isset($_REQUEST['telegramcall_bot'])      ? explode(":", $_REQUEST['telegramcall_bot'])[1] : 'enabled';
        
        $mcn=array();
        $mcn['status']    = isset($_REQUEST['telegrammissedcall_status'])    ? $_REQUEST['telegrammissedcall_status']               : 'disabled';
        $mcn['telegram']  = isset($_REQUEST['telegrammissedcall_telegram'])  ? $_REQUEST['telegrammissedcall_telegram']             : 'enabled';
        $mcn['bot']       = isset($_REQUEST['telegrammissedcall_bot'])       ? $_REQUEST['telegrammissedcall_bot']                  : 'enabled';
        $mcn['bot1']      = isset($_REQUEST['telegrammissedcall_bot'])       ? explode(":", $_REQUEST['telegrammissedcall_bot'])[0] : 'enabled';
        $mcn['bot2']      = isset($_REQUEST['telegrammissedcall_bot'])       ? explode(":", $_REQUEST['telegrammissedcall_bot'])[1] : 'enabled';
        $mcn['voicemail'] = isset($_REQUEST['telegrammissedcall_voicemail']) ? $_REQUEST['telegrammissedcall_voicemail']            : 'enabled';
        
        $mcn2=array();
        $mcn2['status_rc']   = isset($_REQUEST['telegramrecordcall_status'])   ? $_REQUEST['telegramrecordcall_status']               : 'disabled';
        $mcn2['telegram_rc'] = isset($_REQUEST['telegramrecordcall_telegram']) ? $_REQUEST['telegramrecordcall_telegram']             : 'enabled';
        $mcn2['bot_rc']      = isset($_REQUEST['telegramrecordcall_bot'])      ? $_REQUEST['telegramrecordcall_bot']                  : 'enabled';
        $mcn2['bot1_rc']     = isset($_REQUEST['telegramrecordcall_bot'])      ? explode(":", $_REQUEST['telegramrecordcall_bot'])[0] : 'enabled';
        $mcn2['bot2_rc']     = isset($_REQUEST['telegramrecordcall_bot'])      ? explode(":", $_REQUEST['telegramrecordcall_bot'])[1] : 'enabled';
        
        if ($ext==='')
        {
            $extdisplay = $extn;
        }
        else
        {
            $extdisplay = $ext;
        }
        
        if ($action == "add" || $action == "edit" || (isset($mcn0['callnotify']) && $mcn0['callnotify']=="false"))
        {
            if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true)
            {
                $this->call_update($extdisplay, $mcn0);
            }
        }
        elseif ($action == "del")
        {
            $this->call_del($extdisplay);
        }
        
        if ($action == "add" || $action == "edit" || (isset($mcn['misedcallnotify']) && $mcn['misedcallnotify']=="false"))
        {
            if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true)
            {
                $this->missedcall_update($extdisplay, $mcn);
            }
        }
        elseif ($action == "del")
        {
            $this->missedcall_del($extdisplay);
        }
        
        if ($action == "add" || $action == "edit" || (isset($mcn2['recordcallnotify']) && $mcn2['recordcallnotify']=="false"))
        {
            if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true)
            {
                $this->recordcall_update($extdisplay, $mcn2);
            }
        }
        elseif ($action == "del")
        {
            $this->recordcall_del($extdisplay);
        }
    }



    
    public function call_getall($ext, $base='AMPUSER')
    {
        global $amp_conf;
        $mcn=array();
        
        if ($this->astman)
        {
            $telegramcall_status = $this->call_get($ext,"status_call", $base);
            $mcn['telegramcall_status'] = $telegramcall_status ? $telegramcall_status : 'disabled';

            $telegramcall_telegram = $this->call_get($ext,"telegram_call", $base);
            $mcn['telegramcall_telegram'] = $telegramcall_telegram ?  $telegramcall_telegram : '';
            
            $telegramcall_bot = $this->call_get($ext,"bot_call", $base);
            $mcn['telegramcall_bot'] = $telegramcall_bot ?  $telegramcall_bot : '';
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
        return $mcn;
    }
    
    public function missedcall_getall($ext, $base='AMPUSER')
    {
        global $amp_conf;
        $mcn=array();
        
        if ($this->astman)
        {
            $telegrammissedcall_status = $this->missedcall_get($ext,"status", $base);
            $mcn['telegrammissedcall_status'] = $telegrammissedcall_status ? $telegrammissedcall_status : 'disabled';

            $telegrammissedcall_telegram = $this->missedcall_get($ext,"telegram", $base);
            $mcn['telegrammissedcall_telegram'] = $telegrammissedcall_telegram ?  $telegrammissedcall_telegram : '';

            $telegrammissedcall_bot = $this->missedcall_get($ext,"bot", $base);
            $mcn['telegrammissedcall_bot'] = $telegrammissedcall_bot ?  $telegrammissedcall_bot : '';

            $telegrammissedcall_voicemail = $this->missedcall_get($ext,"voicemail", $base);
            $mcn['telegrammissedcall_voicemail'] = $telegrammissedcall_voicemail ?  $telegrammissedcall_voicemail : '';    
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
        return $mcn;
    }

    public function recordcall_getall($ext, $base='AMPUSER')
    {
        global $amp_conf;
        $mcn=array();

        if ($this->astman)
        {
            $telegramrecordcall_status = $this->recordcall_get($ext,"status_rc", $base);
            $mcn['telegramrecordcall_status'] = $telegramrecordcall_status ? $telegramrecordcall_status : 'disabled';

            $telegramrecordcall_telegram = $this->recordcall_get($ext,"telegram_rc", $base);
            $mcn['telegramrecordcall_telegram'] = $telegramrecordcall_telegram ?  $telegramrecordcall_telegram : '';

            $telegramrecordcall_bot = $this->recordcall_get($ext,"bot_rc", $base);
            $mcn['telegramrecordcall_bot'] = $telegramrecordcall_bot ?  $telegramrecordcall_bot : '';
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
        return $mcn;
    }




    public function call_get($ext, $key, $base='AMPUSER', $sub='telegramcall')
    {
        global $amp_conf;
        
        if ($this->astman)
        {
            if(!empty($sub) && $sub!=false) { $key=$sub.'/'.$key; }
            return $this->astman->database_get($base,$ext.'/'.$key);
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    
    public function missedcall_get($ext, $key, $base='AMPUSER', $sub='telegrammissedcall')
    {
        global $amp_conf;
        if ($this->astman)
        {
            if(!empty($sub) && $sub!=false) { $key=$sub.'/'.$key; }
            return $this->astman->database_get($base,$ext.'/'.$key);
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    
    public function recordcall_get($ext, $key, $base='AMPUSER', $sub='telegramrecordcall')
    {
        global $amp_conf;
        
        if ($this->astman)
        {
            if(!empty($sub) && $sub!=false)
            {
                $key=$sub.'/'.$key;
            }
            return $this->astman->database_get($base,$ext.'/'.$key);
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    
      
      
      
    public function call_update($ext, $options, $base='AMPUSER', $sub='telegramcall')
    {
        global $amp_conf;
        if ($this->astman)
        {
            foreach ($options as $key => $value)
            {
                if(!empty($sub) && $sub!=false)
                {
                    $key=$sub.'/'.$key;
                }
                $this->astman->database_put($base,$ext."/$key",$value);
            }
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    
    public function missedcall_update($ext, $options, $base='AMPUSER', $sub='telegrammissedcall')
    {
        global $amp_conf;
        if ($this->astman)
        {
            foreach ($options as $key => $value)
            {
                if(!empty($sub) && $sub!=false)
                { 
                    $key=$sub.'/'.$key;
                }
                $this->astman->database_put($base,$ext."/$key",$value);
            }
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    
    public function recordcall_update($ext, $options, $base='AMPUSER', $sub='telegramrecordcall')
    {
        global $amp_conf;
        if ($this->astman)
        {
            foreach ($options as $key => $value)
            {
                if(!empty($sub) && $sub!=false)
                {
                    $key = sprintf('%s/%s', $sub, $key);
                }
                $this->astman->database_put($base,$ext."/$key",$value);
            }
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    



    public function call_del($ext, $base='AMPUSER', $sub='telegramcall')
    {
        global $amp_conf;
        // Clean up the tree when the user is deleted
        if ($this->astman)
        {
            $this->astman->database_deltree("$base/$ext/$sub");
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
    
    public function missedcall_del($ext, $base='AMPUSER', $sub='telegrammissedcall')
    {
        global $amp_conf;
        // Clean up the tree when the user is deleted
        if ($this->astman)
        {
            $this->astman->database_deltree("$base/$ext/$sub");
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }

    public function recordcall_del($ext, $base='AMPUSER', $sub='telegramrecordcall')
    {
        global $amp_conf;
        // Clean up the tree when the user is deleted
        if ($this->astman)
        {
            $this->astman->database_deltree("$base/$ext/$sub");
        }
        else
        {
            fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
        }
    }
}