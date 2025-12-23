<?php
	if (!isConnect('admin')) {
		throw new Exception('{{401 - Accès non autorisé}}');
	}
	$plugin = plugin::byId('eibd');
	sendVarToJS('eqType', $plugin->getId());
	sendVarToJS('GadLevel',config::byKey('level','eibd'));
	sendVarToJS('AllDpt',Dpt::All_DPT());
	$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="add"> 
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>
			<div class="cursor eqLogicAction logoPrimary" data-action="addByTemplate"> 
				<i class="fas fa-plus"></i>
				<br>
				<span>{{Template}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>  
				<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoBusMoniteur">
				<i class="fas fa-archive"></i>  
				<br>
				<span>{{Moniteur de bus}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoAdressGroup">
				<i class="fas fa-address-card"></i>
				<br>
				<span>{{Adresses de groupe}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoLog">
				<i class="fas fa-medkit"></i>  
				<br>
				<span>{{Log du démon}}</span>
			</div>
			<div class="cursor eqLogicAction logoSecondary" data-action="gotoHealth">
				<i class="fas fa-medkit"></i>  
				<br>
				<span>{{Santé}}</span>
			</div>
		</div>
		<legend>
			<i class="fas fa-table"></i> 
			{{Mes Modules KNX}}
			<a class="btn btn-default btn-sm eqLogicAction pull-right" data-action="Thumbnail" style="margin-top:5px;"><i class="fas fa-th-large"></i></a>
			<a class="btn btn-default btn-sm eqLogicAction pull-right" data-action="List" style="margin-top:5px;"><i class="fas fa-list-alt"></i></a>		
		</legend>
		<div class="eqLogicThumbnail">
			<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
			<div class="eqLogicThumbnailContainer">
				<?php
					foreach ($eqLogics as $eqLogic) {
						$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
						echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
						$file='plugins/eibd/core/config/templates/'.$eqLogic->getConfiguration('typeTemplate').'.png';
						if(file_exists($file))					
							echo '<img src="'.$file.'" height="105" width="95" />';
						else
							echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
						echo '<br>';
						echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
						echo '</div>';
					}
				?>
			</div>

		</div>
		<div class="eqLogicList" style="display: none;">
			<table id="table_eqLogicList" class="table table-bordered table-condensed tablesorter">
				<thead>
					<tr>
						<th>{{Template}}</th>
						<th>{{Objet parent}}</th>
						<th>{{Nom}}</th>
						<th>{{Adresse physique}}</th>
						<th>{{Catégorie}}</th>
						<th>{{Activer}}</th>
						<th>{{Visible}}</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach ($eqLogics as $eqLogic) {
							echo '<tr class="eqLogicDisplay" data-eqLogic_id="' . $eqLogic->getId() . '">';
							$file='plugins/eibd/core/config/templates/'.$eqLogic->getConfiguration('typeTemplate').'.png';
							if(file_exists($file))
								echo '<td><div style="display: none;">'.$eqLogic->getConfiguration('typeTemplate').'</div><img src="'.$file.'" height="45"  /></td>';
							else
								echo '<td><img src="plugins/eibd/plugin_info/eibd_icon.png" height="45" /></td>';
							echo '<td class="eqLogicDisplayAttr" data-l1key="object">';
							if(is_object($eqLogic->getObject()))
								echo $eqLogic->getObject()->getHumanName(true,true);
							echo '</td>';
							echo '<td><span class="label label-info eqLogicDisplayAttr" data-l1key="name">' . $eqLogic->getName() . '</span></td>';
							echo '<td><span class="label label-info eqLogicDisplayAttr" data-l1key="logicalId"> ' . $eqLogic->getLogicalId() . '</span></td>';
							echo '<td>';
							$Categorie=$eqLogic->getCategory();
							foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
								if(!isset($Categorie[$key]))
									echo '<span class="label label-success eqLogicDisplayAttr" data-l1key="category" data-l2key="' . $key . '" data-enable="0">' . $value['name'] . '</span>';
								elseif($Categorie[$key]) 
									echo '<span class="label label-success eqLogicDisplayAttr" data-l1key="category" data-l2key="' . $key . '" data-enable="'.$Categorie[$key].'">' . $value['name'] . '</span>';
								else
									echo '<span class="label label-default eqLogicDisplayAttr" data-l1key="category" data-l2key="' . $key . '" data-enable="'.$Categorie[$key].'">' . $value['name'] . '</span>';
							}
							echo '</td>';
							$active = '<span class="label label-success eqLogicDisplayAttr" data-l1key="isEnable" data-enable="'.$eqLogic->getIsEnable().'">{{Oui}}</span>';
							if (!$eqLogic->getIsEnable()) {
								$active = '<span class="label label-danger eqLogicDisplayAttr" data-l1key="isEnable" data-enable="'.$eqLogic->getIsEnable().'">{{Non}}</span>';
							}
							echo '<td>' . $active . '</td>';
							$visible = '<span class="label label-success eqLogicDisplayAttr" data-l1key="isVisible" data-enable="'.$eqLogic->getIsVisible().'">{{Oui}}</span>';
							if (!$eqLogic->getIsVisible()) {
								$visible = '<span class="label label-danger eqLogicDisplayAttr" data-l1key="isVisible" data-enable="'.$eqLogic->getIsVisible().'">{{Non}}</span>';
							}
							echo '<td>' . $visible . '</td>';
							//echo '<td><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a></td>';
							echo '<td></td>';
							echo '</tr>';
						}
					?>
				</tbody>
			</table>
		</div>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<form class="form-horizontal">
					<fieldset>
						<div class="form-group">
							<label class="col-sm-3 control-label">
								{{Nom de l'équipement KNX}}
								<sup>
									<i class="fas fa-question-circle tooltips" title="{{Indiquez le nom de votre équipement}}"></i>
								</sup>
							</label>
							<div class="col-sm-3">
								<input type="hidden" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="typeTemplate"/>
								<input type="hidden" class="eqLogicAttr form-control" data-l1key="id" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement KNX}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">
								{{Adresse Physique de l'équipement}}
								<sup>
									<i class="fas fa-question-circle tooltips" title="{{Indiquez l'adresse physique de votre équipement. Cette information n'est pas obligatoire mais peut être utile dans certain cas.}}"></i>
								</sup>
							</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="logicalId" placeholder="{{Adresse physique l'équipement KNX}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label" >
								{{Objet parent}}
								<sup>
									<i class="fas fa-question-circle tooltips" title="{{Sélectioner l'objet dans lequel doit apparaitre cet equipement.}}"></i>
								</sup>
							</label>
							<div class="col-sm-3">
								<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
									<option value="">{{Aucun}}</option>
									<?php
										foreach (jeeObject::all() as $object) {
											echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
										}
									?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">
								{{Catégorie}}								
								<sup>
									<i class="fas fa-question-circle tooltips" title="{{Choisir une ou plusieurs catégories.}}"></i>
								</sup>
							</label>
							<div class="col-sm-9">
								<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
									}
								?>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">
								{{État du widget}}
								<sup>
									<i class="fas fa-question-circle tooltips" title="{{Choisissez les options de visibilité et d'activation
									Si l'équipement n'est pas activé il ne sera pas utilisable dans jeedom, mais visible sur le dashboard
									Si l'équipement n'est pas visible il sera caché sur le Dashbord, mais utilisable dans Jeedom}}"></i>
								</sup>
							</label>
							<div class="col-sm-9">
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
								<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
							</div>
						</div>
						<div class="form-group">
							<label class="col-sm-3 control-label">
								{{Délai max entre 2 messages}}
								<sup>
									<i class="fas fa-question-circle tooltips" title="{{Cette information est optionnelle et permet de définir si un équipement est fonctionnel ou non. Elle ne peut être utilisée que si votre équipement envoie régulièrement des informations (sonde de température, horloge, ...)}}"></i>
								</sup>
							</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="timeout" placeholder="{{Délai maximum autorisé entre 2 messages (en mn)}}"/>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Commandes}}</a>
				<a class="btn btn-primary btn-sm Template pull-right" data-action="add" style="margin-top:5px;"><i class="fas fa-plus-circle"></i> {{Template}}</a><br/><br/>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th ></th>
							<th>{{Nom}}</th>
							<th>{{Configuration KNX}}</th>
							<th>{{Flag}}</th>
							<th>{{Valeur}}</th>
							<th>{{Paramètre}}</th>
							<th></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<?php 
include_file('desktop', 'eibd', 'js', 'eibd');
include_file('desktop', 'Template', 'js', 'eibd');
include_file('core', 'plugin.template', 'js'); 
?>
