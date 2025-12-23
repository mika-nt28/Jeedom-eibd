<?php
if (!isConnect('admin')) {
    throw new Exception('401 Unauthorized');
}
?>
<div class="row">
	<form class="form-horizontal" onsubmit="return false;">
		<legend>{{Définition de l'équipement}}</legend>
		<div class="col-md-12">
			<div class="form-group">
				<label class="col-md-5 control-label">
					{{Nom de l'équipement KNX}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Indiquez le nom de votre équipement}}" style="font-size : 1em;color:grey;"></i>
					</sup>
				</label>
				<div class="col-md-5">
					<input type="text" class="EqLogicTemplateAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement KNX}}"/>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-5 control-label ">
					{{Adresse physique de l'équipement}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Indiquez l'adresse physique de votre équipement. Cette information n'est pas obligatoire mais peut être utile dans certains cas. Pour la trouver, il faut la retrouver sur le logiciel ETS}}" style="font-size : 1em;color:grey;"></i>
					</sup>
				</label>
				<div class="col-md-5">
					<input type="text" class="EqLogicTemplateAttr form-control" data-l1key="logicalId"/>
				</div>
			</div>
			<div class="form-group">
				<label class="col-md-5 control-label" >
					{{Objet parent}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Indiquez l'objet dans lequel cet équipement apparaîtra sur le Dashboard}}" style="font-size : 1em;color:grey;"></i>
					</sup>
				</label>
				<div class="col-md-5">
					<select id="sel_object" class="EqLogicTemplateAttr form-control" data-l1key="object_id">
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
				<label class="col-md-5 control-label" >
					{{Template de votre équipement}}
					<sup>
						<i class="fa fa-question-circle tooltips" title="{{Choisir le template de votre nouvel équipement}}" style="font-size : 1em;color:grey;"></i>
					</sup>
				</label>
				<div class="col-md-5">
					<select class="EqLogicTemplateAttr form-control" data-l1key="template">
						<option value="">{{Sélectionner votre template}}</option>
						<?php
						foreach (eibd::devicesParameters() as $id => $template) {		
							echo '<option value="' . $id . '">' . $template['name'] . '</option>';
						}
						?>
					</select>
				</div>
			</div>		
		</div>
		<legend>{{Définition des commandes}}</legend>
		<div class="col-md-12">
			<div class="form-horizontal CmdsTempates">
				<div class="option_bt"></div>
			</div>
		</div>
	</form>		
</div>
