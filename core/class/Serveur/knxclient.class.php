<?php
class KNXnet{	
	const KNXIPv10 = "10";
	const SEARCH_REQUEST = "0201";
	const SEARCH_RESPONSE = "0202";
	const DESCRIPTION_REQUEST = "0203";
	const DESCRIPTION_RESPONSE = "0204";
	const CONNECT_REQUEST = "0205";
	const CONNECT_RESPONSE = "0206";
	const CONNECTIONSTATE_REQUEST = "0207";
	const CONNECTIONSTATE_RESPONSE = "0208";
	const DISCONNECT_REQUEST = "0209";
	const DISCONNECT_RESPONSE = "020A";

	const TUNNELLING_REQUEST = "0420";
	const TUNNELLING_ACK = "0421";

	const ROUTING_INDICATION = "0530";
	const ROUTING_LOST_MESSAGE = "0531";

	const IPV4_UDP = "01";
	const IPV4_TCP = "02";

	const DEVICE_MGMT_CONNECTION = "03";
	const TUNNEL_CONNECTION = "04";
	const REMLOG_CONNECTION = "06";
	const REMCONF_CONNECTION = "07";
	const OBJSVR_CONNECTION = "08";

	//CRI
	const TUNNEL_LINKLAYER = "02";  
	const TUNNEL_RAW = "04";	
	const TUNNEL_BUSMONITOR = "80";	
	const TUNNELLING_LAYER = "29";

