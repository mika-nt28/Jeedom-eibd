<?php
if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
include_file('desktop', 'ETSparse', 'js', 'eibd');
include_file('desktop', 'autoCreate', 'js', 'eibd');
include_file('desktop', 'arborescenceCreate', 'js', 'eibd');
sendVarToJS('templates',eibd::devicesParameters());
if(isset($_REQUEST['SelectAddr']))
	echo '<script>var SelectAddr="'.$_REQUEST['SelectAddr'].'";</script>';
else
	echo '<script>var SelectAddr="";</script>';
if(isset($_REQUEST['SelectDpt']))
	echo '<script>var SelectDpt="'.str_replace("XXX","",$_REQUEST['SelectDpt']).'";</script>';
else
	echo '<script>var SelectDpt="";</script>';
?>
<style>
	table #table_GadInconue {
	    width: 100%;
	    display:block;
	}
	thead #table_GadInconue {
	    display: inline-block;
	    width: 100%;
	}
	tbody #table_GadInconue {
	    height: 200px;
	    display: inline-block;
	    width: 100%;
	    overflow: auto;
	}
</style>
<div class="input-group pull-right" style="display:inline-flex">
	<span class="input-group-btn">
		<a class="btn btn-success btn-xs roundedRight Include" data-validation=true></a> 
		<a class="btn btn-default btn-xs roundedRight Ets4Parser" >
			<i class="fas fa-cloud-upload"></i>
			{{ Importer}}
		</a> 
		<a class="btn btn-warning btn-xs roundedRight bt_autoCreate" >
			<i class="fas fa-plus-circle"></i>
			{{ Créer}}
		</a> 
	</span>
</div>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" class="active">
		<a href="#InconueTab" aria-controls="home" role="tab" data-toggle="tab" aria-expanded="true">
			<i class="fa fa-tachometer"></i> {{Inconnue}}</a>
	</li>
	<li role="presentation" class="">
		<a href="#DeviceTab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
			<i class="fa fa-list-alt"></i> {{Équipements}}</a>
	</li>
	<li role="presentation" class="">
		<a href="#AdressTab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
			<i class="fa fa-list-alt"></i> {{Adresses de groupes}}</a>
	</li>
	<li role="presentation" class="">
		<a href="#LocationsTab" aria-controls="profile" role="tab" data-toggle="tab" aria-expanded="false">
			<i class="fa fa-list-alt"></i> {{Localisations}}</a>
	</li>
</ul>
<div class="tab-content" style="height: 500px;overflow: auto;">
	<div role="tabpanel" class="tab-pane active" id="InconueTab">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-danger btn-xs roundedRight removeAllGad">
					<i class="fas fa-trash-o"></i>
					{{ Nettoyer}}
				</a>
			</span>
		</div>
		<table id="table_GadInconue" class="table table-bordered table-condensed tablesorter GadInsert">
			<thead>
				<tr>
					<th>{{Source}}</th>
					<th>{{Destination}}</th>
					<th>{{Data Point Type}}</th>
					<th>{{Dernière valeur}}</th>
					<th>{{Action sur cette adresse de groupe}}</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div role="tabpanel" class="tab-pane" id="DeviceTab">
		<ul class="MyDeviceGroup"></ul>
	</div>
	<div role="tabpanel" class="tab-pane" id="AdressTab">
		<ul class="MyAdressGroup"></ul>
	</div>
	<div role="tabpanel" class="tab-pane" id="LocationsTab">
		<ul class="MyLocationsGroup"></ul>
	</div>
