# check_synology_freespace
Synology freespace check for Nagios

The connexion is made to the NAS using the SNMPv3 with MD5 authentification and no private password. (authNoPriv). The required username and password are those defined in the SNMP configuration.

Command usage: 
check_synology_freespace.php [-h -v] -H hostname -u username -p password -w warning -c critical [-m mode] [-i id] 
	SNMP v3 check of available space on Synology NAS 
	
	List of options 
	    -H : hostname to be checked 
	    -u : username to connect to host 
	    -p : password to connect to host 
	    -v : verbose. Activate debug info 
	    -w : warning level, in percent or byte, depending of selected mode 
	    -c : critical level, in percent or byte, depending of selected mode 
	    -m : mode percent or byte. Defaut is percent 
	    -i : disk id (integer). Defaut is 0 
	    -h : print this help. 

				
Usage example in Nagios config:  
define command {  
	command_name check_synology_freespace  
	command_line /path/to/libexec/check_synology_freespace.php -H $HOSTADDRESS$ -u $USER2$ -p $USER3$ -i $ARG1$ -m $ARG2$ -w $ARG3$ -c $ARG4$  
}

define service {  
	host	my.host  
	service_description FreeSpace  
	check_command check_synology_freespace!1!percent!15!5  
	use generic-service  
}
