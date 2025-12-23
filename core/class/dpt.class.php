<?php
require_once dirname(__FILE__) . '/DataPointType/EIS14_ABB_ControlAcces.class.php';
class Dpt{
	public static function DptSelectEncode ($dpt, $value, $inverse=false, $option=null){
		$All_DPT=self::All_DPT();
		switch (explode('.',$dpt)[0]){
			case "1":
				if ($value != 0 && $value != 1)
					{
					$ValeurDpt=$All_DPT["Boolean"][$dpt]['Valeurs'];
					$value = array_search($value, $ValeurDpt); 
					}
				if ($inverse){
					if ($value == 0 )
						$value = 1;
					else
						$value = 0;
				}
				$data= $value;
				break;
			case "2":
				$data= $value;
				break;
			case "3":
				if ($value > 0)
					$stepCode = abs($value) & 0x07;
				$data = $option["ctrl"] << 3 | $stepCode;
				break;
			case "5":
				switch ($dpt){
					case "5.001":
						if ($inverse)
							$value=100-$value;
						$value = round(intval($value) * 255 / 100);
						break;
					case "5.003":
						if ($inverse)
							$value=360-$value;
						$value = round(intval($value) * 255 / 360);
						break;
					case "5.004":
						if ($inverse)
							$value=255-$value;
						$value = round(intval($value) * 255);
						break;
				}
				$data= array($value);
				break;
			case "6":
				if ($value < 0)
					$value = (abs($value) ^ 0xff) + 1 ; # twos complement
				$data= array($value);
				break;
			case "7":
				$data= array(($value >> 8)&0xff, ($value& 0xff));
				break;
			case "8":
				if($value >= 0x8000)
					$value = -(($value - 1) ^ 0xffff);  # invert twos complement
				$data= array(($value >> 8)&0xff, ($value& 0xff));
				break;
			case "9": 
				if($value<0){
					$sign = 1;
					$value = - $value;
				}
				else
					$sign = 0;
				$value = $value * 100.0;
				$exp = 0;
				while ($value > 2047){
					$exp ++;
					$value = $value / 2;
				}
				if ($sign)
					$value = - $value;
				$value = $value & 0x7ff;
				$data= array(($sign << 7) | (($exp & 0x0f)<<3)| (($value >> 8)&0x07), ($value& 0xff));
				break;
			case "10": 
				$date   = new DateTime($value); 
				$wDay = $date->format('N');
				$hour = $date->format('H');
				$min = $date->format('i');
				$sec = $date->format('s');
				$data = array(($wDay << 5 )| $hour  , $min , $sec);
				break;
			case "11":
				$date = new DateTime(); 
				if($value != ''){
					$value = strtotime(str_replace('/', '-', $value)); 
					$date->setTimestamp($value);
				}
				$day = $date->format('d');
				$month = $date->format('m');
				$year = $date->format('y');
				$data = array($day,$month ,$year);
				break;
			case "12":
				$data= unpack("C*", pack("L", $value));
				break;
			case "13":
				if ($value < 0)
					$value = (abs($value) ^ 0xffffffff) + 1 ; # twos complement
				$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF);
				break;
			case "14":
				$value = unpack("L",pack("f", $value)); 
				$data = array(($value[1]>>24)& 0xff, ($value[1]>>16)& 0xff, ($value[1]>>8)& 0xff,$value[1]& 0xff);
				break;
			case "16":
				$data=array();
				$chr=str_split($value);
				for ($i = 0; $i < 14; $i++)
					$data[$i]=ord($chr[$i]);
				break;
			case "17":
				$data= array(($value -1) & 0x3f);
				break;
			case "18":
				$control = jeedom::evaluateExpression($option["ctrl"]);
				$data= array(($control << 8) & 0x80 | $value & 0x3f);
				break;
			case "19": 
				$date = new DateTime(); 
				if($value != ''){
					$value = strtotime(str_replace('/', '-', $value)); 
					$date->setTimestamp($value);
				}
				$wDay = $date->format('N');
				$hour = $date->format('H');
				$min = $date->format('i');
				$sec = $date->format('s');
				$day = $date->format('d');
				$month = $date->format('m');
				$year = $date->format('Y')-1900;
				$data = array($year,$month & 0x0f ,$day & 0x1f,($wDay << 5 ) & 0xe0| $hour  & 0x1f , $min  & 0x3f , $sec & 0x3f,0x00,0x00);
			break;
			case "20":
				if ($dpt != "20.xxx"){
					if(!is_numeric($value)){
						$ValeurDpt=$All_DPT["8BitEncAbsValue"][$dpt]["Valeurs"];
						$value = array_search($value, $ValeurDpt); 
					}
				}
				$data= array($value);
			break;
			case "23":
				if ($dpt != "23.xxx"){
					if(!is_numeric($value)){
						$ValeurDpt=$All_DPT["2bit"][$dpt]["Valeurs"];
						$value = array_search($value, $ValeurDpt); 
					}
				}
				$data= array($value);
			break;
			case "27":
				foreach(explode('|',$option["Info"]) as $bit => $Info){
					$value=cmd::byId(str_replace('#','',$Info))->execCmd();
					$data= array();
					if($bit < 8)
						$data[0].=$value>>$bit;
					elseif($bit < 16)
						$data[1].=$value>>$bit;
					if($value){
						if($bit < 8)
							$data[2].=0x01>>$bit;
						elseif($bit < 16)
							$data[3].=0x01>>$bit;
					}
						
				}
			break;
			case "225":
				if ($dpt != "225.002"){
					$TimePeriode=cmd::byId(str_replace('#','',$option["TimePeriode"]));
					$data= array(($TimePeriode->execCmd() >> 8) & 0xFF, $TimePeriode->execCmd() & 0xFF, $value);
				}
			break;
			case "229":
				if ($dpt != "229.001"){
					if ($value < 0)
					   $value = (abs($value) ^ 0xffffffff) + 1 ; 
					$ValInfField=cmd::byId(str_replace('#','',$option["ValInfField"]));
					$StatusCommande=cmd::byId(str_replace('#','',$option["StatusCommande"]));
					$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF,$ValInfField->execCmd(),$StatusCommande->execCmd());
				}
			break;
			case "232":	
				$data= self::html2rgb($value);
			break;
			case "235":
				if ($dpt != "235.001"){
					/*if ($value < 0)
					   $value = (abs($value) ^ 0xffffffff) + 1 ; */
					foreach(explode('|',$option["ActiveElectricalEnergy"]) as $tarif => $ActiveElectricalEnergy){
						$value=cmd::byId(str_replace('#','',$ActiveElectricalEnergy))->execCmd();
						$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF,$tarif,(0<< 1) & 0x02 | 0);
					}
				}
			break;
			case "251":
				list($r, $g, $b)=self::html2rgb($value);
				$w=jeedom::evaluateExpression($option["Température"]);
				$data= array($r, $g, $b, $w, 0x00, 0x0F);
			break;
			case "Color":	
				$data= false;
				list($r, $g, $b)=self::html2rgb($value);
				$cmdR=cmd::byId(str_replace('#','',$option["R"]));
				if(is_object($cmdR))
					$cmdR->execCmd(array('slider'=>$r));
				$cmdG=cmd::byId(str_replace('#','',$option["G"]));
				if(is_object($cmdG))
					$cmdG->execCmd(array('slider'=>$g));
				$cmdB=cmd::byId(str_replace('#','',$option["B"]));
				if(is_object($cmdB))
					$cmdB->execCmd(array('slider'=>$b));
			break;	
			case "ABB_ControlAcces_Read_Write":
				$Group=jeedom::evaluateExpression($option["Group"]);
				$PlantCode=jeedom::evaluateExpression($option["PlantCode"]);
				$Expire=jeedom::evaluateExpression($option["Expire"]);
				$data = EIS14_ABB_ControlAcces::WriteTag($value,$Group,$PlantCode,$Expire);
			break;
			default:
				switch($dpt){
					case "x.001":
						if ($option["Mode"] !=''){
							$data= array();
							$Mode=cmd::byId(str_replace('#','',$option["Mode"]));
							if (is_object($Mode)){
								$Mode->event(($data[0]>>1) & 0xEF);
								$Mode->setCache('collectDate', date('Y-m-d H:i:s'));
							}
						}
						$data= array(($Mode->execCmd()<< 1) & 0xEF | $value& 0x01);
					break;
				}
			break;
		};
		return $data;
	}
	public static function DptSelectDecode ($dpt, $data, $inverse=false, $option=null){
		if ($inverse)
			log::add('eibd', 'debug','La commande sera inversée');
		$All_DPT=self::All_DPT();
		switch (explode('.',$dpt)[0]){
			case "1":
				$value = $data;		
				if ($inverse)
					{
					if ($value == 0 )
						$value = 1;
					else
						$value = 0;
					}
				break;
			case "2":
				$value = $data;	
				break;
			case "3": 
				$ctrl = ($data & 0x08) >> 3;
				$stepCode = $data & 0x07;
				if ($ctrl)
					$value = $stepCode;
				else 
					$value = -$stepCode;
				break;
			case "5":  
				switch ($dpt)
				{
					case "5.001":
						$value = round((intval($data[0]) * 100) / 255);
						if ($inverse)
							$value=100-$value;
						break;
					case "5.003":
						$value = round((intval($data[0]) * 360) / 255);
						if ($inverse)
							$value=360-$value;
						break;
					case "5.004":
						$value = round(intval($data[0]) / 255);
						break;
					default:
						$value = intval($data[0]);
						break;
				}     
				break;
			case "6":
				if ($data[0] >= 0x80)
					$value = -(($data[0] - 1) ^ 0xff);  # invert twos complement
				else
					$value = $data[0];
				break;
			case "7":
				$value = $data[0] << 8 | $data[1];
				break;
			case "8":  
				$value = $data[0] << 8 | $data[1];
				if ($value >= 0x8000)
					$value = -(($value - 1) ^ 0xffff);  # invert twos complement
				break;
			case "9": 
				$exp = ($data[0] & 0x78) >> 3;
				$sign = ($data[0] & 0x80) >> 7;
				$mant = ($data[0] & 0x07) << 8 | $data[1];
				if ($sign)
					$sign = -1 << 11;
				else
					$sign = 0;
				$value = ($mant | $sign) * pow (2, $exp) * 0.01;   
				break;
			case "10": 
				$wDay =($data[0] >> 5) & 0x07;
				$hour =$data[0]  & 0x1f;
				$min = $data[1] & 0x3f;
				$sec = $data[2] & 0x3f;
				$value = /*new DateTime(*/$hour.':'.$min.':'.$sec;//);
				break;
			case "11":
				$day = $data[0] & 0x1f;
				$month = $data[1] & 0x0f;
				$year = $data[2] & 0x7f;
				if ($year<90)
					$year+=2000;
				else
					$year+=1900;
				$value =/* new DateTime(*/$day.'/'.$month.'/'.$year;//);
				break;
			case "12":
				$value = unpack("L",pack("C*",$data[3],$data[2],$data[1],$data[0]));
				break;
			case "13":
				$value = $data[0] << 24 | $data[1] << 16 | $data[2] << 8 | $data[3] ;
				if ($value >= 0x80000000)
					$value = -(($value - 1) ^ 0xffffffff);  # invert twos complement           
				break;
			case "14":
				$value= $data[0]<<24 |  $data[1]<<16 |  $data[2]<<8 |  $data[3]; 
				$value = unpack("f", pack("L", $value))[1];
				break;
			case "16":
				$value='';
				foreach($data as $chr)
					$value.=chr(($chr));
				break;

			case "17":
				$value = $data[0] & 0x3f;
				$value += 1;
				break;
			case "18":
				if ($option != null)	{
					if ($option["ctrl"] !=''){	
						$control=cmd::byId(str_replace('#','',$option["ctrl"]));
						if (is_object($control)){
							$ctrl = ($data[0] >> 7) & 0x01;
							log::add('eibd', 'debug', 'L\'objet '.$control->getName().' a été trouvé et va être mis à jour avec la valeur '. $ctrl);
							$control->event($ctrl);
							$control->setCache('collectDate', date('Y-m-d H:i:s'));
						}
					}
				}
				$value = $data[0] & 0x3f;
				break;
			case "19":
				$year=$data[0]+1900;
				$month=$data[1]& 0x0f;
				$day=$data[2]& 0x1f;
				$wDay =($data[3] >> 5) & 0x07;
				$hour =$data[3]  & 0x1f;
				$min = $data[4] & 0x3f;
				$sec = $data[5] & 0x3f;
				$Fault=($data[6] >> 7) & 0x01;
				$WorkingDay=($data[6] >> 6) & 0x01;
				$noWorkingDay=($data[6] >> 5) & 0x01;
				$noYear=($data[6] >> 4) & 0x01;
				$noDate=($data[6] >> 3) & 0x01;
				$noDayOfWeek=($data[6] >> 2) & 0x01;
				$NoTime=($data[6] >> 1) & 0x01;
				$SummerTime=$data[6] & 0x01;
				$QualityOfClock=($data[7] >> 7) & 0x01;
				//$date = new DateTime();
				//$date->setDate($year ,$month ,$day );
				//$date->setTime($hour ,$min ,$sec );
				//$value = $date->format('Y-m-d h:i:s')	
				$value = $day.'/'.$month.'/'.$year.' '.$hour.':'.$min.':'.$sec;
				break;
			case "20":
				$value = $data[0];
				if ($dpt != "20.xxx"){
					if (dechex($value)>0x80)
						$value = dechex($value)-0x80;
					if (dechex($value)>0x20)
						$value = dechex($value)-0x20;
					$value = $All_DPT["8BitEncAbsValue"][$dpt]["Valeurs"][$value];
				}
			break;
			case "23":
				$value = $data[0];
				if ($dpt != "23.xxx")
					$value = $All_DPT["2bit"][$dpt]["Valeurs"][$value];
			break;
			case "27":
				if ($option != null){
					for($byte=0;$byte<count($data);$byte++){
						if ($option["Info"] !='')
							$Info=explode('|',$option["Info"]);	
						for($bit=0;$bit <= 0xFF;$bit++){
							$bits=str_split($data[$byte],1);
							$InfoCmd=cmd::byId(str_replace('#','',$Info[$bit]));
							if (is_object($InfoCmd)){
								log::add('eibd', 'debug', 'Nous allons mettre à jour l\'objet: '. $InfoCmd->getHumanName);
								$InfoCmd->event($bits[$bit]);
								$InfoCmd->setCache('collectDate', date('Y-m-d H:i:s'));
							}
						}
					}
				}
			break;
			case "225":
				if ($dpt != "225.002"){
					$value = $data[0];    
					if ($option != null){
						if ($option["ValInfField"] !='' /*&& is_numeric($data[4])&& $data[4]!=''*/){	
							//log::add('eibd', 'debug', 'Mise à jour de l\'objet Jeedom ValInfField: '.$option["ValInfField"]);
							$TimePeriode=cmd::byId(str_replace('#','',$option["TimePeriode"]));
							if (is_object($TimePeriode)){
								$valeur = $data[0] << 8 | $data[1];
								log::add('eibd', 'debug', 'L\'objet ' . $TimePeriode->getName() . ' a été trouvé et va être mis à jour avec la valeur ' . $valeur);
								$TimePeriode->event($valeur);
								$TimePeriode->setCache('collectDate', date('Y-m-d H:i:s'));
							}
						}
						
					}
				}
			break;
			case "229":
				if ($dpt != "229.001"){
					/*if ($value < 0)
					   $value = (abs($value) ^ 0xffffffff) + 1 ; 
					$ValInfField=cmd::byId(str_replace('#','',$option["ValInfField"]));
					$StatusCommande=cmd::byId(str_replace('#','',$option["StatusCommande"]));
					$data= array(($value>>24) & 0xFF, ($value>>16) & 0xFF,($value>>8) & 0xFF,$value & 0xFF,$ValInfField->execCmd(),$StatusCommande->execCmd());*/
					$value = $data[0] << 24 | $data[1] << 16 | $data[2] << 8 | $data[3] ;
					if ($value >= 0x80000000)
						$value = -(($value - 1) ^ 0xffffffff);  # invert twos complement       
					if ($option != null){
						//Mise à jour de l'objet Jeedom ValInfField
						if ($option["ValInfField"] !='' /*&& is_numeric($data[4])&& $data[4]!=''*/){	
							//log::add('eibd', 'debug', 'Mise à jour de l\'objet Jeedom ValInfField: '.$option["ValInfField"]);
							$ValInfField=cmd::byId(str_replace('#','',$option["ValInfField"]));
							if (is_object($ValInfField)){
								$valeur=$data[4];
								log::add('eibd', 'debug', 'L\'objet ' . $ValInfField->getName() . ' a été trouvé et va être mis à jour avec la valeur ' . $valeur);
								$ValInfField->event($valeur);
								$ValInfField->setCache('collectDate', date('Y-m-d H:i:s'));
							}
						}
						//Mise à jour de l'objet Jeedom StatusCommande
						if ($option["StatusCommande"] !='' /*&& is_numeric(($data[5]>>1) & 0x01)&& $data[5]!=''*/){
							//log::add('eibd', 'debug', 'Mise à jour de l\'objet Jeedom StatusCommande: '.$option["StatusCommande"]);
							$StatusCommande=cmd::byId(str_replace('#','',$option["StatusCommande"]));
							if (is_object($StatusCommande)){
								$valeur=($data[5]>>1) & 0x01;
								log::add('eibd', 'debug', 'L\'objet ' . $StatusCommande->getName() . ' a été trouvé et va être mis à jour avec la valeur ' . $valeur);
								$StatusCommande->event($valeur);
								$StatusCommande->setCache('collectDate', date('Y-m-d H:i:s'));
							}
						}
					}
				}
			break;
			case "232":
				$value= self::rgb2html($data[0],$data[1], $data[2]);
			break;
			case "235":
				if ($dpt == "235.001"){
					$value = $data[5] & 0x01;  
					if($value == 1)
					   break; 
					log::add('eibd', 'debug', 'La valeur de l\'énergie electrique est valide');
					$value=($data[5]>>1) & 0x01;
					if($value == 1)
					   break;
					log::add('eibd', 'debug', 'La valeur du tarif est valide');	
					if ($option != null){
						if ($option["ActiveElectricalEnergy"] !=''){	
							$ActiveElectricalEnergy=explode('|',$option["ActiveElectricalEnergy"]);
							$Tarif=$data[4];
							log::add('eibd', 'debug', 'Nous allons mettre à jour le tarif '. $Tarif);	
							$ActiveElectricalEnergyCommande=cmd::byId(str_replace('#','',$ActiveElectricalEnergy[$Tarif]));
							if (is_object($ActiveElectricalEnergyCommande)){
								$valeur =$data[0] << 24 | $data[1] << 16 | $data[2] << 8 | $data[3] ;
								if ($valeur >= 0x80000000)
									$valeur = -(($valeur - 1) ^ 0xffffffff);  # invert twos complement    
								log::add('eibd', 'debug', 'L\'objet ' . $ActiveElectricalEnergyCommande->getName() . ' a été trouvé et va être mis à jour avec la valeur ' . $valeur);
								$ActiveElectricalEnergyCommande->event($valeur);
								$ActiveElectricalEnergyCommande->setCache('collectDate', date('Y-m-d H:i:s'));
							}
						}
					}
				}
			break;
			case "251":
				$Temperature=cmd::byId(str_replace('#','',$option["Température"]));
				if (is_object($Temperature)/* && $data[5]&0x01*/){
					$valeur=$data[3];
					log::add('eibd', 'debug', 'L\'objet ' . $Temperature->getName() . ' a été trouvé et va être mis à jour avec la valeur ' . $valeur);
					$Temperature->event($valeur);
					$Temperature->setCache('collectDate', date('Y-m-d H:i:s'));
				}
				$value= self::rgb2html($data[0],$data[1], $data[2]);
			break;			
			case "Color":	
				$R=cmd::byId(str_replace('#','',$option["R"]));
				if(!is_object($R) && $R->getType() == 'info')
					return;
				$G=cmd::byId(str_replace('#','',$option["G"]));
				if(!is_object($G) && $G->getType() == 'info')
					return;
				$B=cmd::byId(str_replace('#','',$option["B"]));
				if(!is_object($B) && $B->getType() == 'info')
					return;
				$listener = listener::byClassAndFunction('eibd', 'UpdateCmdOption', $option);
				if (!is_object($listener)){
					$listener = new listener();
					$listener->setClass('eibd');
					$listener->setFunction('UpdateCmdOption');
					$listener->setOption($option);
					$listener->emptyEvent();
					$listener->addEvent($R->getId());
					$listener->addEvent($G->getId());
					$listener->addEvent($B->getId());
					$listener->save();
				}
				$value= self::rgb2html($R->execCmd(),$G->execCmd(),$B->execCmd());
			break;
			case "ABB_ControlAcces_Read_Write":
				$Read= EIS14_ABB_ControlAcces::ReadTag($data);
				if(!$Read)
					return false;
				list($value,$PlantCode,$Expire)=$Read;
				$isValidCode = false;
				/*
				foreach(explode("&&",$option["Group"]) as $Groupe){
					if(jeedom::evaluateExpression($Groupe) == $Groupe){
						$isValidCode= true;
						break;
					}
				}
				if(!$isValidCode){
					log::add('eibd','debug','{{Le badge ('.$value.')  n\'appartient à aucun groupe  ('.$Group.') }}');
					return false;
				}*/				
				foreach(explode("&&",$option["PlantCode"]) as $Plant){
					if(jeedom::evaluateExpression($Plant) == $PlantCode){
						$isValidCode= true;
						break;
					}
				}
				if(!$isValidCode){
					log::add('eibd', 'debug', '{{Le badge (' . $value . ') n\'appartient à aucun PlantCode (' . $PlantCode . ')}}');
					return false;
				}
// 				if(jeedom::evaluateExpression($option["Expire"]) > $Expire){
// 					log::add('eibd','debug','{{Le badge ('.$value.') a expiré ('.date("d/m/Y H:i:s",$Expire).')}}');
// 					return false;
// 				}
			break;	
			default:
				switch($dpt){
					case "x.001":
						$value = $data[0]& 0x01;      
						if ($option != null){
							//Mise à jour de l'objet Jeedom Mode
							if ($option["Mode"] !=''){		
								$Mode=cmd::byId(str_replace('#','',$option["Mode"]));
								if (is_object($Mode)){
									$Mode->event(($data[0]>>1) & 0xEF);
									$Mode->setCache('collectDate', date('Y-m-d H:i:s'));
								}
							}
						}
					break;		
				}
			break;
		};
		return $value;
	}
	public static function OtherValue ($dpt, $oldValue){
		$All_DPT=self::All_DPT();
		$type= substr($dpt,0,strpos( $dpt, '.' ));
		switch ($type){
			default:
				$value=$oldValue;
			break;
			case "1":
				if ($oldValue == 1)
					$value=0;
				else
					$value=1;
			break;
		}
		return $value;
	}
	private static function html2rgb($color){
		if ($color[0] == '#')
			$color = substr($color, 1);
		if (strlen($color) == 6)
			list($r, $g, $b) = array($color[0].$color[1],
		$color[2].$color[3],
		$color[4].$color[5]);
		elseif (strlen($color) == 3)
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		else
			return false;
		$r = hexdec($r); 
		$g = hexdec($g);
		$b = hexdec($b);
		return array($r, $g, $b);
	}
	private static function rgb2html($r, $g=-1, $b=-1)	{
		if (is_array($r) && sizeof($r) == 3)
			list($r, $g, $b) = $r;
		$r = intval($r); 
		$g = intval($g);
		$b = intval($b);
		
		$r = dechex($r<0?0:($r>255?255:$r));
		$g = dechex($g<0?0:($g>255?255:$g));
		$b = dechex($b<0?0:($b>255?255:$b));
		
		$color = (strlen($r) < 2?'0':'').$r;
		$color .= (strlen($g) < 2?'0':'').$g;
		$color .= (strlen($b) < 2?'0':'').$b;
		return '#'.$color;
	}
	public static function getDptUnite($dpt){
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["Unite"];
				next($Type);
				}
			next($All_DPT);
			}
		return '';
		}
	public static function getDptOption($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["Option"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public static function getDptActionType($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["ActionType"];
				next($Type);
				}
			next($All_DPT);
			}
		return 'other';
		}
	public static function getDptInfoType($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["InfoType"];
				next($Type);
				}
			next($All_DPT);
			}
		return 'string';
		}
	public static function getDptGenericType($dpt)	{
		$All_DPT=self::All_DPT();
		while ($Type = current($All_DPT))
			{
			while ($Dpt = current($Type)) 
				{	
				if ($dpt == key($Type))
					return $Dpt["GenericType"];
				next($Type);
				}
			next($All_DPT);
			}
		return ;
		}
	public static function getDptFromData($data)	{
		if(!is_array($data))
			return "1.xxx";
		switch(count($data)){
			case 1:
				return "5.xxx";
			break;
			case 2:
				return "9.xxx";
			break;
			case 3:
				return "10.xxx";
			break;
			case 4:
				return "14.xxx";
			break;
			default:
				return false;
			break;
		}
	}
	public static function All_DPT(){
		return array (
		"Boolean"=> array(
			"1.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0, 1),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.001"=> array(
				"Name"=>"Switch",
				"Valeurs"=>array("Off", "On"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.002"=> array(
				"Name"=>"Boolean",
				"Valeurs"=>array("False", "True"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.003"=> array(
				"Name"=>"Enable",
				"Valeurs"=>array("Disable", "Enable"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.004"=> array(
				"Name"=>"Ramp",
				"Valeurs"=>array("No ramp", "Ramp"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.005"=> array(
				"Name"=>"Alarm",
				"Valeurs"=>array("No alarm", "Alarm"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.006"=> array(
				"Name"=>"Binary value",
				"Valeurs"=>array("Low", "High"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.007"=> array(
				"Name"=>"Step",
				"Valeurs"=>array("Decrease", "Increase"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.008"=> array(
				"Name"=>"Up/Down",
				"Valeurs"=>array("Up", "Down"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.009"=> array(
				"Name"=>"Open/Close",
				"Valeurs"=>array("Open", "Close"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.010"=> array(
				"Name"=>"Start",
				"Valeurs"=>array("Stop", "Start"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.011"=> array(
				"Name"=>"State",
				"Valeurs"=>array("Inactive", "Active"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.012"=> array(
				"Name"=>"Invert",
				"Valeurs"=>array("Not inverted", "Inverted"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.013"=> array(
				"Name"=>"Dimmer send-style",
				"Valeurs"=>array("Start/stop", "Cyclically"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.014"=> array(
				"Name"=>"Input source",
				"Valeurs"=>array("Fixed", "Calculated"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.015"=> array(
				"Name"=>"Reset",
				"Valeurs"=>array("No action", "Reset"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.016"=> array(
				"Name"=>"Acknowledge",
				"Valeurs"=>array("No action", "Acknowledge"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.017"=> array(
				"Name"=>"Trigger",
				"Valeurs"=>array("Trigger", "Trigger"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.018"=> array(
				"Name"=>"Occupancy",
				"Valeurs"=>array("Not occupied", "Occupied"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.019"=> array(
				"Name"=>"Window/Door",
				"Valeurs"=>array("Closed", "Open"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.021"=> array(
				"Name"=>"Logical function",
				"Valeurs"=>array("OR", "AND"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.022"=> array(
				"Name"=>"Scene A/B",
				"Valeurs"=>array("Scene A", "Scene B"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"1.023"=> array(
				"Name"=>"Shutter/Blinds mode",
				"Valeurs"=>array("Only move Up/Down", "Move Up/Down + StepStop"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"1BitPriorityControl"=> array(
			"2.001"=> array(
				"Name"=>"DPT_Switch_Control",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.002"=> array(
				"Name"=>"DPT_Bool_Control",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.003"=> array(
				"Name"=>"DPT_Enable_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.004"=> array(
				"Name"=>"DPT_Ramp_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.005"=> array(
				"Name"=>"DPT_Alarm_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.006"=> array(
				"Name"=>"DPT_BinaryValue_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.007"=> array(
				"Name"=>"DPT_Step_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.010"=> array(
				"Name"=>"DPT_Start_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.011"=> array(
				"Name"=>"DPT_State_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"2.012"=> array(
				"Name"=>"DPT_Invert_Controll",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"3BitControl"=> array(
			"3.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0,1,2,3,4,5,6,7),
				"min"=>-7,
				"max"=>7,
				"InfoType"=>'numeric',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array("ctrl"),
				"Unite" =>""),
			"3.007"=> array(
				"Name"=>"Dimming",
				"Valeurs"=>array(0,1,2,3,4,5,6,7),
				"min"=>-7,
				"max"=>7,
				"InfoType"=>'numeric',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array("ctrl"),
				"Unite" =>"step"),
			"3.008"=> array(
				"Name"=>"Blinds",
				"Valeurs"=>array(0,1,2,3,4,5,6,7),
				"min"=>-7,
				"max"=>7,
				"InfoType"=>'numeric',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array("ctrl"),
				"Unite" =>"step")),
		"8BitUnsigned"=> array(
			"5.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 255,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"5.001"=> array(
				"Name"=>"Scaling",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>100,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"5.003"=> array(
				"Name"=>"Angle",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>360,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°"),
			"5.004"=> array(
				"Name"=>"Percent (8 bit)",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 255,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"5.005"=> array(
				"Name"=>"Decimal factor",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>1,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ratio"),
			"5.006"=> array(
				"Name"=>"Tariff",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 254,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>""),
			"5.010"=> array(
				"Name"=>"Unsigned count",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 255,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses")),
		"8BitSigned"=> array(
			"6.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=> -128,
				"max"=> 127,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"6.001"=> array(
				"Name"=>"Percent (8 bit)",
				"Valeurs"=>array(),
				"min"=> -128,
				"max"=> 127,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"6.010"=> array(
				"Name"=>"Signed count",
				"Valeurs"=>array(),
				"min"=>-128,
				"max"=>127,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses")),	
		"2ByteUnsigned"=> array(
			"7.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"7.001"=> array(
				"Name"=>"Unsigned count",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses"),
			"7.002"=> array(
				"Name"=>"Time period (resol. 1ms)",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"7.003"=> array(
				"Name"=>"Time period (resol. 10ms)",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 655350,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"7.004"=> array(
				"Name"=>"Time period (resol. 100ms)",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 6553500,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"7.005"=> array(
				"Name"=>"Time period (resol. 1s)",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"7.006"=> array(
				"Name"=>"Time period (resol. 1min)",
				"Valeurs"=>array(),
				"min"=> 0,
				"max"=> 65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"min"),
			"7.007"=> array(
				"Name"=>"Time period (resol. 1h)",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"h"),
			"7.010"=> array(
				"Name"=>"Interface object property ID",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"7.011"=> array(
				"Name"=>"Length",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mm"),
			"7.012"=> array(
				"Name"=>"Electrical current",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mA"),  # Add special meaning for 0 (create Limit object)
			"7.013"=> array(
				"Name"=>"Brightness",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lx"),
			"7.600"=> array(
				"Name"=>"Température de blanc",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>65535,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lx")),
		"2ByteSigned"=> array(
			"8.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"8.001"=> array(
				"Name"=>"Signed count",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses"),
			"8.002"=> array(
				"Name"=>"Delta time (ms)",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"8.003"=> array(
				"Name"=>"Delta time (10ms)",
				"Valeurs"=>array(),
				"min"=>-3276800,
				"max"=>3276700,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"8.004"=> array(
				"Name"=>"Delta time (100ms)",
				"Valeurs"=>array(),
				"min"=>-3276800,
				"max"=>3276700,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"8.005"=> array(
				"Name"=>"Delta time (s)",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"8.006"=> array(
				"Name"=>"Delta time (min)",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"min"),
			"8.007"=> array(
				"Name"=>"Delta time (h)",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"h"),
			"8.010"=> array(
				"Name"=>"Percent (16 bit)",
				"Valeurs"=>array(),
				"min"=>-327.68,
				"max"=>327.67,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"8.011"=> array(
				"Name"=>"Rotation angle",
				"Valeurs"=>array(),
				"min"=>-32768,
				"max"=>32767,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°")),
		"2ByteFloat"=> array(
			"9.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>-671088.64,
				"max"=>670760.96,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"9.001"=> array(
				"Name"=>"Temperature",
				"Valeurs"=>array(),
				"min"=>-273,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°C"),
			"9.002"=> array(
				"Name"=>"Temperature difference",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K"),
			"9.003"=> array(
				"Name"=>"Temperature gradient",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K/h"),
			"9.004"=> array(
				"Name"=>"Luminous emittance",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lx"),
			"9.005"=> array(
				"Name"=>"Wind speed",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m/s"),
			"9.006"=> array(
				"Name"=>"Air pressure",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Pa"),
			"9.007"=> array(
				"Name"=>"Humidity",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"%"),
			"9.008"=> array(
				"Name"=>"Air quality",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ppm"),
			"9.010"=> array(
				"Name"=>"Time difference 1",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"9.011"=> array(
				"Name"=>"Time difference 2",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"ms"),
			"9.020"=> array(
				"Name"=>"Electrical voltage",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mV"),
			"9.021"=> array(
				"Name"=>"Electric current",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mA"),
			"9.022"=> array(
				"Name"=>"Power density",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W/m²"),
			"9.023"=> array(
				"Name"=>"Kelvin/percent",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K/%"),
			"9.024"=> array(
				"Name"=>"Power",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kW"),
			"9.025"=> array(
				"Name"=>"Volume flow",
				"Valeurs"=>array(),
				"min"=> -670760,
				"max"=> 670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"l/h"),
			"9.026"=> array(
				"Name"=>"Rain amount",
				"Valeurs"=>array(),
				"min"=>-670760,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"l/m²"),
			"9.027"=> array(
				"Name"=>"Temperature (°F)",
				"Valeurs"=>array(),
				"min"=>-459.6,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°F"),
			"9.028"=> array(
				"Name"=>"Wind speed (km/h)",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>670760,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"km/h")),
		"Time"=> array(
			"10.xxx"=> array(
				"Name"=>"Generic", 
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>16777215,
				"InfoType"=>'string',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"10.001"=> array(
				"Name"=>"Time of day",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Date"=> array(
			"11.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>16777215,
				"InfoType"=>'string',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"11.001"=> array(
				"Name"=>"Date",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"4ByteUnsigned"=> array(
			"12.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>4294967295,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"12.001"=> array(
				"Name"=>"Unsigned count",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>4294967295,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses")),
		"4ByteSigned"=> array(
			"13.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=> -2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"13.001"=> array(
				"Name"=>"Signed count",
				"Valeurs"=>array(),
				"min"=> -2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"pulses"),
			"13.001"=> array(
				"Name"=>"Flow rate",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m³/h"),
			"13.010"=> array(
				"Name"=>"Active energy",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W.h"),
			"13.011"=> array(
				"Name"=>"Apparent energy",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"VA.h"),
			"13.012"=> array(
				"Name"=>"Reactive energy",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"VAR.h"),
			"13.013"=> array(
				"Name"=>"Active energy (kWh)",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kW.h"),
			"13.014"=> array(
				"Name"=>"Apparent energy (kVAh)",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kVA.h"),
			"13.015"=> array(
				"Name"=>"Reactive energy (kVARh)",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=> 2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kVAR.h"),
			"13.100"=> array(
				"Name"=>"Long delta time",
				"Valeurs"=>array(),
				"min"=>-2147483648,
				"max"=>2147483647,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s")),
		"4ByteFloat"=> array(
			"14.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"14.000"=> array(
				"Name"=>"Acceleration",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m/s²"),
			"14.001"=> array(
				"Name"=>"Acceleration, angular",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad/s²"),
			"14.002"=> array(
				"Name"=>"Activation energy",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J/mol"),
			"14.003"=> array(
				"Name"=>"Activity (radioactive)",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s⁻¹"),
			"14.004"=> array(
				"Name"=>"Amount of substance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"mol"),
			"14.005"=> array(
				"Name"=>"Amplitude",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"14.006"=> array(
				"Name"=>"Angle, radiant",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad"),
			"14.007"=> array(
				"Name"=>"Angle, degree",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°"),
			"14.008"=> array(
				"Name"=>"Angular momentum",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J.s"),
			"14.009"=> array(
				"Name"=>"Angular velocity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad/s"),
			"14.010"=> array(
				"Name"=>"Area",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m²"),
			"14.011"=> array(
				"Name"=>"Capacitance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"F"),
			"14.012"=> array(
				"Name"=>"Charge density (surface)",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.013"=> array(
				"Name"=>"Charge density (volume)",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m³"),
			"14.014"=> array(
				"Name"=>"Compressibility",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m²/N"),
			"14.015"=> array(
				"Name"=>"Conductance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"S"),
			"14.016"=> array(
				"Name"=>"Conductivity, electrical",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"S/m"),
			"14.017"=> array(
				"Name"=>"Density",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kg/m³"),
			"14.018"=> array(
				"Name"=>"Electric charge",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C"),
			"14.019"=> array(
				"Name"=>"Electric current",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A"),
			"14.020"=> array(
				"Name"=>"Electric current density",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A/m²"),
			"14.021"=> array(
				"Name"=>"Electric dipole moment",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Cm"),
			"14.022"=> array(
				"Name"=>"Electric displacement",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.023"=> array(
				"Name"=>"Electric field strength",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V/m"),
			"14.024"=> array(
				"Name"=>"Electric flux",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"c"),  # unit??? C
			"14.025"=> array(
				"Name"=>"Electric flux density",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.026"=> array(
				"Name"=>"Electric polarization",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"C/m²"),
			"14.027"=> array(
				"Name"=>"Electric potential",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V"),
			"14.028"=> array(
				"Name"=>"Electric potential difference",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V"),
			"14.029"=> array(
				"Name"=>"Electromagnetic moment",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A.m²"),
			"14.030"=> array(
				"Name"=>"Electromotive force",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V"),
			"14.031"=> array(
				"Name"=>"Energy",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J"),
			"14.032"=> array(
				"Name"=>"Force",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N"),
			"14.033"=> array(
				"Name"=>"Frequency",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Hz"),
			"14.034"=> array(
				"Name"=>"Frequency, angular (pulsatance)",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad/s"),
			"14.035"=> array(
				"Name"=>"Heat capacity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J/K"),
			"14.036"=> array(
				"Name"=>"Heat flow rate",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W"),
			"14.037"=> array(
				"Name"=>"Heat quantity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J"),
			"14.038"=> array(
				"Name"=>"Impedance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm"),
			"14.039"=> array(
				"Name"=>"Length",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m"),
			"14.040"=> array(
				"Name"=>"Light quantity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J"),
			"14.041"=> array(
				"Name"=>"Luminance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"cd/m²"),
			"14.042"=> array(
				"Name"=>"Luminous flux",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"lm"),
			"14.043"=> array(
				"Name"=>"Luminous intensity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"cd"),
			"14.044"=> array(
				"Name"=>"Magnetic field strengh",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A/m"),
			"14.045"=> array(
				"Name"=>"Magnetic flux",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Wb"),
			"14.046"=> array(
				"Name"=>"Magnetic flux density",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"T"),
			"14.047"=> array(
				"Name"=>"Magnetic moment",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A.m²"),
			"14.048"=> array(
				"Name"=>"Magnetic polarization",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"T"),
			"14.049"=> array(
				"Name"=>"Magnetization",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A/m"),
			"14.050"=> array(
				"Name"=>"Magnetomotive force",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"A"),
			"14.051"=> array(
				"Name"=>"Mass",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kg"),
			"14.052"=> array(
				"Name"=>"Mass flux",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"kg/s"),
			"14.053"=> array(
				"Name"=>"Momentum",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N/s"),
			"14.054"=> array(
				"Name"=>"Phase angle, radiant",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"rad"),
			"14.055"=> array(
				"Name"=>"Phase angle, degree",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°"),
			"14.056"=> array(
				"Name"=>"Power",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W"),
			"14.057"=> array(
				"Name"=>"Power factor",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"cos phi"),
			"14.058"=> array(
				"Name"=>"Pressure",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Pa"),
			"14.059"=> array(
				"Name"=>"Reactance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm"),
			"14.060"=> array(
				"Name"=>"Resistance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm"),
			"14.061"=> array(
				"Name"=>"Resistivity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Ohm.m"),
			"14.062"=> array(
				"Name"=>"Self inductance",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"H"),
			"14.063"=> array(
				"Name"=>"Solid angle",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"sr"),
			"14.064"=> array(
				"Name"=>"Sound intensity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W/m²"),
			"14.065"=> array(
				"Name"=>"Speed",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m/s"),
			"14.066"=> array(
				"Name"=>"Stress",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"Pa"),
			"14.067"=> array(
				"Name"=>"Surface tension",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N/m"),
			"14.068"=> array(
				"Name"=>"Temperature, common",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"°C"),
			"14.069"=> array(
				"Name"=>"Temperature, absolute",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K"),
			"14.070"=> array(
				"Name"=>"Temperature difference",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"K"),
			"14.071"=> array(
				"Name"=>"Thermal capacity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J/K"),
			"14.072"=> array(
				"Name"=>"Thermal conductivity",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"W/m/K"),
			"14.073"=> array(
				"Name"=>"Thermoelectric power",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"V/K"),
			"14.074"=> array(
				"Name"=>"Time",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"s"),
			"14.075"=> array(
				"Name"=>"Torque",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N.m"),
			"14.076"=> array(
				"Name"=>"Volume",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m³"),
			"14.077"=> array(
				"Name"=>"Volume flux",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"m³/s"),
			"14.078"=> array(
				"Name"=>"Weight",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"N"),
			"14.079"=> array(
				"Name"=>"Work",
				"Valeurs"=>array(),
				"min"=>-3.4028234663852886e+38,
				"max"=> 3.4028234663852886e+38,
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"Unite"=>"J")),
		"String"=> array(
			"16.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>0,
				"max"=>5192296858534827628530496329220095,
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"16.000"=> array(
				"Name"=>"String",
				"Valeurs"=>array(/*14 * (0,), 14 * (127,)*/),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"16.001"=> array(
				"Name"=>"String",
				"Valeurs"=>array(/*14 * (0,), 14 * (255,)*/),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Scene"=> array(
			"17.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"17.001"=> array(
				"Name"=>"Scene",
				"Valeurs"=>array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Scene Control"=> array(
			"18.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array("ctrl"),
				"Unite" =>""),
			"18.001"=> array(
				"Name"=>"Scene Control",
				"Valeurs"=>array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array("ctrl"),
				"Unite" =>"")),
		"DateTime"=> array(
			"19.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"19.001"=> array(
				"Name"=>"DateTime",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"8BitEncAbsValue"=> array(
			"20.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>'0',
				"max"=>'255',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.003"=> array(
				"Name"=>"Occupancy mode",
				"Valeurs"=>array("occupied","standby","not occupied"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.102"=> array(
				"Name"=>"Heating mode",
				"Valeurs"=>array("Auto","Comfort","Standby","Night","Frost"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.102_2"=> array(
				"Name"=>"MDT Heating mode",
				"Valeurs"=>array(0=>"Auto",1=>"Comfort",2=>"Standby",4=>"Night",8=>"Frost"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"20.105"=> array(
				"Name"=>"Heating controle mode",
				"Valeurs"=>array("Auto","Heat","Morning Warmup","Cool","Night Purge","Precool","Off","Test","Emergency Heat","Fan only","Free Cool","Ice","Maximum Heating Mode","Economic Heat/Cool Mode","Dehumidification","Calibration Mode","Emergency Cool Mode","Emergency Steam Mode"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"2bit"=> array(
			"23.xxx"=> array(
				"Name"=>"Generic",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"23.001"=> array(
				"Name"=>"OnOffAction",
				"Valeurs"=>array("On","Off","Off/On","On/Off"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"23.002"=> array(
				"Name"=>"Alarm Reaction",
				"Valeurs"=>array("no alarm is used","alarm position is UP","alarm position is DOWN"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"23.003"=> array(
				"Name"=>"UpDown Action",
				"Valeurs"=>array("Up","Down","UpDown","DownUp"),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'select',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"3Bytes"=> array(
			"232.600"=> array(
				"Name"=>"Colour RGB",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'color',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>"")),
		"Other"=> array(
			"27.001"=> array(
				"Name"=>"Combined info On/Off",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'binary',
				"ActionType"=>'other',
				"GenericType"=>"DONT",
				"Option" =>array("Info"),
				"Unite" =>""),
			"225.002"=> array(
				"Name"=>"Scaling step time",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array("TimePeriode"),
				"Unite" =>""),
			"229.001"=> array(
				"Name"=>"Metering value",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array("ValInfField","StatusCommande"),
				"Unite" =>""),
			"235.001"=> array(
				"Name"=>"Tarif ActiveEnergy",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array("ActiveElectricalEnergy"),
				"Unite" =>""),
			"237.600"=> array(
				"Name"=>"DALI_Control_Gear_Diagnostic",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'numeric',
				"ActionType"=>'slider',
				"GenericType"=>"DONT",
				"Option" =>array(),
				"Unite" =>""),
			"251.600"=> array(
				"Name"=>"Colour RGBW",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'color',
				"GenericType"=>"DONT",
				"Option" =>array("Température"),
				"Unite" =>"")),
		"Spécifique"=> array(
			"x.001"=> array(
				"Name"=>"Hager Etat/Mode",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array("Mode"),
				"Unite" =>""),
			"Color"=> array(
				"Name"=>"Gestion des couleur (RGB / HTML)",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'color',
				"GenericType"=>"DONT",
				"Option" =>array("R","G","B"),
				"Unite" =>"")),
		"ABB - Acces Control"=> array(
			"ABB_ControlAcces_Read_Write"=> array(
				"Name"=>"Read/Write code Tag",
				"Valeurs"=>array(),
				"min"=>'',
				"max"=>'',
				"InfoType"=>'string',
				"ActionType"=>'message',
				"GenericType"=>"DONT",
				"Option" =>array("Group","PlantCode","Expire"),
				"Unite" =>""))
		);
	}
}?>