#!/usr/bin/php -q
<?php

// Load FreePBX bootstrap environment
$restrict_mods = array('telegrammissedcall' => true);
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf'))
{
    include_once('/etc/asterisk/freepbx.conf');
}
// set_time_limit(0);
// error_reporting(0);

require_once "telegram_missed_global.php";
$telegram = new \AGI_TelegramMissed();
// sleep(3);

if ($telegram->vmstatus == "SUCCESS" && $telegram->status == "enabled")
{
    $telegram->token  = $telegram->getRequest("agi_arg_1");
    $telegram->chatid = $telegram->getRequest("agi_arg_2");

    $mess  = _("Missed call + voice message\n%%__DATETIME__%%\n%%__CID__%% -> %%__EXTTOLOCAL__%%");
    $fname = $telegram->getRequest("agi_arg_13"); //Voicemail

    if ($fname != '')
    {
        $telegram->sendDocumentTel($mess, $fname);
    }
}
elseif ($telegram->dialstatus != "ANSWER" && $telegram->status == "enabled")
{
    $telegram->token  = $telegram->getRequest("agi_arg_1");
    $telegram->chatid = $telegram->getRequest("agi_arg_2");

    $mess = _("Missed call\n%%__DATETIME__%%\n%%__CID__%% -> %%__EXTTOLOCAL__%%");
    $telegram->sendMessageTel($mess);
}
elseif ($telegram->dialstatus == "ANSWER")
{
    $fname = $telegram->getRequest('agi_uniqueid');

    $callstatus = $telegram->getRequest("agi_arg_7");
    if ($callstatus == "enabled")
    {
        //CallToken
        $telegram->token  = $telegram->getRequest("agi_arg_8");
        $telegram->chatid = $telegram->getRequest("agi_arg_9");

        $mess = _("Call\n%%__DATETIME__%%\n%%__CID__%% -> %%__EXTTOLOCAL__%%");
        $telegram->sendMessageTel($mess);
    }

    $rcstatus = $telegram->getRequest("agi_arg_10");
    if ($fname != '' && $rcstatus == "enabled")
    {
        //rctoken
        $telegram->token  = $telegram->getRequest("agi_arg_11");
        $telegram->chatid = $telegram->getRequest("agi_arg_12");

        $mess = _("Record Call\n%%__DATETIME__%%\n%%__CID__%% -> %%__EXTTOLOCAL__%%");
        $telegram->sendDocumentTel($mess, $fname);
    }
}