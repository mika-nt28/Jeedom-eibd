function ImportEts(merge){
	var html = $('<form class="form-horizontal" onsubmit="return false;">');
	html.append($('<div class="form-group">')
		.append($('<label class="col-md-4 control-label">')
			.append('{{Type de fichier}}')
			.append($('<sup>')
				.append($('<i class="fa fa-question-circle tooltips" title="{{Sélectionnez le type de fichier}}">'))))
		.append($('<div class="col-md-8">')
			.append($('<select class=" EtsParseParameter" data-l1key="ProjetType">')
				.append($('<option value="ETS">')
					.append('{{ETS}}'))
				.append($('<option value="TX100">')
					.append('{{TX100}}')))));
	html.append($('<div class="form-group">')
		.append($('<label class="col-md-4 control-label">')
			.append('{{Importez votre projet}}')
			.append($('<sup>')
				.append($('<i class="fa fa-question-circle tooltips" title="{{Uploadez votre projet ETS (*.knxproj)}}">'))))
		.append($('<div class="col-md-8">')
			.append($('<input type="file" name="Knxproj" id="Knxproj" data-url="plugins/eibd/core/ajax/eibd.ajax.php?action=EtsParser" placeholder="{{Ficher export ETS}}" class="form-control input-md"/>'))));
	bootbox.dialog({
		title: "{{Importez votre projet KNX}}",
		message: html,
		buttons: {
			"Annuler": {
				className: "btn-default",
				callback: function () {
				}
			},
			success: {
				label: "Valider",
				className: "btn-primary",
				callback: function () {
					$.ajax({
						type: 'POST',   
						url: 'plugins/eibd/core/ajax/eibd.ajax.php',
						data:
						{
							action: 'AnalyseEtsProj',
							merge: merge,
							ProjetType: $('.EtsParseParameter[data-l1key=ProjetType]').val()
						},
						dataType: 'json',
						global: true,
						error: function(request, status, error) {},
						success: function(data) {
							bootbox.confirm({
								message: "{{Voulez-vous importer un autre fichier ?}}",
								buttons: {
									confirm: {
										label: '{{Oui}}',
										className: 'btn-success'
									},
									cancel: {
										label: '{{Non}}',
										className: 'btn-danger'
									}
								},
								callback: function (result) {
									if(result){
										ImportEts(true);
									}else{
										KnxProject = data.result;
										CreateArboressance(data.result.Devices,$('.MyDeviceGroup'),true);
										CreateArboressance(data.result.GAD,$('.MyAdressGroup'),true);
										CreateArboressance(data.result.Locations,$('.MyLocationsGroup'),true);
										bootbox.confirm({
											message: "{{Voulez-vous créer automatiquement les équipements ?}}",
											buttons: {
												confirm: {
													label: '{{Oui}}',
													className: 'btn-success'
												},
												cancel: {
													label: '{{Non}}',
													className: 'btn-danger'
												}
											},
											callback: function (result) {
												if(result){
													autoCreate();
												}
											}
										});
									}
								}
							});
						}
					});
				}
			},
		}
	});
	$('#Knxproj').fileupload({
		dataType: 'json',
		replaceFileInput: false,
		//done: function (data) {
		success: function(data) {
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: data.result, level: 'danger'});
				return;
			}
			$('#div_alert').showAlert({message: "Import ETS complet.</br>Vous pouvez commencer la configuration des équipements", level: 'success'});
			//$('.EtsImportData').append(data.result);
		}
	});
}