</div>
<script>
var KnxGadInconueRefresh = null;
var KnxProject = null;
$.ajax({
	type: 'POST',
	async: false,
	url: 'plugins/eibd/core/ajax/eibd.ajax.php',
	data: {
		action: 'getIsInclude',
	},
	dataType: 'json',
	global: false,
	error: function(request, status, error) {
	},
	success: function(data) {
		if (data.state != 'ok') {
			$('#div_alert').showAlert({message: data.result, level: 'danger'});
			return;
		}
		if(data.result == "false"){
			$('.Include').attr('data-validation',"true");
			$('.Include').html($('<i class="fas fa-bullseye">'))
				.append(' {{Activer}}');
		}else{
			$('.Include').attr('data-validation',"false");
			$('.Include').html($('<i class="fas fa-spinner fa-pulse">'))
				.append(' {{Désactiver}}');
		}
	}
});
$('.Include').off().on('click', function () {
	$.ajax({
		type: 'POST',
		async: false,
		url: 'plugins/eibd/core/ajax/eibd.ajax.php',
		data: {
			action: 'setIsInclude',
			value: $('.Include').attr('data-validation')
		},
		dataType: 'json',
		global: false,
		error: function(request, status, error) {
		},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}			
			if($('.Include').attr('data-validation') == "false"){
				$('.Include').attr('data-validation',"true");
				$('.Include').html($('<i class="fa fa-bullseye">'))
					.append(' {{Activer l\'inclusion}}');
			}else{
				$('.Include').attr('data-validation',"false");
				$('.Include').html($('<i class="fa fa-spinner fa-pulse">'))
					.append(' {{Désactiver l\'inclusion}}');
			}
		}
	});
});
$('.Ets4Parser').off().on('click', function() {
	ImportEts(false);
});
$('.bt_autoCreate').off().on('click', function() {
	autoCreate();
});
var SelectGad='';
jeedomUtils.initTableSorter();
$("#table_GadInconue .tablesorter-filter[data-column=2]").val(SelectDpt);
$("#table_GadInconue .tablesorter-filter[data-column=0]").val(SelectAddr);
getKnxGadInconue();
function getKnxGadInconue () {
	$.ajax({
		type: 'POST',
		async: false,
		url: 'plugins/eibd/core/ajax/eibd.ajax.php',
		data: {
			action: 'getCacheGadInconue',
		},
		dataType: 'json',
		global: false,
		error: function(request, status, error) {
			setTimeout(function() {
				getKnxGadInconue()
			}, 100);
		},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#table_GadInconue tbody').html('');
			jQuery.each(jQuery.parseJSON(data.result),function(key, value) {
				var tr=$("<tr>");
				tr.append($("<td class='AdressePhysique'>").text(value.AdressePhysique));
				tr.append($("<td class='AdresseGroupe'>").text(value.AdresseGroupe));
				tr.append($("<td class='DataPointType'>").text(value.DataPointType));
				tr.append($("<td class='valeur'>").text(value.valeur));
				tr.append($("<td>")
					.append($('<a class="btn btn-danger btn-xs Gad pull-right" data-action="remove">')
						.append($('<i class="fa fa-minus-circle">'))
						.text('{{Supprimer}}')));
			      	$('#table_GadInconue tbody').append(tr);
			});				
			$('#table_GadInconue').trigger('update');
			$("#table_GadInconue .tablesorter-filter[data-column=0]").trigger('keyup');
			$("#table_GadInconue .tablesorter-filter[data-column=2]").trigger('keyup');
			KnxGadInconueRefresh=setTimeout(function() {
				getKnxGadInconue()
			}, 10000);
		}
	});
}
getEtsProj();
function getEtsProj () {
	$.ajax({
		type: 'POST',
		async: false,
		url: 'plugins/eibd/core/ajax/eibd.ajax.php',
		data: {
			action: 'getEtsProj',
		},
		dataType: 'json',
		global: false,
		error: function(request, status, error) {
			getEtsProj()
		},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			if (data.result == false) 
				return;
			KnxProject = data.result;
			CreateArboressance(data.result.Devices,$('.MyDeviceGroup'),true);
			CreateArboressance(data.result.GAD,$('.MyAdressGroup'),true);
			CreateArboressance(data.result.Locations,$('.MyLocationsGroup'),true);
		}
	});
}
$('body').on('click', '.Gad[data-action=remove]', function(){
	var gad=$(this).closest('tr').find('.AdresseGroupe').text();
	removeInCache(gad);
	$(this).closest('tr').remove();
});
$('body').on('click', '.removeAllGad', function(){
	removeInCache('');
	$('#table_GadInconue tbody').html("");
});
$('body').on('click', '.GadInsert tbody tr', function(){
	$('.AdresseGroupe').css('font-weight','unset');
	$('.GadInsert tr').css('font-weight','unset');
	$(this).closest('tr').css('font-weight','bold');
	SelectGad = $(this).closest('tr').find('.AdresseGroupe').text();
	SelectAddr = $(this).closest('tr').find('.AdressePhysique').text();
	SelectDpt=$(this).closest('tr').find('.DataPointType').text();
})
.on('dblclick','.AdresseGroupe',function(e){
	$('.AdresseGroupe').css('font-weight','unset');
	$('.GadInsert tr').css('font-weight','unset');
	$(this).css('font-weight','bold');
	SelectGad=$(this).attr('data-AdresseGroupe');
	SelectAddr=$(this).attr('data-AdressePhysique').replace(/\-/g, '.');
	SelectDpt=$(this).attr('data-DataPointType').replace(/\-/g, '.');
	$(this).closest('.modal-content').find('button[data-bb-handler=success]').trigger('click');
});
function removeInCache(gad){
	$.ajax({
		type: 'POST',
		async: false,
		url: 'plugins/eibd/core/ajax/eibd.ajax.php',
		data: {
			action: 'setCacheGadInconue',
			gad:gad,
		},
		dataType: 'json',
		global: false,
		error: function(request, status, error) {},
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
		}
	});
}
</script>
