function CreateObject(object){
	$.ajax({
		type: 'POST',
		async: false,
		url: 'plugins/eibd/core/ajax/eibd.ajax.php',
		data: {
			action: 'CreateObject',
			name:object,
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
function selectTemplate(_equipement){
	var select = $('<select class="EqLogicTemplateAttr form-control" data-l1key="template">');
	$.each(templates,function(id,template){
		select.append($('<option>')
			.attr('value',id)
			.append(template.name));
	});
	bootbox.dialog({
		title: "{{Sélectionnez le template}}",
		message: select,
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
					CreatebyTemplate(_equipement,templates[$('.EqLogicTemplateAttr[data-l1key=template]').val()]);
				}
			},
		}
	});
      
}
function htmlMergeTemplate(template,cmds){
	var selectParent = $('<select class="EqLogicTemplateAttr form-control" data-l1key="object">');
	selectParent.append($('<option>').text("{{Aucun}}"));
	jeedom.object.all({success:function(objects){
		$.each(objects,function(index,object){
			selectParent.append($('<option value="'+object.id+'">').text(object.name));
		});
	}});
	var selectCmd = $('<select class="EqLogicTemplateAttr form-control" data-l1key="cmd">');
	selectCmd.append($('<option>').text('Aucun'));
	var optgroup = $('<optgroup>').attr('label','Base');
	$.each(template.cmd,function(idCmd,cmd){
		var optionName = cmd.name;
		if(isset(cmd.SameCmd) && cmd.SameCmd != '') 
			optionName = cmd.SameCmd;
		var optionExist = false;
		optgroup.find('option').each(function() {
			if($(this).text() == optionName)
				optionExist = true;
		});
		if(!optionExist){
			optgroup.append($('<option>')
				.attr('value','_'+idCmd)
				.text(optionName));
		}
	});
	selectCmd.append(optgroup);
	$.each(template.options,function(id,option){
		var optgroup = $('<optgroup>').attr('label',option.name);
		$.each(option.cmd,function(idCmd, cmd){
			var optionName = cmd.name;
			if(isset(cmd.SameCmd) && cmd.SameCmd != '') 
				optionName = cmd.SameCmd;
			var optionExist = false;
			optgroup.find('option').each(function() {
				if($(this).text() == optionName)
					optionExist = true;
			});
			if(!optionExist){
				optgroup.append($('<option>')
					.attr('value',id+'_'+idCmd)
					.text(optionName));
			}
		});
		selectCmd.append(optgroup);
	});
	var html = $('<div>')
	$.each(cmds,function(id, cmd){
		html.append($('<div class="form-group">')
			.append($('<label class="col-sm-5 control-label">')
				.append(cmd.name))
			.append($('<div class="col-sm-7">')
				.append(selectCmd.clone().attr('data-l2key',cmd.AdresseGroupe))));		
	});
	return $('<form class="form-horizontal">')
		.append($('<fieldset>')
			.append($('<legend>').text("{{Equipement}}"))
			.append($('<div class="form-group">')
				.append($('<label class="col-sm-5 control-label">')
					.append('{{Nom de l\'équipement KNX}}')
					.append($('<sup>')
						.append($('<i class="fas fa-question-circle tooltips" title="{{Indiquez le nom de votre équipement}}">'))))
				.append($('<div class="col-sm-7">')
					.append($('<input type="text" class="EqLogicTemplateAttr form-control" data-l1key="name" placeholder="{{Nom de l\'équipement KNX}}"/>'))))			
			.append($('<div class="form-group">')
				.append($('<label class="col-sm-5 control-label">')
					.append('{{Objet parent}}')
					.append($('<sup>')
						.append($('<i class="fas fa-question-circle tooltips" title="{{Sélectionnez l\'objet parent}}">'))))
				.append($('<div class="col-sm-7">')
					.append(selectParent)))			
			.append($('<legend>').text("{{Commandes}}"))
			.append(html));	
}
$('body').off('change','.EqLogicTemplateAttr[data-l1key=cmd]').on('change','.EqLogicTemplateAttr[data-l1key=cmd]',function(){
	//$(this).closest('fieldset').find('option[value='+$(this).val()+']').attr('disabled',true);
	//$(this).find('option[value='+$(this).val()+']').attr('disabled',false);;
});
function getTemplate(_equipement){
	var _template = _equipement.find('label:first').text();
	if(_template != ''){
		if(templates[_template] == 'Undefinded'){
			selectTemplate(_equipement);
			return;
		}
		var isTemplate;
		$.each(templates,function(id, template){
			if(template.name.includes(_template)){
				isTemplate = template;
				return;
			}
		});
		if(isTemplate != null){
			CreatebyTemplate(_equipement,isTemplate) ;
			return;
		}
	}
	selectTemplate(_equipement);
}
function CreatebyTemplate(_equipement,_template){	
	var eqLogic = new Object();
	var dataArbo = new Array();
	_equipement.find(' ul:first li').each(function(){
		var cmd = new Object();
		if($(this).attr('data-AdresseGroupe') != 'Undefinded'){
			cmd.AdresseGroupe = $(this).attr('data-AdresseGroupe');
			cmd.AdressePhysique = $(this).attr('data-AdressePhysique').replace( '-',/\./g);
			cmd.DataPointType = $(this).attr('data-DataPointType').replace( '-',/\./g);
			cmd.name = $(this).text();
			dataArbo.push(cmd);
		}
	});	
	bootbox.dialog({
		title: "{{Merge des commandes sur le template}}",
		message: htmlMergeTemplate(_template,dataArbo),
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
					eqLogic.name = $('.EqLogicTemplateAttr[data-l1key=name]').val();
					eqLogic.category = _template["category"];
					eqLogic.object_id = $('.EqLogicTemplateAttr[data-l1key=object]').val();
					eqLogic.isEnable =  _template["isEnable"];
					eqLogic.isVisible =  _template["isVisible"];
					eqLogic.cmd = new Array();
					$('.EqLogicTemplateAttr[data-l1key=cmd]').each(function(){
						if($(this).val() != null){
							var _option = $(this).val().split("_")[0];
							var index = $(this).val().split("_")[1];
							if(_option == ''){
								if (typeof(_template.cmd[index]) !== 'undefined'){
									var logicalId=$(this).attr('data-l2key');
									_template.cmd[index].logicalId = logicalId;
									if (typeof(_template.cmd[index].value) !== 'undefined')
										_template.cmd[index].value="#["+$('.EqLogicTemplateAttr[data-l1key=object] option:selected').text()+"]["+eqLogic.name+"]["+_template.cmd[index].value+"]#";
									eqLogic.cmd.push(_template.cmd[index]);
									if(isset(_template.cmd[index].SameCmd) && _template.cmd[index].SameCmd != '') {
										$.each(_template.cmd[index].SameCmd.split('|'),function(id, name){
											$.each(_template.cmd,function(idCmd, cmd){
												if(cmd.name == name && idCmd != index){
													_template.cmd[idCmd].logicalId=logicalId;
													_template.cmd[idCmd].value="#["+$('.EqLogicTemplateAttr[data-l1key=object] option:selected').text()+"]["+eqLogic.name+"]["+_template.cmd[idCmd].value+"]#";
													eqLogic.cmd.push(_template.cmd[idCmd]);
												}
											});
										});
									}
								}
							}else{
								$.each(_template.options,function(optionId, option){
									if(_option == optionId){
										$.each(option,function(idoptionCmd, optionCmd){	
											if (typeof(_template.options[optionId].cmd[index]) !== 'undefined'){
												_template.options[optionId].cmd[index].logicalId=$(this).attr('data-l2key');
												if (typeof(_template.options[optionId].cmd[index].value) !== 'undefined')
													_template.options[optionId].cmd[index].value="#["+$('.EqLogicTemplateAttr[data-l1key=object] option:selected').text()+"]["+_template.name+"]["+_template.options[optionId].cmd[index].value+"]#";
												eqLogic.cmd.push(_template.options[optionId].cmd[index]);
												if(isset(_template.options[optionId].cmd[index].SameCmd) && _template.options[optionId].cmd[index].SameCmd != '') {
													$.each(_template.options[optionId].cmd[index].SameCmd.split('|'),function(idSameCmd, name){
														$.each(_template.options[optionId].cmd,function(idCmd, cmd){
															if(cmd.name == name && idCmd != index){
																_template.options[optionId].cmd[idCmd].logicalId=logicalId;
																_template.options[optionId].cmd[idCmd].value="#["+$('.EqLogicTemplateAttr[data-l1key=object] option:selected').text()+"]["+eqLogic.name+"]["+_template.cmd[idCmd].value+"]#";
																eqLogic.cmd.push(_template.options[optionId].cmd[idCmd]);
															}
														});
													});
												}
											}
										});
									}
								});
							}
						}
					});
					SaveMergeTemplate(eqLogic);
				}
			},
		}
	});
		
}
function SaveMergeTemplate(eqLogic){
	jeedom.eqLogic.save({
		type: 'eibd',
		eqLogics: [eqLogic],
		error: function (error) {
			$('#div_alert').showAlert({message: error.message, level: 'danger'});
		},
		success: function (_data) {
			
		}
	});
};
function CreateMenu(data){
	var menu = $('<span class="input-group-btn">');
	menu.append($('<a class="btn btn-success btn-xs roundedRight createObject">').append($('<i class="far fa-object-group">')));
	menu.append($('<a class="btn btn-warning btn-xs roundedRight createTemplate">').append($('<i class="fas fa-address-card">')));
	return $('<div class="input-group pull-right" style="display:inline-flex">').append(menu);
}
function CreateArboressance(data, Arboressance, first){
	if (first)
		Arboressance.html('');
	jQuery.each(data,function(Niveau, Parameter) {
		if(Parameter == null) {
			Arboressance.append($('<li class="col-sm-11 Level">').text(Niveau));
		}else if(typeof Parameter.AdresseGroupe == "undefined") {
			Arboressance.append($('<li class="col-sm-11 Level">')
				.append(CreateMenu(Parameter))
				.append($('<label>')
					.append(Niveau))
				.append(CreateArboressance(Parameter, $('<ul>').hide(),false)));
		}else{
			var li =$('<li class="col-sm-11 AdresseGroupe">');
			if(typeof Parameter.AdresseGroupe != "undefined"){
				var AdresseGroupe =Parameter.AdresseGroupe;
				li.attr('data-AdresseGroupe',AdresseGroupe);
			}
			if(typeof Parameter.AdressePhysique != "undefined"){
				var AdressePhysique =Parameter.AdressePhysique.replace(/\./g, '-');
				li.attr('data-AdressePhysique',AdressePhysique);
			}
			if(typeof Parameter.DataPointType != "undefined"){
				var DataPointType =Parameter.DataPointType.replace(/\./g, '-');
				li.attr('data-DataPointType',DataPointType);
			}
			li.text('('+Parameter.AdresseGroupe+') '+Niveau);
			Arboressance.append(li);
		}
	});
	if (first){
		Arboressance.off().on('click','.Level',function(e){
			if(!$(this).find('ul:first').is(":visible"))
				$(this).find('ul:first').show();
			else
				$(this).find('ul:first').hide();
			e.stopPropagation();
		})
		.on('click','.AdresseGroupe',function(e){
			$('.AdresseGroupe').css('font-weight','unset');
			$('.GadInsert tr').css('font-weight','unset');
			$(this).css('font-weight','bold');
			SelectGad=$(this).attr('data-AdresseGroupe');
			SelectAddr=$(this).attr('data-AdressePhysique').replace(/\-/g, '.');
			SelectDpt=$(this).attr('data-DataPointType').replace(/\-/g, '.');
			e.stopPropagation();
		})
		.on('dblclick','.AdresseGroupe',function(e){
			$('.AdresseGroupe').css('font-weight','unset');
			$('.GadInsert tr').css('font-weight','unset');
			$(this).css('font-weight','bold');
			SelectGad=$(this).attr('data-AdresseGroupe');
			SelectAddr=$(this).attr('data-AdressePhysique').replace(/\-/g, '.');
			SelectDpt=$(this).attr('data-DataPointType').replace(/\-/g, '.');
			e.stopPropagation();
			$(this).closest('.modal-content').find('button[data-bb-handler=success]').trigger('click');
		})
		.on('click','.createObject',function(e){
			e.stopPropagation();
			CreateObject($(this).parents('.Level:first').find('label:first').text());
		})
		.on('click','.createTemplate',function(e){
			e.stopPropagation();
			getTemplate($(this).parents('.Level:first'));
		});
		if(SelectAddr != ''){
			$.each(Arboressance.find(".AdresseGroupe"),function() {
				if($(this).attr("data-AdressePhysique") == SelectAddr.replace(/\./g, '-')){
					$(this).css('background-color','blue');
					$(this).css('color','white');
					$(this).parent().show();
					$(this).parent().parent().parent().show();
				}
			});
		}
		if(SelectDpt != ''){
			var SelectDptId = SelectDpt.replace(/\./g, '-');
			$.each(Arboressance.find(".AdresseGroupe"),function() {
				if($(this).attr("data-DataPointType") == SelectDptId){
					$(this).css('background-color','blue');
					$(this).css('color','white');
					$(this).parent().show();
					$(this).parent().parent().parent().show();
				}
				else if($(this).attr("data-DataPointType").replace($(this).attr("data-DataPointType").substr(-3), '') == SelectDptId.replace(SelectDptId.substr(-3), '')){
					$(this).css('background-color','yellow');
					$(this).parent().show();
					$(this).parent().parent().parent().show();
				}
			});
		}
	}
	return Arboressance;
}	
