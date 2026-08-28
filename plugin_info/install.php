<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
/*
 * Depuis specific.config.ini, le type de passerelle par défaut sur Atlas (Pro ou pas) est maintenant ft12cemi (KNX intégré sur /dev/ttyS2).
 * Le cœur Jeedom ne stocke pas en base une valeur égale au défaut : les installations Atlas qui utilisaient
 * l'ancien défaut (ipt) avec une passerelle IP se retrouvent donc en ft12cemi après mise à jour.
 * Cela casse silencieusement les configurations, si l'utilisateur n'est pas averti.
 * On rétablit ipt explicitement quand la passerelle configurée avant la mise à jour n'est pas un périphérique série.
 */
function eibd_migrateGatewayType() {
	/*
	Si la config ne renvoie pas ft12cemi comme Type de passerelle,
	-On est sur une box non atlas avec l'ancien défaut, ou avec un TypeKNXgateway persisté
	-On est sur une Atlas avec un type différent du nouveau défaut persisté
	On ne fait rien.
	*/
	if(config::byKey('TypeKNXgateway', 'eibd') != 'ft12cemi')
		return;

	/*
	Si aucune passerelle n'est configurée, c'est une installation propre, on ne fait rien.
	*/
	$gateway = trim(config::byKey('KNXgateway', 'eibd'));
	if($gateway == '')
		return;

	/*
	Si la passerelle commence par un slash, c'est une interface série physique sur une Atlas ou un adaptateur USB, etc.
	On laisse ft12cemi qui est cohérent.
	*/
	if(strpos($gateway, '/') === 0)
		return;

	/*
	Sinon, on a une IP ou un nom d'hôte comme passerelle, mais un Type de passerelle sur ft12cemi.
	Il faut agir, et redéfinir explicitement l'ancien défaut ipt comme type de passerelle
	pour que ça soit persisté en base et pas écrasé par la mise à jour.
	*/
	config::save('TypeKNXgateway', 'ipt', 'eibd');
	log::add('eibd','info','[Mise à jour] Type de passerelle rétabli en IP Tunnelling (ipt) pour la passerelle '.$gateway);
}
function eibd_update() {
	log::add('eibd','debug','Lancement du script de mise à jour');
	//On appelle notre fonction de vérification
	eibd_migrateGatewayType();
	$oldPath = dirname(__FILE__) . '/../core/config/';
	$File = 'KnxProj.json';
	if(file_exists($oldPath.$File)){
		$dataPath = dirname(__FILE__) . '/../data/';
		if (!is_dir($dataPath)) 
			mkdir($dataPath);
		exec('sudo chmod -R 777 '.$dataPath);
		if(!file_exists($dataPath.$File))
			exec('sudo mv '.$oldPath.$File.' '.$dataPath.$File);
		exec('sudo rm '.$oldPath.$File);
	}
	if(exec("command -v eibd") !='')
		config::save('KnxSoft', 'eibd','eibd');
	else{
		exec("sudo systemctl stop knxd.service");
		exec("sudo systemctl stop knxd.socket"); 
		exec("sudo systemctl disable knxd.service");
		exec("sudo systemctl disable knxd.socket"); 
	}
	if(config::byKey('KnxSoft', 'eibd') == 'eibd')
		exec('sudo usermod -a -G www-data eibd');
	if(config::byKey('KnxSoft', 'eibd') == 'knxd')
		exec('sudo usermod -a -G www-data knxd');
	while(is_object($listener=listener::byClassAndFunction('eibd', 'TransmitValue')))
		$listener->remove();
	foreach(eqLogic::byType('eibd') as $eqLogic){
		switch($eqLogic->getConfiguration('typeTemplate')){
				
			case 'bso':
				$eqLogic->setConfiguration('typeTemplate','shutter_BSO');
			break;
			case 'thermostat_ver':
				$eqLogic->setConfiguration('typeTemplate','thermostat_verrou');
			break;
			case 'dimmerRGB':
				$eqLogic->setConfiguration('typeTemplate','light_dimmer_rgb');
			break;
			case 'RGBW':
				$eqLogic->setConfiguration('typeTemplate','light_dimmer_rgb');
			break;
			case 'dimmer':
				$eqLogic->setConfiguration('typeTemplate','light_dimmer');
			break;
		}
		$eqLogic->save();
	}
	log::add('eibd','debug','Fin du script de mise à jour'); 
}
function eibd_remove() {
	while(is_object($listener=listener::byClassAndFunction('eibd', 'TransmitValue')))
		$listener->remove();
}
?>
