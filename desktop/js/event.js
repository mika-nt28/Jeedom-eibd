$('body').off().on('eibd::GadInconnue', function (_event,_options) {
	var value=jQuery.parseJSON(_options);
	var Html= $('<div>').load('index.php?v=d&modal=eibd.gadInconnue&plugin=eibd&type=eibd');
	if (typeof(value.DeviceName) !== 'undefined') 
		Html.find('.equipement').text(value.DeviceName);
	Html.find('.source').text(value.AdressePhysique);
	if (typeof(value.cmdName) !== 'undefined') 
		Html.find('.cmd').text(value.cmdName);
	Html.find('.destination').text(value.AdresseGroupe);
	Html.find('.dpt').text(value.DataPointType);
	Html.find('.valeur').text(value.valeur);
	bootbox.dialog({
		title: "{{Gad inconnue détecté}}",
		height: "800px",
		width: "auto",
		message: Html,
		buttons: {
			"Annuler": {
				className: "btn-default",
				callback: function () {
					//el.atCaret('insert', result.human);
				}
			},
			success: {
				label: "Ajouter a un equipement",
				className: "btn-primary",
				callback: function () {
					//input.closest('.modal-body').find('.EqLogicTemplateAttr[data-l1key=logicalId]').val(SelectAddr);	
					//input.val(SelectGad);
				}
			},
		}
	});
});
