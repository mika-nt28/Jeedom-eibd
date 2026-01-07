<?php
if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
$eqLogics = eibd::byType('eibd');
?>
<div style="height: 500px;overflow: auto;">
	<table id="table_healthEibd" class="table table-bordered table-condensed tablesorter">
		<thead>
			<tr>
				<th></th>
				<th>{{ID}}</th>
				<th>{{Module}}</th>
				<th>{{Adresse physique}}</th>
				<th>{{Statut}}</th>
				<th>{{Batterie}}</th>
				<th>{{Dernière communication}}</th>
				<th>{{Date de création}}</th>
			</tr>
		</thead>
		<tbody>
		<?php
			foreach ($eqLogics as $eqLogic) {
				$file='plugins/eibd/core/config/templates/'.$eqLogic->getConfiguration('typeTemplate').'.png';
				if(file_exists($file))
					echo '<td><img src="'.$file.'" height="45"  /></td>';
				else
					echo '<td><img src="plugins/eibd/plugin_info/eibd_icon.png" height="45" /></td>';
				echo '<td><span class="label label-info">' . $eqLogic->getId() . '</span></td>';
				echo '<td><span class="label label-info">' . $eqLogic->getHumanName() . '</span></td>';
				echo '<td><span class="label label-info">' . $eqLogic->getLogicalId() . '</span></td>';
				$status = '<span class="label label-success">{{OK}}</span>';
				if ($eqLogic->getStatus('state') == 'nok') {
					$status = '<span class="label label-danger">{{NOK}}</span>';
				}
				echo '<td>' . $status . '</td>';
				$battery_status = '<span class="label label-success">{{OK}}</span>';
				if ($eqLogic->getStatus('battery') < 20 && $eqLogic->getStatus('battery') != '') {
					$battery_status = '<span class="label label-danger">' . $eqLogic->getStatus('battery') . '%</span>';
				} elseif ($eqLogic->getStatus('battery') < 60 && $eqLogic->getStatus('battery') != '') {
					$battery_status = '<span class="label label-warning">' . $eqLogic->getStatus('battery') . '%</span>';
				} elseif ($eqLogic->getStatus('battery') > 60 && $eqLogic->getStatus('battery') != '') {
					$battery_status = '<span class="label label-success">' . $eqLogic->getStatus('battery') . '%</span>';
				} else {
					$battery_status = '<span class="label label-primary"title="{{Secteur}}"><i class="fa fa-plug"></i></span>';
				}
				echo '<td>' . $battery_status . '</td>';
				echo '<td><span class="label label-info" >' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
				echo '<td><span class="label label-info">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
			}
		?>
		</tbody>
	</table>

	<script>
	jeedomUtils.initTableSorter();
	</script>
</div>
