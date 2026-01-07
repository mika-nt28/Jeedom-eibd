<?php
require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>
 <div>
	<form class="form-horizontal">
		<legend><i class="fas fa-archive"></i> {{Connexion au bus KNX}}</legend>
		<fieldset>
			<div class="form-group">
				<label class="col-lg-4 control-label" >
					{{Interface de communication :}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Choisir le démon de connexion au réseau KNX.}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<select class="configKey form-control" data-l1key="KnxSoft" >
						<option value="knxd">KNXD</option>
						<option value="manual">Manuel</option>
					</select>
				</div>
			</div>
			<div class="form-group NoSoft">
				<label class="col-lg-4 control-label">
					{{Adresse IP :}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Saisir l'adresse IP du démon distant.}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<input class="configKey form-control" data-l1key="EibdHost" />
				</div>
			</div>
			<div class="form-group NoSoft">
				<label class="col-lg-4 control-label">
					{{Port :}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Saisir le port du démon distant.}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<input class="configKey form-control" data-l1key="EibdPort" />
				</div>
			</div>
		</fieldset>
	</form>
</div>
 <div>
	<form class="form-horizontal">
		<legend><i class="fas fa-address-card"></i> {{Options}}</legend>
		<fieldset>
			<div class="form-group">
				<label class="col-lg-4 control-label" >
					{{Niveau de GAD}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Choisir le nombre de niveaux de votre configuration ETS.}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<select class="configKey form-control" data-l1key="level">
						<option value="1">{{GAD à 1 niveau}}</option>
						<option value="2">{{GAD à 2 niveaux}}</option>
						<option value="3">{{GAD à 3 niveaux}}</option>
					</select>
				</div>
			</div>
		</fieldset>
	</form>
</div>
 <div class="Soft">
	<form class="form-horizontal">
		<legend><i class="icon fas fa-cog"></i> {{Configuration du démon}}</legend>
		<fieldset>
			<div class="form-group">
				<label class="col-lg-4 control-label" >
					{{Type de passerelle}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Choisir le type de passerelle.}}"></i>
					</sup>
				</label>
				<div class="col-lg-4">
					<select class="configKey form-control" data-l1key="TypeKNXgateway" >
						<option value="ft12cemi">{{FT12 - CEMI}}</option>
						<option value="ft12">{{FT12 - Ligne série}}</option>
						<option value="bcu1s">{{BCU1 - kernel driver}}</option>
						<option value="tpuart">{{TPUART - kernel driver Linux 2.6}}</option>
						<option value="ip">{{IP - EIBnet/IP Routing protocol}}</option>
						<option value="ipt">{{IPT - EIBnet/IP Tunneling protocol}}</option>
						<option value="iptn">{{IPTN - EIBnet/IP NAT mode}}</option>
						<option value="usb">{{USB - KNX USB interface}}</option>
					</select>
				</div>
			</div>
			<div class="form-group">
				<label class="col-lg-4 control-label">
					{{Adresse de la passerelle}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Saisir l'adresse IP de votre passerelle.}}"></i>
					</sup>
				</label>
				<div class="col-lg-4 KNXgateway">
					<div class="input-group">
						<input class="configKey form-control input-sm roundedLeft tooltipstered" data-l1key="KNXgateway" placeholder="{{Adresse IP de la passerelle}}">
						<span class="input-group-addon roundedLeft KNXgatewayPort">:</span>
						<input class="configKey form-control input-sm roundedLeft tooltipstered KNXgatewayPort" data-l1key="KNXgatewayPort" placeholder="{{Port de la passerelle}}">
						<span class="input-group-btn roundedRight">
							<a class="btn btn-primary btn-sm SearchGatway">
								<i class="fas fa-search">{{Rechercher}}</i>
							</a>
						</span>
					</div>
					<div class="input-group KNXNAT">
						<input class="configKey form-control input-sm roundedLeft tooltipstered" data-l1key="KNXIPNAT" placeholder="{{Adresse IP NAT}}">
						<span class="input-group-addon roundedLeft ">:</span>
						<input class="configKey form-control input-sm roundedLeft tooltipstered" data-l1key="KNXPORTNAT" placeholder="{{Port NAT}}">
					</div>
					<div class="KNXgatewayFind"></div>
				</div>
			</div>
		</fieldset>
	</form>
</div>
 <div class="Soft">
	<form class="form-horizontal">
		<legend><i class="icon fas fa-cog"></i> {{Configuration avancée du démon}} <i class="fas fa-plus-circle" data-toggle="collapse" href="#OptionsCollapse" role="button" aria-expanded="false" aria-controls="OptionsCollapse"></i></legend>
		<fieldset>
			<div class="collapse" id="OptionsCollapse">
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Adresse multicaste du serveur KNX}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Saisir l'adresse de connexion multicast de votre réseau KNX}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<div class="input-group">
							<input class="configKey form-control input-sm roundedLeft tooltipstered" data-l1key="multicast-address" placeholder="{{Adresse IP MULTICAST}}">
							<span class="input-group-addon roundedLeft">:</span>
							<input class="configKey form-control input-sm roundedLeft tooltipstered" data-l1key="multicast-port" placeholder="{{Port MULTICAST}}	">
						</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Nom du serveur KNX}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Nom visible sous ETS}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input class="configKey form-control" data-l1key="ServeurName" placeholder="{{Nom du serveur KNX}}"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Adresse physique du démon}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Saisir une adresse physique libre sur votre réseau KNX.}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input class="configKey form-control" data-l1key="EibdGad" placeholder="{{Adresse physique du démon}}"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Nombres de connexions autorisé sur le serveur du démon}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Saisir le nombre de connexions maximum sur le serveur Jeedom. Attention les n adresses physiques suivant l'adresse précédemment configurée doivent être également libres}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input class="configKey form-control" data-l1key="EibdNbAddr" placeholder="{{Nombre de connexions simultanées}}"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Filtre serveur KNX}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Voulez-vous activer les filtres du serveur Jeedom (Obligatoire pour le téléchargement ETS) ?}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input type="checkbox" class="configKey tooltips" data-l1key="Filter"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Visibilité du serveur KNX}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Voulez-vous que le serveur Jeedom soit visible sous ETS ?}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input type="checkbox" class="configKey tooltips" data-l1key="Discovery"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Mode Routing}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Activer le mode Routing sur le serveur.}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input type="checkbox" class="configKey tooltips" data-l1key="Routing"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Mode Tunnelling}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Activer le mode Tunnelling sur le serveur.}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input type="checkbox" class="configKey tooltips" data-l1key="Tunnelling"/>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-4 control-label">
						{{Temps d'attente entre 2 envois (ms)}}
						<sup>
							<i class="fa fa-question-circle tooltips" title="{{Saisir un temps d'attente en milliseconde entre 2 envois.}}"></i>
						</sup>
					</label>
					<div class="col-lg-4">
						<input class="configKey form-control" data-l1key="SendSleep" placeholder="{{Temps d'attente entre 2 envois (ms)}}"/>
					</div>
				</div>
			</div>
		</fieldset>
	</form>
</div>
<script>
$('.configKey[data-l1key=KnxSoft]').off().on('change',function(){
	switch($(this).val()){
		case 'knxd':
			$('.configKey[data-l1key=EibdHost]').val('127.0.0.1');
			$('.configKey[data-l1key=EibdPort]').val('6720');
			$('.Soft').show();
			$('.NoSoft').hide();
		break;
		default:
			$('.Soft').hide();
			$('.NoSoft').show();
		break;
	}
});
$('.configKey[data-l1key=TypeKNXgateway]').off().on('change',function(){
	switch($('.configKey[data-l1key=TypeKNXgateway]').val()){
		case 'ip':
			$('.KNXgateway').closest('.form-group').hide();
		break;
		case 'ipt':
			$('.KNXNAT').hide();
			$('.KNXgateway').closest('.form-group').show()
			$('.KNXgatewayPort').show();
			$('.SearchGatway').closest('.input-group-btn').show();
		break;
		case 'iptn':
			$('.KNXNAT').show();
			$('.KNXgateway').closest('.form-group').show()
			$('.KNXgatewayPort').show();
			$('.SearchGatway').closest('.input-group-btn').show();
		break;
		case 'usb':
			$('.KNXNAT').hide();
			$('.KNXgateway').closest('.form-group').show()
			$('.KNXgatewayPort').hide();
			$('.SearchGatway').closest('.input-group-btn').show();
		break;
		default:
			$('.KNXNAT').hide();
			$('.KNXgateway').closest('.form-group').show()
			$('.KNXgatewayPort').hide();
			$('.SearchGatway').closest('.input-group-btn').hide();
		break;
	}
});
$('.SearchGatway').off().on('click',function(){
	$.ajax({
		type: 'POST',
		url: 'plugins/eibd/core/ajax/eibd.ajax.php',
		data:
			{
			action: 'SearchGatway',
			type: $('.configKey[data-l1key=TypeKNXgateway]').val(),
			},
		dataType: 'json',
		async: true,
		global: true,
		error: function(request, status, error) {},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			if(data.result){
				var format = '';
				switch($('.configKey[data-l1key=TypeKNXgateway]').val()){
					case 'ipt':
					case 'iptn':
						var Detect = $('<tbody>');
						$.each(data.result,function(index, value){
							Detect.append($('<tr>')
								.append($('<td class="DeviceName">')
									.append(value.DeviceName))
								.append($('<td class="IndividualAddressGateWay">')
									.append(value.IndividualAddressGateWay))
								.append($('<td class="KnxIpGateway">')
									.append(value.KnxIpGateway))
								.append($('<td class="KnxPortGateway">')
									.append(value.KnxPortGateway)));
						});
						$('.KNXgatewayFind').find('#table_KNXgateway').remove();
						$('.KNXgatewayFind').append($('<table id="table_KNXgateway" class="table table-bordered table-condensed ui-sortable">')
							.append($('<thead>')
								.append($('<tr>')
									.append($('<th>')
										.append('{{Nom}}'))
									.append($('<th>')
										.append('{{Adresse physique}}'))
									.append($('<th>')
										.append('{{IP}}'))
									.append($('<th>')
										.append('{{Port}}'))))
							.append(Detect));
						$('#table_KNXgateway tbody tr').off().on('click',function(){
							$('.configKey[data-l1key=KNXgateway]').val($(this).find('.KnxIpGateway').text());
							$('.configKey[data-l1key=KNXgatewayPort]').val($(this).find('.KnxPortGateway').text());
						});
					break;
					case 'usb':
						$('.configKey[data-l1key=KNXgateway]').val(data.result);
					break;
				}
			}
		}
	});
});
	
</script>
