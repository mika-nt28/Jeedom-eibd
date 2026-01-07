<?php
try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');
	include_file('core', 'dpt', 'class', 'eibd');
	include_file('core', 'knxproj', 'class', 'eibd');
	include_file('core', 'autoCreate', 'class', 'eibd');
	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}
	switch(init('action')){
		case 'setIsInclude':
			ajax::success(cache::set('eibd::isInclude',init('value'), 0));
		break;
		case 'getIsInclude':
			ajax::success(cache::byKey('eibd::isInclude')->getValue(false));
		break;
		case 'getLog':
			exec('sudo journalctl -u knxd.service -n 30 -o json',$result);
			foreach($result as $log)
				$return[] = json_decode($log,true);
			ajax::success($return);
		break;
		case 'SearchGatway':
			switch(init('type')){
				case 'ip':
				case 'ipt':
				case 'iptn':
					$result=eibd::SearchBroadcastGateway();
				break;
				/*case 'ft12':
				break;
				case 'bcu1':
				break;
				case 'tpuarts':
				break;*/
				case 'usb':
					$result=eibd::SearchUsbGateway();
				break;
				default:
					ajax::success(false);
				break;
			}
			ajax::success($result);
		break;
		case 'Read':
			$Commande=cmd::byLogicalId(init('Gad'))[0];
			if (is_object($Commande))
				ajax::success($Commande->execute(array('init'=>true)));
			else
				ajax::success(false);
		break;
		case 'getCacheGadInconue':
			ajax::success(cache::byKey('eibd::CreateNewGad')->getValue('[]'));
		break;
		case 'setCacheGadInconue':
			if(init('gad') == ""){
				cache::set('eibd::CreateNewGad', '[]', 0);
			}else{
				$cache = cache::byKey('eibd::CreateNewGad');
				$value = json_decode($cache->getValue('[]'), true);
				foreach ($value as $key => $val) {
				       if ($val['AdresseGroupe'] == init('gad')){
					       unset($value[$key]);
					       array_shift($value);
				       }
				}
				cache::set('eibd::CreateNewGad', json_encode($value), 0);
			}
			ajax::success('');
		break;
		case 'EtsParser':
			if(isset($_FILES['Knxproj'])){ 
				$uploaddir = '/tmp/KnxProj/';
				if (!is_dir($uploaddir)) 
					mkdir($uploaddir);
				$uploadfile = $uploaddir.basename($_FILES['Knxproj']['name']);
				$ext = pathinfo($_FILES['Knxproj']['name'], PATHINFO_EXTENSION);
				if(move_uploaded_file($_FILES['Knxproj']['tmp_name'], $uploadfile)){
					if($ext == 'gz')
						knxproj::ExtractTX100ProjectFile($uploadfile);
					else
						knxproj::ExtractETSProjectFile($uploadfile);
					ajax::success(true);
				}else
					ajax::success(false);
			}
		break;
		case 'AnalyseEtsProj':
			$knxproj=new knxproj(init('merge'),init('ProjetType'));
			$knxproj->WriteJsonProj();
			ajax::success(json_decode($knxproj->getAll(),true));
		break;
		case 'getEtsProj':
			$filename=dirname(__FILE__) . '/../../data/KnxProj.json';
			if (file_exists($filename))
				ajax::success(json_decode(file_get_contents($filename),true));
			ajax::success(false);
		break;
		case 'autoCreate':
			$autoCreate = new autoCreate(init('option'));
			$autoCreate->CheckOptions();
			ajax::success(true);
		break;
		case 'getTemplate':
			ajax::success(eibd::devicesParameters()[init('template')]);
		break;
		case 'CreateObject':
			$Object = jeeObject::byName(init('name')); 
			if (!is_object($Object)) {
				log::add('eibd','info','[Import ETS] Nous allons créer l\'objet : '.init('name'));
				$Object = new jeeObject(); 
				$Object->setName(init('name'));
				$Object->setIsVisible(true);
				$Object->save();
			}
			ajax::success(true);
		break;
	}
	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
?>