	//Message Code pour cEMI 
	// voir 03_06_03 ENI IMI.pdf
	const L_Busmon_ind = "2B";
	const L_Data_req = "11";				// Mode Normal : emission à destination des device KNX
	const L_Data_con = "2E";				// Mode Normal : réponses des device KNX
	const L_Data_ind = "29";				// Mode Normal : utilisé par le serveur cEMI pour indiquer le resultat d'une transmission
	const L_Raw_req = "10";				// Mode Raw : emission à destination des device KNX
	const L_Raw_ind = "2D";				// Mode Raw : réponses des device KNX
	const L_Raw_con = "2F";				// Mode Raw : utilisé par le serveur cEMI pour indiquer le resultat d'une transmission
	const L_Poll_Data_req = "13";
	const L_Poll_Data_con  = "25";
									// Utilisés pour la config de l'interface KNX :
	const M_PropRead_req = "FC";
	const M_PropRead_con = "FB";
	const M_PropWrite_req = "F6";
	const M_PropWrite_con = "F5";
	const M_PropInfo_ind = "F7";
	const M_FuncPropCommand_req = "F8";
	const M_FuncPropStateRead_req = "F9";
	const M_FuncPropCommand_con = "FA";
	const M_FuncPropStateread_con = "FA";
	const M_Reset_req = "F1";				//The M_Reset.req message shall be used to restart the cEMI Server device
	const M_Reset_ind = "F0";				//The M_Reset.ind message shall be used to indicate to the cEMI client a reset or start-up of the cEMI Server device.
	//protected $ServerIp=$_SERVER['SERVER_ADDR'];
	protected $ServerIp='192.168.0.100';//config::byKey('internalAddr');
	protected $BroadcastIp="224.0.23.12";
	protected $BroadcastPort=3671;
	protected $ConnexionMode="Tunneling";
	protected $ServerPort=1024;
	protected $Binded=true;
	protected $IndividualAddress=0x0001;
	public function __construct ($KnxBroadcastHost="224.0.23.12",$KnxBroadcastPort=3671,$ConnexionMode="Tunneling")	{
	//	$this->ServerIp=$_SERVER['SERVER_ADDR'];
		$this->ServerIp=config::byKey('internalAddr');
		$this->BroadcastIp=$KnxBroadcastHost;
		$this->BroadcastPort=intval($KnxBroadcastPort);
		$this->ConnexionMode=$ConnexionMode;
		$this->ServerPort=1024;
		$this->Binded=true;
		$this->IndividualAddress=0x0001;
	}
	/******************************************************************************************************************************************************
	* 
	*                                                    Recherche des interfaces KNX
	*
	*******************************************************************************************************************************************************/
	public function ScruteBroadcast(){	
		if (!$this->BroadcastSocket = self::ConnectBroadcast())
			{
			log::add('KnxServer', 'debug', $this->lastError);
			return false;
			}
		
		while(true) 
			{ 
			$buf = ' ';
			socket_recvfrom($this->BroadcastSocket, $buf, 2048, 0, $empty_from, $this->BroadcastPort);
			$this->ReadFrame= unpack("C*", $buf);
			self::DecriptFrame();
			}
		socket_close($this->BroadcastSocket);
		return true;
		}
	public function SearchRequestBroadcast(){	
		if (!$this->BroadcastSocket = self::ConnectBroadcast())
			{
			log::add('KnxServer', 'debug', $this->lastError);
			return false;
			}
		log::add('KnxServer', 'debug', 'Envoi de la trame search request');
		self::SearchRequest();
		while(!isset($this->KnxIpGateway)) 
			{ 
			$buf = '';
			socket_recvfrom($this->BroadcastSocket, $buf, 2048, 0, $empty_from, $this->BroadcastPort);
			$this->ReadFrame= unpack("C*", $buf);
			self::DecriptFrame();
			}
		socket_close($this->BroadcastSocket);
		return true;
		}
	private function ConnectBroadcast(){
		set_time_limit(0); 
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if (!$socket) {
			$this->lastError = "socket_create() failed: reason: " . socket_strerror(socket_last_error($socket));
			return false;
		}
		
		while(!socket_bind($socket, '0.0.0.0', $this->ServerPort)) 
			$this->ServerPort++;
		if (!socket_set_option($socket, IPPROTO_IP, MCAST_JOIN_GROUP, array("group"=>$this->BroadcastIp,"interface"=>0))) 
			{
			$this->lastError = "socket_set_option() failed: reason: " . socket_strerror(socket_last_error($socket));
			return false;
			}
		return $socket;
	}	
	private function SearchRequest(){
		// SEARCH_REQUEST
		//KNXnet/IP frame header
		$msg = "06".						// 06 HEADER_SIZE
		self::KNXIPv10 .					// 10 KNX/IP v1.0
		self::SEARCH_REQUEST .			// servicetypeidentifier
		"000E".						// totallength,14octets
		
		//Host Protocol Address Information (HPAI)		
		"08".						// structure length
		self::IPV4_UDP.						//host protocol code, e.g. 01h, for UDP over IPv4
		bin2hex(inet_pton($this->ServerIp)).					//192.168.0.49
		sprintf('%04x', $this->ServerPort);						//portnumberofcontrolendpoint
		
		$hex_msg = hex2bin($msg);
		$dataBrute='0x';
		foreach (unpack("C*", $hex_msg) as $Byte)
			$dataBrute.=sprintf('%02x',$Byte).' ';
		log::add('KnxServer', 'debug', 'Data emise: ' . $dataBrute);
		if (!$len = socket_sendto($this->BroadcastSocket, $hex_msg, strlen($hex_msg), 0, $this->BroadcastIp, $this->BroadcastPort)) 
			{
			$this->lastError = "socket_sendto() failed: reason: " . socket_strerror(socket_last_error($this->BroadcastSocket));
			return false;
			}
	}
	/******************************************************************************************************************************************************
	* 
	*                                                    Ouverture d'une connexion
	*
	*******************************************************************************************************************************************************/
	public function Open_GroupSocket(){	
		if(isset($this->KnxIpGateway)) 	
			{
			if (!$this->socket=self::ConnectGateway())
				return false;
			if ($this->ConnexionMode == "Tunneling")
				{
				log::add('KnxServer', 'debug', 'Connexion en mode Tunneling');
				if (!self::ConnectRequest()) 
					return false;
				/*if (!self::ConnectStatRequest()) 
					return false;*/
				}
			else
				log::add('KnxServer', 'debug', 'Connexion en mode Routing');
			}
		else
			{
			$this->lastError = "KnxIpGateway n'existe pas";
			return false;
			}
		return true;
		}
	public function OpenT_Group(){		
		if(isset($this->KnxIpGateway)) 	
			{		
			if (!$this->socket=self::ConnectGateway()) 
				return false;
			/*if ($this->ConnexionMode == "Tunneling")
				{
				log::add('KnxServer', 'debug', 'Connexion en mode Tunneling');
				if (!self::ConnectRequest()) 
					return false;
				if (!self::ConnectStatRequest()) 
					return false;
				}
			else
				log::add('KnxServer', 'debug', 'Connexion en mode Routing');*/
			}
		else
			{
			$this->lastError = "KnxIpGateway n'existe pas";
			return false;
			}
		return true;
		}	
	/******************************************************************************************************************************************************
	* 
	*                                                    Action sur le bus
	*
	*******************************************************************************************************************************************************/
	public function SendAPDU($dest,$data){	
		log::add('KnxServer', 'debug', 'Envoi d\'une trame');
		$SrcAdr= sprintf('%04x',$this->IndividualAddress);
		$DstAdr= sprintf('%04x',self::Gad2Hex($dest));
		if(!is_array($data))
			{
			log::add('KnxServer', 'debug', 'Objet inférieur à 6 bits');
			$Sizedata=sprintf('%02x', 0x01);
			$ACPI ='00';
			$data =0x80 | ($data & 0x3f);
			$hexdata=sprintf('%02x', $data);
			}
			else
			{
			log::add('KnxServer', 'debug', 'Objet supérieur à 6 bits');
			$Sizedata=sprintf('%02x', count($data)+1 & 0xff);
			$ACPI = '0080';
			$hexdata='';
			for ($i =0; $i < count ($data); $i++)
				$hexdata.= sprintf('%02x', $data[$i]);	
			}
		//		Ctrl1	Ctrl2	SrcAdr   	DstAdr       Sizedata	TCPI/ACPI	Data
		$msg = 	"BC" . 	"90". 	$SrcAdr.	$DstAdr . 	$Sizedata  .  $ACPI  .  $hexdata;
		
		if ($this->ConnexionMode == "Tunneling"){
			if (!self::TunnelingSend($msg)) 
				return false;
		}
		else
		{
			if(!self::RoutingSend($msg))
				return false;
		}
		return true;
		}
	public function SendTP($data){	
		log::add('KnxServer', 'debug', 'Envoi d\'une trame');
		$msg=self::TP2cEMI($data);
		if ($this->ConnexionMode == "Tunneling"){
			if (!self::TunnelingSend($msg)) 
				return false;
		}
		else
		{
			if(!self::RoutingSend($msg))
				return false;
		}
		return true;
		}
	public function ReadAPDU($dest){	
		$SrcAdr= sprintf('%04x',$this->IndividualAddressGateWay);
		$DstAdr= sprintf('%04x',$dest);
		$Sizedata=sprintf('%02x', 0x01);
		$ACPI ='00';
		$hexdata='00';
		//		Ctrl1	Ctrl2	SrcAdr   	DstAdr       Sizedata	TCPI/ACPI	Data
		$msg = 	"BC" . 	"90". $SrcAdr.		$DstAdr . 	$Sizedata  .  $ACPI  .  $hexdata;
		
		if ($this->ConnexionMode == "Tunneling"){
			if (!self::TunnelingSend($msg)) 
				return false;
		} else {
			if(!self::RoutingSend($msg))
				return false;
		}
		if (!$len=self::Receive()) 
			return false;
		return self::getData();
	}
	public function getGroup_Src() {	
		if (!$len=self::Receive()) 
			return false;
		return $len;
	}
	public function RemoteReset(){	
		//REMOTE_RESET_REQUEST
		$msg=	'06'. 				//header size
				self::KNXIPv10 .	//protocol version
				'0743'.				//service type identifier 0743h
				'00'.				//reserved
				'0A'.				//total length, 10 octets
				'02'.				//structure length of SELECTOR
				'01'.				//Programming Mode Selector
				'01'.				//restart
				'00';				//reserved

		if (!self::Send($msg)) 
			return false;
		if (!$len=self::Receive()) 
			return false;
		}	
	/******************************************************************************************************************************************************
	* 
	*                                                    Creation d'une trame de type Routing
	*
	*******************************************************************************************************************************************************/
	private function RoutingSend($msg){
		$msg_size_bytes = strlen ($msg) / 2;
		$msg_size_bytes += 8;				//totallength,L+08hoctets 
		$msg_size_hexa = sprintf('%04x', $msg_size_bytes);

		$header = "06".						// 06 HEADER_SIZE
		self::KNXIPv10 .	
		self::ROUTING_INDICATION .
		$msg_size_hexa.						//totallength,L+08hoctets 
		self::L_Data_ind.				//cEMI Message Code ( MC )    11h=L_Data.req    29h=L_Data.ind
		"00";						//Additional Information n Length   00=No additional information
			
		$msg = $header . $msg;	
		return self::Send($msg);
	}
	/******************************************************************************************************************************************************
	* 
	*                                                    Gestion du mode Tunneling
	*
	*******************************************************************************************************************************************************/
	private function ConnectRequest()	{	
		// CONNECT_REQUEST
		//$msg = "0610 0205 001A 0801 c0a80031 0418 0801 c0a80031 0418 0404 0200";

		$msg = "06".							// 06 HEADER_SIZE
		self::KNXIPv10 .	
 		self::CONNECT_REQUEST .
 		"001A".									//totallength,24octets

		"08".									// structure length
		self::IPV4_UDP.							//host protocol code, e.g. 01h, for UDP over IPv4
		bin2hex(inet_pton($this->ServerIp)).					//IPaddressofcontrolendpoint
		sprintf('%04x', $this->ServerPort).	//portnumberofcontrolendpoint
		
		"08".									// structure length	
		self::IPV4_UDP.							//host protocol code, e.g. 01h, for UDP over IPv4
		bin2hex(inet_pton($this->ServerIp)).					// IPaddressofdataendpoint		
		sprintf('%04x', $this->ServerPort).	//portnumberofcontrolendpoint
		
		//Connection Request Information (CRI)
		"04".									//structure length
		self::TUNNEL_CONNECTION.
		self::TUNNEL_LINKLAYER.
		"00";									//Reserved
		if (!$len=self::Send($msg)) 
			return false;
		if (!$len=self::Receive()) 
			return false;
		return $len; 
		}	
	private function DisconnectRequest() {
		$msg = "06".							// 06 HEADER_SIZE
		self::KNXIPv10 .	
 		self::DISCONNECT_REQUEST .
 		"0010".									//totallength,16octets
		sprintf('%02x', $this->Channel_ID).		//communication channel ID, e.g. 21
		"00".									//Reserved
		"08".									// structure length			
		self::IPV4_UDP.							//host protocol code, e.g. 01h, for UDP over IPv4
		bin2hex(inet_pton($this->ServerIp)).						//192.168.0.49  IPaddressofdataendpoint		
		sprintf('%04x', $this->ServerPort);									//portnumberofcontrolendpoint,3671
								//Reserved
		if (!$len=self::Send($msg)) 
			return false;
		if (!$len=self::Receive()) 
			return false;
		return $len; 
		return $len; 
		}		
	private function ConnectStatRequest() {	
		// CONNECT_REQUEST
		//$msg = "0610 0205 001A 0801 c0a80031 0418 0801 c0a80031 0418 0404 0200";

		$msg = "06".							// 06 HEADER_SIZE
		self::KNXIPv10 .	
 		self::CONNECTIONSTATE_REQUEST .
 		"0010".									//totallength,24octets

		sprintf('%02x', $this->Channel_ID).		//communication channel ID, e.g. 21
		"08".									// structure length
		self::IPV4_UDP.							//host protocol code, e.g. 01h, for UDP over IPv4
		bin2hex(inet_pton($this->ServerIp)).					//IPaddressofcontrolendpoint
		sprintf('%04x', $this->ServerPort);	//portnumberofcontrolendpoint
		
		if (!$len=self::Send($msg)) 
			return false;
		if (!$len=self::Receive()) 
			return false;
		return $len; 
		}	
	private function TunnelingSend($msg){ 
		$this->SeqCounter++;			// pour la prochaine emission
		$msg_size_bytes = strlen ($msg) / 2;
		$msg_size_bytes += 12;					//totallength,L+08hoctets 
		$msg_size_hexa = sprintf('%04x', $msg_size_bytes);

		$header = "06".							// 06 HEADER_SIZE
		self::KNXIPv10 .	
 		self::TUNNELLING_REQUEST .
 		$msg_size_hexa.							//totallength,L+12 octets 
		
		"04".									//structure length
		sprintf('%02x', $this->Channel_ID).		//communication channel ID, e.g. 21
		sprintf('%02x', $this->SeqCounter).	//Sequence counter
		"00".									//Reserved
		self::L_Data_req."00";					// Message Code and no additionnal information
		
		
		$msg = $header . $msg;
		
		
		if (!$len=self::Send($msg)) 
			return false;
		if (!$len=self::Receive()) 
			return false;
		log::add('KnxServer','debug',$len);		
		return $len;
	}
	private function TunnelingCheckAck($SeqCounter)	{ 
		if ($SeqCounter !=$this->SeqCounter)
			return false;
		return true;
	}
	private function TunnelingAck(){ 
		$msg = "06".						// 06 HEADER_SIZE
		self::KNXIPv10 .	
 		self::TUNNELLING_ACK .
 		"000A".						//totallength 
 		"04".						// structure length	
 		sprintf('%02x', $this->Channel_ID).
 		sprintf('%02x', $this->SeqCounter).
 		"00";						// 00h (NO_ERROR)

		self::Send($msg);
	}
/******************************************************************************************************************************************************
	* 
	*                                                    Gestion de socket
	*
	*******************************************************************************************************************************************************/
	private function ConnectGateway(){
		set_time_limit(0); 
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if (!$socket) {
			$this->lastError = "socket_create() failed: reason: " . socket_strerror(socket_last_error($socket));
			return false;
		}
	//	if($this->Binded){	
			while(!socket_bind($socket, '0.0.0.0', $this->ServerPort)) 
				$this->ServerPort++;
	//	}
	/*	if (!socket_set_option($socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>0,"usec"=>10))) {
			$this->lastError = "socket_set_option() failed: reason: " . socket_strerror(socket_last_error($socket));
			return false;
		}*/
		log::add('KnxServer', 'debug', 'Is connected KnxGateway to '.$this->ServerIp.':'.$this->ServerPort);
		return $socket;
	}	
	public function CloseConnexion(){
		if ($this->ConnexionMode == "Tunneling")
			self::DisconnectRequest();
		socket_close($this->socket);
		}
	public function Send($msg){
		if (is_resource($this->socket)) {
			$hex_msg = hex2bin($msg);
			$this->SendFrame=unpack("C*", $hex_msg);
			$dataBrute='0x';
			foreach ($this->SendFrame as $Byte)
				$dataBrute.=sprintf('%02x',$Byte).' ';
			log::add('KnxServer', 'TX', $dataBrute);
			//if (!$len = socket_sendto($this->socket, $hex_msg, strlen($hex_msg), 0, $this->BroadcastIp, $this->BroadcastPort)) 
			if (!$len = socket_sendto($this->socket, $hex_msg, strlen($hex_msg), 0, $this->KnxIpGateway, $this->KnxPortGateway)) 
				{
				$this->lastError = "socket_sendto() failed: reason: " . socket_strerror(socket_last_error($this->socket));
				return false;
				}
			return $len; 
		}
		else
			return false;
	}
	private function Receive()	{
		if (is_resource($this->socket)) {
			$buf='';
			$empty_from='';if (!socket_recvfrom($this->socket, $buf, 2048, 0, $empty_from, $port)) 
				{
				$this->lastError = "socket_recvfrom() failed: reason: " . socket_strerror(socket_last_error($this->socket));
				return false;
				}
			$this->ReadFrame=unpack("C*", $buf);
			if (!self::DecriptFrame()) 
				return false;
			return true;
		}
		else
			return false;
	}
	private function DecriptFrame()	{
		$dataBrute='0x';
		foreach ($this->ReadFrame as $Byte)
			$dataBrute.=sprintf('%02x',$Byte).' ';
		log::add('KnxServer', 'RX', $dataBrute);
		$HeaderSize=array_slice($this->ReadFrame,0,1)[0];
		$this->Header=array_slice($this->ReadFrame,0,$HeaderSize);
		$this->Body=array_slice($this->ReadFrame,$HeaderSize);
		switch (array_slice($this->Header,2,1)[0])
			{
			case 0x02:
				switch (array_slice($this->Header,3,1)[0])
					{
					case 0x01:
						$this->ServiceTypeIdentifier="SEARCH_REQUEST";
						break;
					case 0x02:
						$this->ServiceTypeIdentifier="SEARCH_RESPONSE";
						$this->KnxIpGateway =	array_slice($this->Body,2,1)[0]
										.".".	array_slice($this->Body,3,1)[0]
										.".".	array_slice($this->Body,4,1)[0]
										.".".	array_slice($this->Body,5,1)[0];
						$KnxPortGateway =	array_slice($this->Body,6,2);
						$this->KnxPortGateway =$KnxPortGateway[0]<<8|$KnxPortGateway[1];
						$this->IndividualAddressGateWay=array_slice($this->Body,12,1)[0]<<8|array_slice($this->Body,13,1);
						$this->DeviceName= self::Hex2String(array_slice($this->Body,32,4));
						break;
					case 0x03:
						$this->ServiceTypeIdentifier="DESCRIPTION_REQUEST";
						break;
					case 0x04:
						$this->ServiceTypeIdentifier="DESCRIPTION_RESPONSE";
						break;
					case 0x05:
						$this->ServiceTypeIdentifier="CONNECTION_REQUEST";
						break;
					case 0x06:
						$this->ServiceTypeIdentifier="CONNECTION_RESPONSE";
						if (sprintf('%02x',array_slice($this->Body,1,1)[0]) != 0x00) {
							$this->lastError = "CONNECTION_RESPONSE() failed: reason: " .sprintf('%02x',array_slice($this->Body,1,1)[0]);
							return false;
						}
						$this->Channel_ID = array_slice($this->Body,0,1)[0];
						$this->IndividualAddress=array_slice($this->Body,12,1)[0]<<8|array_slice($this->Body,13,1);
						log::add('KnxServer', 'debug', 'Channel_ID: ' . sprintf('%02x',$this->Channel_ID));
						log::add('KnxServer', 'debug', 'INDIVIDUAL_ADDR: ' . self::Hex2AddrPhy($this->IndividualAddress));
						break;
					case 0x07:
						$this->ServiceTypeIdentifier="CONNECTIONSTATE_REQUEST";
						break;
					case 0x08:
						$this->ServiceTypeIdentifier="CONNECTIONSTATE_RESPONSE";
						if ($this->Channel_ID != array_slice($this->Body,0,1)[0])
							return false;
						break;
					case 0x09:
						$this->ServiceTypeIdentifier="DISCONNECT_REQUEST";
						break;
					case 0x0A:
						$this->ServiceTypeIdentifier="DISCONNECT_RESPONSE";
						break;
					}
				break;
			case 0x03:
				$this->ServiceTypeIdentifier="Device Management";
				switch (array_slice($this->Header,3,1)[0])
					{
					case 0x10:
						$this->ServiceTypeIdentifier="DEVICE_CONFIGURATION_REQUEST";
						break;
					case 0x11:
						$this->ServiceTypeIdentifier="DEVICE_CONFIGURATION_ACK";
						break;
					}
				break;
			case 0x04:
				$this->ServiceTypeIdentifier="Tunnelling";
				switch (array_slice($this->Header,3,1)[0])
					{
					case 0x20:
						$this->ServiceTypeIdentifier="TUNNEL_REQUEST";
						$this->Frame=array_slice($this->Body,6);
						$this->SeqCounter = array_slice($this->Body,2,1)[0];//recup SeqCounter du message precedent
						self::TunnelingAck();
						break;
					case 0x21:
						self::TunnelingCheckAck(array_slice($this->Body,2,1)[0]);	
						break;
					}
				break;
			case 0x05:
				//053F   
				//if (0x30>=array_slice($this->Header,3,1)[0]<=0x3F) 
				$this->ServiceTypeIdentifier="Routing";
				$this->Frame=array_slice($this->Body,2);
				break;
			case 0x06:
				//06FF    
				//if (0x00>=array_slice($this->Header,3,1)[0]<=0xFF)
				$this->ServiceTypeIdentifier="Remote Logging";
				break;
			case 0x07:
				//07FF  
				//if (0x40>=array_slice($this->Header,3,1)[0]<=0xFF)
				$this->ServiceTypeIdentifier="Remote Configuration and Diagnosis";
				break;
			case 0x08:
				//08FF  
				//if (0x80>=array_slice($this->Header,3,1)[0]<=0xFF)
				$this->ServiceTypeIdentifier="Object Server";
				break;
			default:
				$this->lastError="Unknown Service Type Identifier";
				return false;
				break;
			}
		return true;
		}
	private function EncriptFrame()	{
		$HeaderSize=array_slice($this->ReadFrame,0,1)[0];
		$this->Header=array_slice($this->ReadFrame,0,$HeaderSize);
		$this->Body=array_slice($this->ReadFrame,$HeaderSize);
		switch ($this->ServiceTypeIdentifier)
			{
			case "SEARCH_REQUEST":
				//0x0201
			break;
			case "DESCRIPTION_REQUEST":
				//0x0203
			break;
			case "CONNECTION_REQUEST":
				//0x0205
			break;
			case "CONNECTIONSTATE_RESPONSE":
				//0x0207
			break;
			case "DISCONNECT_REQUEST":
				//0x0209
			break;
			case "DEVICE_CONFIGURATION_REQUEST":
				//0x0301
			break;
			case "TUNNEL_REQUEST":
				//0x0420
			break;
			default:
				return false;
				break;
			}
		$dataBrute='0x';
		foreach ($this->ReadFrame as $Byte)
			$dataBrute.=sprintf('%02x',$Byte).' ';
		log::add('KnxServer', 'debug', 'Data émise: ' . $dataBrute);
		}
	/******************************************************************************************************************************************************
	* 
	*                                                    Outils de convertion 
	*
	*******************************************************************************************************************************************************/
	private function TP2cEMI($KNX_TP){
		$KNX_TP =str_replace(' ','',$KNX_TP);
		$msg = substr ($KNX_TP, 0, 2).		// CtrlField1 = CtrlField TP
		substr ($KNX_TP, 10, 1)."0".		// CtrlField2 = 1er Quartet + "0000"( standard frame)
		substr ($KNX_TP, 2, 8).				// Src Addr et Dest Addr identique
		"0".substr ($KNX_TP, 11, 1).		// longeur = 2eme Quartet
		substr ($KNX_TP, 12);				// recopie exactement la fin du message

		return $msg;
	}
	private function Gad2Hex($addr){
		$addr = explode("/", $addr);
		if (count ($addr) >= 3)
			$r =(($addr[0] & 0x1f) << 11) | (($addr[1] & 0x7) << 8) | (($addr[2] & 0xff));
		if (count ($addr) == 2)
			$r = (($addr[0] & 0x1f) << 11) | (($addr[1] & 0x7ff));
		if (count ($addr) == 1)
			$r = (($addr[1] & 0xffff));
		return $r;
	}
	private function Hex2Gad ($addr)	{
		return sprintf ("%d/%d/%d", ($addr >> 11) & 0x1f, ($addr >> 8) & 0x07,($addr >> 0) & 0xff);
	}	
	private function Hex2AddrPhy ($addr){
		return sprintf ("%d.%d.%d", ($addr >> 12) & 0x0f, ($addr >> 8) & 0x0f, ($addr >> 0) & 0xff);
	}
	private function AddrPhy2Hex ($addr){
		$addr = explode(".", $addr);
		$r=(($addr[0] << 12) & 0x0f) | (($addr[1] << 8) & 0x0f) | (($addr[2] << 0) & 0xff);
		return $r;
	}
	private function Hex2String($datahex){
		$string='';
		foreach ($datahex as $hexcar)
			$string .= chr($hexcar);
		return $string;
		}
	/******************************************************************************************************************************************************
	* 
	*                                                    Externaliser les informations
	*
	*******************************************************************************************************************************************************/
	public function setConnexionMode($ConnexionMode){
		$this->ConnexionMode=$ConnexionMode;
		}
	public function getConnexionMode()	{
		return $this->ConnexionMode;
		}	
	public function setKnxIpGateway($KnxIpGateway){
		$this->KnxIpGateway=$KnxIpGateway;
		}
	public function getKnxIpGateway()	{
		return $this->KnxIpGateway;
		}	
	public function setKnxPortGateway($KnxPortGateway){
		$this->KnxPortGateway=$KnxPortGateway;
		}
	public function getKnxPortGateway()	{
		return $this->KnxPortGateway;
	}
	public function getIndividualAddressGateWay()	{
		return self::Hex2AddrPhy($this->IndividualAddressGateWay);
	}
	public function setIndividualAddressGateWay($IndividualAddressGateWay)	{
		$this->IndividualAddressGateWay=self::AddrPhy2Hex($IndividualAddressGateWay);
	}
	public function getDeviceName()	{
		return $this->DeviceName;
	}
	public function getLastError()	{
		return $this->lastError;
	}
	public function getChannelID()	{
		return $this->Channel_ID;
	}
	public function setChannelID($channelId)	{
		 $this->Channel_ID=	$channelId;
	}
	public function setSeqCounter($SeqCounter){
		$this->SeqCounter=$SeqCounter;
		}
	public function getSeqCounter()	{
		return $this->SeqCounter;
		}	
	public function getServerPort()	{
		return $this->ServerPort;
	}	
	public function setBinded($Binded)	{
		$this->Binded=$Binded;
	}	
	public function setServerPort($ServerPort)	{
		$this->ServerPort=$ServerPort;
	}
	public function getIndividualAddress()	{
		return self::Hex2AddrPhy($this->IndividualAddress);
	}
	public function setIndividualAddress($IndividualAddress)	{
		$this->IndividualAddress=self::AddrPhy2Hex($IndividualAddress);
	}
	public function getControleField1(){
		return $this->ControleField1=array_slice($this->Frame,0,1)[0];
	}
	public function getControleField2(){
		return $this->ControleField2=array_slice($this->Frame,1,1)[0];
	}
	public function getAdrSource(){
		$this->AdrSource=array_slice($this->Frame,2,2) ;
		$this->AdrSource=(($this->AdrSource[0]& 0xff )<< 8) | ($this->AdrSource[1]& 0xff );
		return self::Hex2AddrPhy($this->AdrSource);
	}
	public function getAdrGroup(){
		$this->AdrGroup=array_slice($this->Frame,4,2) ;
		$this->AdrGroup=(($this->AdrGroup[0]& 0xff )<< 8) | ($this->AdrGroup[1]& 0xff );
		return self::Hex2Gad($this->AdrGroup);
	}
	public function getSizedata(){
		return $this->Sizedata=array_slice($this->Frame,6,1)[0];
	}
	public function getACPI(){
		$this->TPCI=(array_slice($this->Frame,7,1)[0] >> 2 ) & 0x3F;
		$this->APCI=(array_slice($this->Frame,7,1)[0] & 0x3F) | (array_slice($this->Frame,8,1)[0]);
		switch ($this->APCI & 0x0C0)
		{
			case 0x00:
				return "Read";
			break;
			case 0x40:
				return "Response";
			break;
			case 0x80:
				return "Write";
			break;
		}
		return false;
	}
	public function getData(){
		if (count($this->Frame) == 9)
			$this->Data=array_slice($this->Frame,8)[0]& 0x3F;
		else
			$this->Data=array_slice($this->Frame,9);
		return $this->Data;
	}
	public function getKnxIpVersion()	{
		return $this->KnxIpVersion=array_slice($this->Header,2,1)[0];
	}
	public function getServiceTypeIdentifier(){
		return $this->ServiceTypeIdentifier;
	}	
	public function getTotalLengh()	{
		return $this->TotalLengh=array_slice($this->Header,5,1)[0];
	}
}
?>
