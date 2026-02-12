<?php
class knxproj {
	private $path;
	private $ProjetType;
	private $Devices=array();
	private $GroupAddresses=array();
	private $Locations=array();
	private $Templates=array();
	private $myProject=array();
	public static function ExtractTX100ProjectFile($File){
		$path = dirname(__FILE__) . '/../../data/knxproj/';
		if (!is_dir($path)) 
			mkdir($path);
		exec('sudo chmod -R 777 '.$path);
		system('cd ' . $path . '; tar xfz "' . $File . '"');
		log::add('eibd','debug','[Import TX100] Extraction des fichiers de projets');
	}
	public static function ExtractETSProjectFile($File){
		$path = dirname(__FILE__) . '/../../data/knxproj/';
		if (!is_dir($path)) 
			mkdir($path);
		exec('sudo chmod -R 777 '.$path);
		$zip = new ZipArchive(); 
		// On ouvre l’archive.
		if($zip->open($File) == TRUE){
			$zip->extractTo($path);
			$zip->close();
		}
		log::add('eibd','debug','[Import ETS] Extraction des fichiers de projets');
	}
 	public function __construct($_Merge,$_ProjetType){
		$this->path = dirname(__FILE__) . '/../../data/knxproj/';
		$this->Templates=eibd::devicesParameters();
		$this->ProjetType=$_ProjetType;
		if($_Merge != 'false'){
			log::add('eibd','debug','[Import ETS] Chargement du fichier projet');
			$filename=dirname(__FILE__) . '/../../data/KnxProj.json';
			$myKNX=json_decode(file_get_contents($filename),true);
			$this->Devices=$myKNX['DevicesAll'];
			$this->GroupAddresses=$myKNX['GAD'];
			$this->Locations=$myKNX['Locations'];
		}
		//log::add('eibd','debug','[Import ETS]'.json_encode($_options));
		switch($this->ProjetType){
			case "ETS":
				$ProjetFile=$this->SearchETSFolder("P-");
				$this->myProject=simplexml_load_file($ProjetFile.'/0.xml');
				$this->ParserETSDevice();
				$this->ParserETSGroupAddresses();
				$this->ParserLocations();
				//$this->CheckOptions();
			break;
			case "TX100":
				$ProjetFile=$this->SearchTX100Folder($this->path);
				$this->ParserTX100GroupAddresses();
				$this->ParserTX100Products();				
			break;
		}
	}
 	public function __destruct(){
		$path = dirname(__FILE__) . '/../../data/knxproj/';
		if (file_exists($path)) 
			exec('sudo rm -R '.$path );
	}
	public function WriteJsonProj(){
		$filename=dirname(__FILE__) . '/../../data/KnxProj.json';
		if (file_exists($filename)) 
			exec('sudo rm '.$filename);
		$file=fopen($filename,"a+");
		fwrite($file,$this->getAll());
		fclose($file);	
	}
	public function getAll(){
		foreach($this->Devices as $DeviceProductRefId => $Device){
			$myKNX['Devices'][$Device['DeviceName']] = null;
			foreach($Device['Cmd'] as $GroupAddressRefId=> $Cmd){
				$myKNX['Devices'][$Device['DeviceName']][$Cmd['cmdName']]['AdressePhysique']=$Device['AdressePhysique'];
				$myKNX['Devices'][$Device['DeviceName']][$Cmd['cmdName']]['AdresseGroupe']=$Cmd['AdresseGroupe'];
				$myKNX['Devices'][$Device['DeviceName']][$Cmd['cmdName']]['DataPointType']=$Cmd['DataPointType'];
			}
		}
		$myKNX['DevicesAll']=$this->Devices;
		$myKNX['GAD']=$this->GroupAddresses;
		$myKNX['Locations']=$this->Locations;
		return json_encode($myKNX,JSON_PRETTY_PRINT);
	}
	private function SearchTX100Folder($path){
		if ($dh = opendir($path)){
			log::add('eibd','debug','[Import TX100] overture de  '.$path);
			while (($file = readdir($dh)) !== false){
				if($file != '.' && $file != '..'){
					log::add('eibd','debug','[Import TX100] Rechercher dans '.$path.$file);
					if ($file == 'configuration'){
						$this->path = $path.$file.'/';
						log::add('eibd','debug','[Import TX100] Dossier courant '.$this->path);
						return $this->path;
					}else{
						$this->SearchTX100Folder($path.$file.'/');
					}
				}
			}
			closedir($dh);
		}	
		return false;
	}
	private function SearchETSFolder($Folder){
		if ($dh = opendir($this->path)){
			while (($file = readdir($dh)) !== false){
				if (substr($file,0,2) == $Folder){
					if (opendir($this->path . $file)) 
						return $this->path . $file;
				}
			}
			closedir($dh);
		}	
		return false;
	}
	private function getETSCatalogue($DeviceProductRefId){	
		//log::add('eibd','debug','[Import ETS] Rechecher des noms de module dans le catalogue');
		$Catalogue = new DomDocument();
		if ($Catalogue->load($this->path . substr($DeviceProductRefId,0,6).'/Catalog.xml')) {//XMl décrivant les équipements
			foreach($Catalogue->getElementsByTagName('CatalogItem') as $CatalogItem){
				if ($DeviceProductRefId==$CatalogItem->getAttribute('ProductRefId'))
					return $CatalogItem->getAttribute('Name');
			}
		}
	}
	private function xml_attribute($object, $attribute){
		if(isset($object[$attribute]))
			return (string) $object[$attribute];
		return false;
	}
	private function getTX100Topology($id){
		$Topology=simplexml_load_file($this->path . 'Topology.xml');
		foreach ($Topology->children() as $Room) {
			$RoomId = $this->xml_attribute($Room, 'name');	
			foreach ($Room->children() as $Property) {
				$PropertyKey = $this->xml_attribute($Property, 'key');
				$PropertyValue = $this->xml_attribute($Property, 'value');
				if($PropertyKey == "name")
					return $PropertyValue;
			}
		}
		return '';
	}
	private function ParserTX100Products(){
		log::add('eibd','debug','[Import TX100] Recherche des equipement');
		$Products=simplexml_load_file($this->path . 'Products.xml');		
		foreach ($Products->children() as $Product) {
			$ProductId = $this->xml_attribute($Product, 'name');	
			foreach ($Product->children() as $Property) {
				$PropertyKey = $this->xml_attribute($Property, 'key');
				$PropertyValue = $this->xml_attribute($Property, 'value');
				if($PropertyKey == "SerialNumber")
					$SerialNumber = $PropertyValue;
				if($PropertyKey == "ProductCatalogReference")
					$Reference = $PropertyValue;
				if($PropertyKey == "IndividualAddress")
					$IndividualAddress = $PropertyValue;
				if($PropertyKey == "product.location")
					$Location = $this->getTX100Topology($PropertyValue);
			}
			$this->Devices[$ProductId]['AdressePhysique'] = $IndividualAddress;
			$this->Devices[$ProductId]['DeviceName'] = $Location.' - '.$Reference.' ('.$SerialNumber.')';
			$this->Devices[$ProductId]['Cmd'] = $this->getTX100ProductCmd($ProductId);
			//$this->Devices[$ProductId]['Cmd']['']['DataPointType']='';
			//$this->Devices[$ProductId]['Cmd']['']['cmdName']='';
			//$this->Devices[$ProductId]['Cmd']['']['AdresseGroupe']='';
									
		}
	}
	private function ParserTX100GroupAddresses(){
		log::add('eibd','debug','[Import TX100] Création de l\'arborescence d\'adresses de groupe');
		$GroupLinks=simplexml_load_file($this->path . 'GroupLinks.xml');
		$this->GroupAddresses = $this->getTX100Level($GroupLinks);
	}
	private function getTX100Level($GroupRanges,$NbLevel=0){
		$Level = array();
		$NbLevel++;
		foreach ($GroupRanges->children() as $GroupRange) {
			$GroupName = $this->xml_attribute($GroupRange, 'name');
          		if ($GroupName == 'Links'){
		  		$NbLevel--;
				return $this->getTX100Level($GroupRange,$NbLevel);
			}
			if($GroupRange->getName() == 'property' && $this->xml_attribute($GroupRange, 'key') == "GroupAddress"){
				config::save('level',$NbLevel,'eibd');
				$AdresseGroupe=$this->formatgaddr($this->xml_attribute($GroupRange, 'value'));
				$ChannelId=$this->xml_attribute($GroupRanges->config->property, 'key');
				$DataPointId=$this->xml_attribute($GroupRanges->config->property, 'value');
				list($DataPointType,$GroupName)=$this->getTX100DptInfo($ChannelId,$DataPointId);
				$Level[$GroupName]=array('DataPointType' => $DataPointType,'AdresseGroupe' => $AdresseGroupe);
				return $Level;
			}else{
				if($GroupRange->getName() == 'config')
					$Level[$GroupName]=$this->getTX100Level($GroupRange,$NbLevel);
			}
        	}
		return $Level;
	}
	private function getTX100ProductCmd($ProductId){
		$DataPointType='';	
		$GroupName=' - ';
		$Channels=simplexml_load_file($this->path . 'Channels.xml');
		foreach ($Channels->children() as $Channel) {
			$ChannelId = $this->xml_attribute($Channel, 'name');
			foreach ($Channel->children() as $Block) {
				if($this->xml_attribute($Block, 'name') == "Context"){
					foreach ($Block->children() as $parameter) {
						if($this->xml_attribute($parameter, 'key') == 'product.id'){
							if($this->xml_attribute($parameter, 'value') == $ProductId){
							}
						}
					}
				}
			}
		}
		return array($DataPointType,$GroupName);
	}
	private function getTX100DptInfo($ChannelId,$DataPointId){
		$DataPointType='';	
		$GroupName=' - ';
		$Channels=simplexml_load_file($this->path . 'Channels.xml');
		foreach ($Channels->children() as $Channel) {
			if($this->xml_attribute($Channel, 'name') == $ChannelId){
				foreach ($Channel->children() as $Block) {
					if($this->xml_attribute($Block, 'name') == "FunctionalBlocks"){
						foreach ($Block->children() as $FunctionalBlock) {
							foreach ($FunctionalBlock->config->children() as $datapoints) {								
								if($this->xml_attribute($datapoints, 'name') == $DataPointId){	
									foreach ($datapoints->children() as $parameter) {
										if($this->xml_attribute($parameter, 'key') == 'aDPTNumber')
											$DataPointType=$this->xml_attribute($parameter, 'value').".xxx";
										if($this->xml_attribute($parameter, 'key') == 'name')
											$GroupName=$this->xml_attribute($parameter, 'value');
									}
									return array($DataPointType,$GroupName);
								}
							}
						}
					}
				}
			}
		}
		return array($DataPointType,$GroupName);
	}
	private function ParserLocations(){
		log::add('eibd','debug','[Import ETS] Création de l\'arborescence de localisation');
		$Level = $this->myProject->Project->Installations->Installation->Locations;
		$this->Locations = $this->getETSLevel($Level,$this->Locations);
		if(!$this->Locations){
			$Level = $this->myProject->Project->Installations->Installation->Buildings;
			$this->Locations = $this->getETSLevel($Level,$this->Locations);

		}
	}
	private function ParserETSGroupAddresses(){
		log::add('eibd','debug','[Import ETS] Création de l\'arborescence d\'adresses de groupe');
		$GroupRanges= $this->myProject->Project->Installations->Installation->GroupAddresses->GroupRanges;
		$this->GroupAddresses = $this->getETSLevel($GroupRanges,$this->GroupAddresses);
	}
	private function getETSLevel($GroupRanges,$Level=null,$NbLevel=0){
    		if($Level == null)
			$Level = array();
		$NbLevel++;
		if($GroupRanges == null)
			return false;
		foreach ($GroupRanges->children() as $GroupRange) {
			$GroupName = $this->xml_attribute($GroupRange, 'Name');
			if($GroupRange->getName() == 'GroupAddress'){
				config::save('level',$NbLevel,'eibd');
				$AdresseGroupe=$this->formatgaddr($this->xml_attribute($GroupRange, 'Address'));
				$GroupId=$this->xml_attribute($GroupRange, 'Id');
				list($AdressePhysique,$DataPointType)=$this->updateDeviceInfo($GroupId,$GroupName,$AdresseGroupe);				
				$Level[$GroupName]=array('Id' => $GroupId ,'AdressePhysique' => $AdressePhysique ,'DataPointType' => $DataPointType,'AdresseGroupe' => $AdresseGroupe);
			}elseif($GroupRange->getName() == 'GroupAddressRef'){	
				foreach($this->getGad($this->xml_attribute($GroupRange, 'RefId')) as $GroupName => $GroupParam)
					$Level[$GroupName] = $GroupParam;
			}elseif($GroupRange->getName() == 'DeviceInstanceRef'){	
				$Level = $this->getDeviceGad($Level,$this->xml_attribute($GroupRange, 'RefId'));    
			}else{
				if(!is_array($Level[$GroupName]))
					$Level[$GroupName]=$this->getETSLevel($GroupRange,null,$NbLevel);
			}
		}
		return $Level;
	}
	private function getGad($id,$level=null,$loop=0){	
		if($level == null)
			$level = $this->GroupAddresses;
		$loop++;
		$Gad = null;
		foreach($level as $NameGroup => $GroupAddresse){
			if(isset($GroupAddresse['Id'])){
				$loop--;
				//if(strrpos($id,$GroupAddresse['Id']) !== false){
				if($id == $GroupAddresse['Id']){
					$Gad[$NameGroup] = $GroupAddresse;
					break;
				}
			}elseif(is_array($GroupAddresse) && $loop < 3 && $Gad == null){
				$Gad = $this->getGad($id,$GroupAddresse,$loop);
			}
		}
		return $Gad;
	}
	private function getDeviceGad($DeviceGad,$id){	
		if($DeviceGad == null)
			$DeviceGad =array();
		foreach($this->Devices as $DeviceProductRefId => $Device){
			if(strrpos($id,$DeviceProductRefId) !== false){
				foreach($Device['Cmd'] as $GroupAddressRefId=> $Cmd){
					$DeviceGad[$Cmd['cmdName'].' ('.$Device['AdressePhysique'].')']['AdressePhysique']=$Device['AdressePhysique'];
					$DeviceGad[$Cmd['cmdName'].' ('.$Device['AdressePhysique'].')']['AdresseGroupe']=$Cmd['AdresseGroupe'];
					$DeviceGad[$Cmd['cmdName'].' ('.$Device['AdressePhysique'].')']['DataPointType']=$Cmd['DataPointType'];
				}
             			break;
			}
		}
		return $DeviceGad;
	}
	private function updateDeviceInfo($id,$name,$addr){
		$AdressePhysique='';
		$DataPointType='';
		foreach($this->Devices as $DeviceProductRefId => $Device){
			foreach($Device['Cmd'] as $GroupAddressRefId=> $Cmd){
				if(strrpos($id,$GroupAddressRefId) !== false){
					$AdressePhysique = $this->Devices[$DeviceProductRefId]['AdressePhysique'];
					$this->Devices[$DeviceProductRefId]['Cmd'][$GroupAddressRefId]['cmdName']=$name;
					$this->Devices[$DeviceProductRefId]['Cmd'][$GroupAddressRefId]['AdresseGroupe']=$addr;
					if($DataPointType == '')
						$DataPointType = $this->Devices[$DeviceProductRefId]['Cmd'][$GroupAddressRefId]['DataPointType'];
				}
			}
		}
		return array($AdressePhysique,$DataPointType);
	}
	private function ParserETSDevice(){
		log::add('eibd','debug','[Import ETS] Recherche de device');
		$Topology = $this->myProject->Project->Installations->Installation->Topology;
		if($Topology == null)
			return false;
		foreach($Topology->children() as $Area){
			$AreaAddress=$this->xml_attribute($Area, 'Address');
			foreach ($Area->children() as $Line)  {
				$LineAddress=$this->xml_attribute($Line, 'Address');
				foreach ($Line->children() as $Device)  {
					$DeviceId=$this->xml_attribute($Device, 'Id');
					$DeviceProductRefId=$this->xml_attribute($Device, 'ProductRefId');
					if ($DeviceProductRefId != ''){
						$this->Devices[$DeviceId]=array();
                      				$this->Devices[$DeviceId]['DeviceName']=$this->getETSCatalogue($DeviceProductRefId);
						$DeviceAddress=$this->xml_attribute($Device, 'Address');
						$this->Devices[$DeviceId]['AdressePhysique']=$AreaAddress.'.'.$LineAddress.'.'.$DeviceAddress;
						foreach($Device->children() as $ComObjectInstanceRefs){
							if($ComObjectInstanceRefs->getName() == 'ComObjectInstanceRefs'){
								foreach($ComObjectInstanceRefs->children() as $ComObjectInstanceRef){
									$DataPointType=explode('-',$this->xml_attribute($ComObjectInstanceRef, 'DatapointType'));
									if($this->xml_attribute($ComObjectInstanceRef, 'Links') !== false){
										$this->Devices[$DeviceId]['Cmd'][$this->xml_attribute($ComObjectInstanceRef, 'Links')]['DataPointType']=$DataPointType[1].'.'.sprintf('%1$03d',$DataPointType[2]);
									}else{
										foreach($ComObjectInstanceRef->children() as $Connector){
											foreach($Connector->children() as $Commande)
												$this->Devices[$DeviceId]['Cmd'][$this->xml_attribute($Commande, 'GroupAddressRefId')]['DataPointType']=$DataPointType[1].'.'.sprintf('%1$03d',$DataPointType[2]);
										}
									}
								}
							}
						}
					}
				}
			}
		}
	}
	private function formatgaddr($addr){
		switch(config::byKey('level', 'eibd')){
			case '3':
				return sprintf ("%d/%d/%d", ($addr >> 11) & 0x1f, ($addr >> 8) & 0x07,$addr & 0xff);
			break;
			case '2':
				return sprintf ("%d/%d", ($addr >> 11) & 0x1f,$addr & 0x7ff);
			break;
			case '1':
				return sprintf ("%d", $addr);
			break;
		}
	}
}
?>
