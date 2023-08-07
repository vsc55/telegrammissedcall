<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }


/** Commanded function since it overlaps with the new Hook (doConfigPageInit) */
// function telegrammissedcall_configpageinit($pagename)
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->doConfigPageInit($pagename);
// }

/** Commanded function since it overlaps with the new Hook (doDialplanHook) */
// function telegrammissedcall_get_config($engine)
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->missedcall_get_config($engine);
// }



// function telegrammissedcall_hookGet_config($engine)
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->missedcall_hookGet_config($engine);	
// }


/* fix to pad exten if framework ver is >=2.10 */
// function telegrammissedcall_padextfix($ext)
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->missedcall_padextfix($ext);
// }

function telegrammissedcall_applyhooks()
{
	\FreePBX::Modules()->deprecatedFunction();
	\FreePBX::Telegrammissedcall()->missedcall_applyhooks();
}
function telegrammissedcall_configpageload()
{
	\FreePBX::Modules()->deprecatedFunction();
	\FreePBX::Telegrammissedcall()->missedcall_configpageload();
}
function telegrammissedcall_configprocess()
{
	\FreePBX::Modules()->deprecatedFunction();
	\FreePBX::Telegrammissedcall()->missedcall_configprocess();
}



// function telegramcall_getall($ext, $base='AMPUSER')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->call_getall($ext, $base);
// }
// function telegrammissedcall_getall($ext, $base='AMPUSER')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->missedcall_getall($ext, $base);
// }
// function telegramrecordcall_getall($ext, $base='AMPUSER')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->recordcall_getall($ext, $base);
// }



// function telegramcall_get($ext, $key, $base='AMPUSER', $sub='telegramcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->call_get($ext, $key, $base, $sub);
// }
// function telegrammissedcall_get($ext, $key, $base='AMPUSER', $sub='telegrammissedcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->missedcall_get($ext, $key, $base, $sub);
// }
// function telegramrecordcall_get($ext, $key, $base='AMPUSER', $sub='telegramrecordcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	return \FreePBX::Telegrammissedcall()->recordcall_get($ext, $key, $base, $sub);
// }



// function telegramcall_update($ext, $options, $base='AMPUSER', $sub='telegramcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->call_update($ext, $options, $base, $sub);
// }
// function telegrammissedcall_update($ext, $options, $base='AMPUSER', $sub='telegrammissedcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->missedcall_update($ext, $options, $base, $sub);
// }
// function telegramrecordcall_update($ext, $options, $base='AMPUSER', $sub='telegramrecordcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->recordcall_update($ext, $options, $base, $sub);
// }



// function telegramcall_del($ext, $base='AMPUSER', $sub='telegramcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->call_del($ext, $base, $sub);
// }
// function telegrammissedcall_del($ext, $base='AMPUSER', $sub='telegrammissedcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->missedcall_del($ext, $base, $sub);
// }
// function telegramrecordcall_del($ext, $base='AMPUSER', $sub='telegramrecordcall')
// {
// 	\FreePBX::Modules()->deprecatedFunction();
// 	\FreePBX::Telegrammissedcall()->recordcall_del($ext, $base, $sub);
// }