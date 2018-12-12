#!/usr/bin/php
<?php
error_reporting(0);
/************************
 * 
 * check_synology_freespace.php
 * 
 * Script to check the status of backups on Synology NAS
 * 
 * Existing statuses for tasks :
 * - uptodate : normal status
 * 
 ************************/
 
function print_help() {
	global $argv;
	echo $argv[0]." [-h -v] -H hostname -u username -p password -w warning -c critical [-m mode] [-i id]\n".
			"SNMP v3 check of available space on Synology NAS\n".
			"\n".
			"List of options\n".
			"    -H : hostname to be checked\n".
			"    -u : username to connect to host\n".
			"    -p : password to connect to host\n".
			"    -v : verbose. Activate debug info\n".
			"    -w : warning level, in percent or byte, depending of selected mode\n".
			"    -c : critical level, in percent or byte, depending of selected mode\n".
			"    -m : mode percent or byte. Defaut is percent\n".
			"    -i : disk id (integer). Defaut is 0\n".
			"    -h : print this help.\n";
}

$debug = 0;

 // Example from https://www.nas-forum.com/forum/topic/46256-script-web-api-synology/
// Parsing Args
if(isset($options['h'])) { print_help(); exit(3);}
if(isset($options['v'])) $debug = true;

$options = getopt("hvH:u:p:w:c:m:i:");
if($debug) print_r($options);

$MODE_PERCENT = 1;
$MODE_BYTE = 2;
$mode = 1; // 1 is percent, 2 is byte

// Check servername
if(!isset($options['H'])) {echo "Hostname not defined.\n";print_help();exit(3);} else {
	$hostname = $options['H'];
}

// Check username
if(!isset($options['u'])) {echo "Username not defined.\n";print_help();exit(3);} else $login = $options['u'];

//Check password
if(!isset($options['p'])) {echo "Password not defined.\n";print_help();exit(3);} else $pass = $options['p'];

$disk_id = 0;
//Check id
if(isset($options['i'])) $disk_id = $options['i'];


//Check password
if(!isset($options['w']) || !is_numeric($options['w'])) {echo "Warning level not defined.\n";print_help();exit(3);} else $warning = $options['w'];
if(!isset($options['c']) || !is_numeric($options['c'])) {echo "Critical level not defined.\n";print_help();exit(3);} else $critical = $options['c'];
if(isset($options['m'])) {
	switch ($options['m']) {
		case "percent":
			$mode = $MODE_PERCENT;
			if($warning > 100 || $warning < 0 || $critical > 100 || $critical < 0) {echo "Inconsistent value of warning or critical threshold\n"; exit(3);}
			break;
		case "byte":
			if($warning < 0 || $critical < 0) {echo "Inconsistent value of warning or critical threshold\n"; exit(3);}
			$mode = $MODE_BYTE;
			break;
		default:
			echo "Mode not recognized : ".$options['m']."\n";
			exit(3);
	}
} 

$status_n = 0;

$raidNameOID = ".1.3.6.1.4.1.6574.3.1.1.2.$disk_id";
$freeSizeOID = ".1.3.6.1.4.1.6574.3.1.1.4.$disk_id";
$totalSizeOID = ".1.3.6.1.4.1.6574.3.1.1.5.$disk_id";
$raidNameId = "SNMPv2-SMI::enterprises.6574.3.1.1.2.$disk_id";
$freeSizeId = "SNMPv2-SMI::enterprises.6574.3.1.1.4.$disk_id";
$totalSizeId = "SNMPv2-SMI::enterprises.6574.3.1.1.5.$disk_id";


/* 
fclose(STDOUT);
fclose(STDERR);
STDOUT = fopen('/dev/null', 'wb');
STDERR = fopen('/dev/null', 'wb');
  */
  $session = new SNMP(SNMP::VERSION_3, $hostname, $login);
  $session->setSecurity('authNoPriv', 'MD5', $pass, 'AES', 'secret007');
  

  $sysdescr = $session->get(array($raidNameOID, $freeSizeOID, $totalSizeOID));
  if($debug) print_r($sysdescr);
  
  $raidName = $sysdescr[$raidNameId];
  $freeSize = $sysdescr[$freeSizeId];
  $totalSize = $sysdescr[$totalSizeId];
  
  $tmp = explode(": ", $raidName);
  $raidName = $tmp[1];
  $tmp = explode(": ", $freeSize);
  $freeSize = $tmp[1];
  $tmp = explode(": ", $totalSize);
  $totalSize = $tmp[1];
  /*
fclose(STDOUT);
fclose(STDERR);
STDOUT = fopen('php://stdout', 'w');
STDERR = fopen('php://stderr', 'w');
  */
  
  $freepercent = intval(1000* $freeSize / $totalSize)/10;
  
  
  if($mode = $MODE_PERCENT) {
	if(	$freepercent < $critical) $status_n = 2;
	elseif(	$freepercent < $warning) $status_n = 1;
	else $status_n = 0;
	$string = "$freepercent % left";
  }
  elseif($mode = $MODE_BYTE) {
	if(	$freeSize < $critical) $status_n = 2;
	elseif(	$freeSize < $warning) $status_n = 1;
	else $status_n = 0;
	$string = "$freesize B left";
  }
  
  	$nagios_status = array (
		0 => "OK",
		1 => "WARNING",
		2 => "CRITICAL",
		3 => "UNKNOWN",
		);

	
	echo "$raidName free space: ".$nagios_status[$status_n].", $string\n";

	/*
		Nagios understands the following exit codes:
		
		0 - Service is OK.
		1 - Service has a WARNING.
		2 - Service is in a CRITICAL status.
		3 - Service status is UNKNOWN.
	*/
    exit ($status_n);
?>