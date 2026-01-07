function getLevelSelect(Level){
	var html = $('<div class="col-sm-7 control-label">');
	for(var loop = 0; loop < Level; loop++){
		html.append($('<select class="autoCreateParameter" data-l1key="levelType" data-l2key="'+loop+'">')
			.append($('<option value="">')
				  .append('{{Aucun}}'))
			.append($('<option value="object">')
				  .append('{{Objet}}'))
			.append($('<option value="function">')
				.append('{{Equipement}}'))
			.append($('<option value="cmd">')
				.append('{{Commande}}')));
	}
	return html;
}
function getNbLevel(arbo,nbLevel){
	nbLevel++;
	var maxLevel = 0;
	$.each(arbo, function(Niveau, Parameter){
		if(typeof Parameter.AdresseGroupe == "undefined"){
			if(typeof Parameter == "object") {
				var level = getNbLevel(Parameter,nbLevel);
				if(level > maxLevel)
					maxLevel = level;
			}

		}else{
			if(nbLevel > maxLevel)
				maxLevel = nbLevel;
		}
	});
	return maxLevel;
}
function autoCreate(){
	var html = $('<form class="autoCreate form-horizontal" onsubmit="return false;">');
  	html.append($('<div class="form-group">') 
		.append($('<label class="col-sm-4 control-label">') 
        		.append('{{Quelle arborescence choisir ?}}') 
			.append($('<sup>') 
				.append($('<i class="fa fa-question-circle tooltips" title="{{Cette option permet de choisir l\'arborescence sur laquelle on va créer nos objet / équipement / commande}}" >'))))
		.append($('<div class="col-sm-7 control-label">') 
			.append($('<select class="autoCreateParameter" data-l1key="arborescence">')
			.append($('<option value="gad">')
				  .append('{{Adresse de groupe}}'))
			.append($('<option value="device">')
				  .append('{{Équipement}}'))
			.append($('<option value="locations">')
				  .append('{{Localisation}}')))));
  	html.append($('<div class="form-group">') 
		.append($('<label class="col-sm-4 control-label">') 
        		.append('{{Créer les objets}}') 
			.append($('<sup>') 
				.append($('<i class="fa fa-question-circle tooltips" title="{{Cette option autorise le parseur à créer automatiquement les objets trouvés selon la definition des niveaux définis précédemment dans l\'arborescence de groupe}}" >'))))
		.append($('<div class="col-sm-7 control-label">') 
			.append($('<input type="checkbox" class="autoCreateParameter" data-l1key="createObjet"/>'))));
	html.append($('<div class="form-group">') 
		.append($('<label class="col-sm-4 control-label">') 
      			.append('{{Créer les équipements}}') 
			.append($('<sup>') 
				.append($('<i class="fa fa-question-circle tooltips" title="{{Cette option autorise le parseur à créer automatiquement les équipements trouvés selon la definition des niveaux définis précédemment dans l\'arborescence de groupe}}">'))))
		.append($('<div class="col-sm-7 control-label">') 
			.append($('<input type="checkbox" class="autoCreateParameter" data-l1key="createEqLogic"/>'))));
	html.append($('<div class="form-group withCreate">') 
		.append($('<label class="col-sm-4 control-label">') 
		        .append('{{Arborescence des groupes}}') 
			.append($('<sup>') 
				.append($('<i class="fa fa-question-circle tooltips" title="{{La définition de l\'arborescence de groupe permet au parseur de connaitre où se situe le nom à prendre pour la création automatique des objets ou des équipements}}">'))))
			.append($('<div class="level">')).hide());
	html.append($('<div class="form-group withCreateEqLogic">') 
		.append($('<label class="col-sm-4 control-label">')
       			.append('{{Uniquement correspondant à un Template}}')
			.append($('<sup>')
				.append($('<i class="fa fa-question-circle tooltips" title="{{Cette option permet de filtrer la création d\'équipements à ceux qui correspondent à un Template (Nom du Template et des Commandes)}}">'))))
		.append($('<div class="col-sm-7 control-label">') 
			.append($('<input type="checkbox" class="autoCreateParameter" data-l1key="createTemplate"/>'))).hide());
	bootbox.dialog({
		title: "{{Création automatique des équipements KNX}}",
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
							action: 'autoCreate',
							option: $('.autoCreate').getValues('.autoCreateParameter')
						},
						dataType: 'json',
						global: true,
						error: function(request, status, error) {},
						success: function(data) {
							window.location.reload();
						}
					});
				}
			},
		}
	});
	$('.autoCreateParameter[data-l1key=arborescence]').off().on('change',function() {
		var arbo = null;		
		switch($(this).val()){
			case 'gad':
				arbo = KnxProject.GAD;
			break;
			case 'device':
				arbo = KnxProject.Devices;
			break;
			case 'locations':
				arbo = KnxProject.Locations;
			break;
		}
		if(arbo != null){
			$('.autoCreate .level').html(getLevelSelect(getNbLevel(arbo,0)));
			$('.autoCreateParameter[data-l1key=levelType]').off().on('change',function() {
				if($(this).val() != 'object' && $(this).val() != ''){
					if($('.autoCreateParameter[data-l1key=levelType] option[value='+$(this).val()+']:selected').length > 1){
						$(this).val('');
						alert('{{Impossible d\'avoir plusieurs champs Équipement ou Commande}}');
					}
				}
			});
		}		
	});
	$('.autoCreateParameter[data-l1key=createEqLogic]').off().on('change',function() {
 		if(this.checked) {
			$('.autoCreate .withCreate').show();
			$('.autoCreate .withCreateEqLogic').show();
		}else{
			$('.autoCreate .withCreate').hide();
			$('.autoCreate .withCreateEqLogic').hide();
		}
	});
	$('.autoCreateParameter[data-l1key=createObjet]').off().on('change',function() {
 		if(this.checked) {
			$('.autoCreate .withCreate').show();
		}else{
			$('.autoCreate .withCreate').hide();
		}
	});
	$('.autoCreateParameter[data-l1key=arborescence]').trigger('change');
}
