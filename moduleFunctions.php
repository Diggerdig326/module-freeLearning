<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function prerequisitesRemoveInactive($connection2, $prerequisites) {
	$return=FALSE ;
	
	if ($prerequisites=="") {
		$return="" ;
	}
	else {
		$prerequisites=explode(",", $prerequisites) ;
		foreach ($prerequisites AS $prerequisite) {
			try {
				$data=array("freeLearningUnitID"=>$prerequisite); 
				$sql="SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
			if ($result->rowCount()==1) {
				$return.=$prerequisite . "," ;
			}
		} 
		if (substr($return, -1)==",") {
			$return=substr($return, 0, -1) ;
		}
	}
	
	return $return ;
}

function prerquisitesMet($connection2, $gibbonPersonID, $prerequisites) {
	$return=FALSE ;
	
	//Get all courses completed
	$complete=array() ;
	try {
		$data=array("gibbonPersonID"=>$gibbonPersonID); 
		$sql="SELECT * FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND (status='Complete - Approved' OR status='Exempt') ORDER BY freeLearningUnitID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }

	while ($row=$result->fetch()) {
		$complete[$row["freeLearningUnitID"]]=TRUE ;
	}
	
	//Check prerequisites against courses completed
	if ($prerequisites=="") {
		$return=TRUE ;
	}
	else {
		$prerequisites=explode(",", $prerequisites) ;
		$prerequisiteCount=count($prerequisites) ;
		$prerequisiteMet=0 ;
		foreach ($prerequisites AS $prerequisite) {
			if (isset($complete[$prerequisite])) {
				$prerequisiteMet++ ;
			}
		}
		if ($prerequisiteMet==$prerequisiteCount) {
			$return=TRUE ;
		}
	}
	
	return $return ;
}

function getBlocksArray($connection2) {
	$return=FALSE ;
	
	try {
		$data=array(); 
		$sql="SELECT * FROM freeLearningUnitBlock ORDER BY freeLearningUnitID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()>0) {
		$return=array() ;
		while ($row=$result->fetch()) {
			$return[$row["freeLearningUnitBlockID"]][0]=$row["freeLearningUnitID"] ;
			$return[$row["freeLearningUnitBlockID"]][1]=$row["title"] ;
			$return[$row["freeLearningUnitBlockID"]][2]=$row["length"] ;
		}
	}
	
	return $return ;
}

function getLearningAreaArray($connection2) {
	$return=FALSE ;
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()>0) {
		$return=array() ;
		while ($row=$result->fetch()) {
			$return[$row["gibbonDepartmentID"]]=$row["name"] ;
		}
	}
	
	return $return ;
}

//If $freeLearningUnitID is NULL, all units are returned: otherwise, only the specified
function getAuthorsArray($connection2, $freeLearningUnitID=NULL) {
	$return=FALSE ;
	
	try {
		if (is_null($freeLearningUnitID)) {
			$data=array(); 
			$sql="SELECT freeLearningUnitAuthorID, freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS gibbonPersonsurname, gibbonPerson.preferredName AS gibbonPersonpreferredName, gibbonPerson.website AS gibbonPersonwebsite, gibbonPerson.gibbonPersonID, freeLearningUnitAuthor.surname AS freeLearningUnitAuthorsurname, freeLearningUnitAuthor.preferredName AS freeLearningUnitAuthorpreferredName, freeLearningUnitAuthor.website AS freeLearningUnitAuthorwebsite FROM freeLearningUnitAuthor LEFT JOIN gibbonPerson ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY gibbonPersonsurname, freeLearningUnitAuthorsurname, gibbonPersonpreferredName, freeLearningUnitAuthorpreferredName" ;
		}
		else {
			$data=array("freeLearningUnitID"=>$freeLearningUnitID); 
			$sql="SELECT freeLearningUnitAuthorID, freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS gibbonPersonsurname, gibbonPerson.preferredName AS gibbonPersonpreferredName, gibbonPerson.website AS gibbonPersonwebsite, gibbonPerson.gibbonPersonID, freeLearningUnitAuthor.surname AS freeLearningUnitAuthorsurname, freeLearningUnitAuthor.preferredName AS freeLearningUnitAuthorpreferredName, freeLearningUnitAuthor.website AS freeLearningUnitAuthorwebsite FROM freeLearningUnitAuthor LEFT JOIN gibbonPerson ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY gibbonPersonsurname, freeLearningUnitAuthorsurname, gibbonPersonpreferredName, freeLearningUnitAuthorpreferredName" ;
		}
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { print $e->getMessage() ;}
	
	if ($result->rowCount()>0) {
		$return=array() ;
		while ($row=$result->fetch()) {
			if ($row["gibbonPersonID"]!=NULL) {
				$return[$row["freeLearningUnitAuthorID"]][0]=$row["freeLearningUnitID"] ;
				$return[$row["freeLearningUnitAuthorID"]][1]=formatName("", $row["gibbonPersonpreferredName"], $row["gibbonPersonsurname"], "Student", false) ;
				$return[$row["freeLearningUnitAuthorID"]][2]=$row["gibbonPersonID"] ;
				$return[$row["freeLearningUnitAuthorID"]][3]=$row["gibbonPersonwebsite"] ;
			}
			else {
				$return[$row["freeLearningUnitAuthorID"]][0]=$row["freeLearningUnitID"] ;
				$return[$row["freeLearningUnitAuthorID"]][1]=formatName("", $row["freeLearningUnitAuthorpreferredName"], $row["freeLearningUnitAuthorsurname"], "Student", false) ;
				$return[$row["freeLearningUnitAuthorID"]][2]=$row["gibbonPersonID"] ;
				$return[$row["freeLearningUnitAuthorID"]][3]=$row["freeLearningUnitAuthorwebsite"] ;
			}
			
		}
	}
	
	return $return ;
}

function getUnitsArray($connection2) {
	$return=FALSE ;
	
	try {
		$data=array(); 
		$sql="SELECT * FROM freeLearningUnit WHERE active='Y'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()>0) {
		$return=array() ;
		while ($row=$result->fetch()) {
			$return[$row["freeLearningUnitID"]][0]=$row["name"] ;
		}
	}
	
	return $return ;
}


//Set $limit=TRUE to only return departments that the user has curriculum editing rights in
function getLearningAreas($connection2, $guid, $limit=FALSE ) {
	$output=FALSE ;
	try {
		if ($limit==TRUE) {
			$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
			$sql="SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE type='Learning Area' AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')  ORDER BY name" ;
		}
		else {
			$data=array(); 
			$sql="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
		}
		$result=$connection2->prepare($sql);
		$result->execute($data);
		while ($row=$result->fetch()) {
			$output.=$row["gibbonDepartmentID"] . "," ;
			$output.=$row["name"] . "," ;
		}
	}
	catch(PDOException $e) { }		
	
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	return $output ;
}


//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
//Mode can be masterAdd, masterEdit, embed
//Outcomes is the result set of a mysql query of all outcomes from the unit the class belongs to
function makeBlock($guid, $connection2, $i, $mode="masterAdd", $title="", $type="", $length="", $contents="", $complete="N", $freeLearningUnitBlockID="", $freeLearningUnitClassBlockID="", $teachersNotes="", $outerBlock=TRUE) {	
	if ($outerBlock) {
		print "<div id='blockOuter$i' class='blockOuter'>" ;
	}
	if ($mode!="embed") {
		?>
		<style>
			.sortable { list-style-type: none; margin: 0; padding: 0; width: 100%; }
			.sortable div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 72px; }
			div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 72px; }
			html>body .sortable li { min-height: 58px; line-height: 1.2em; }
			.sortable .ui-state-highlight { margin-bottom: 5px; min-height: 72px; line-height: 1.2em; width: 100%; }
		</style>
											
		<script type='text/javascript'>
			$(function() {
				$( ".sortable" ).sortable({
					placeholder: "ui-state-highlight"
				});
			
				$( ".sortable" ).bind( "sortstart", function(event, ui) { 
					$("#blockInner<?php print $i ?>").css("display","none") ;
					$("#block<?php print $i ?>").css("height","72px") ;
					$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)"); 
					tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php print $i ?>') ;
					tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php print $i ?>') ;
					$(".sortable").sortable( "refresh" ) ;
					$(".sortable").sortable( "refreshPositions" ) ;
				});
			});
			
		</script>
		<script type='text/javascript'>	
			$(document).ready(function(){
				$("#blockInner<?php print $i ?>").css("display","none");
				$("#block<?php print $i ?>").css("height","72px")
			
				//Block contents control
				$('#show<?php print $i ?>').unbind('click').click(function() {
					if ($("#blockInner<?php print $i ?>").is(":visible")) {
						$("#blockInner<?php print $i ?>").css("display","none");
						$("#block<?php print $i ?>").css("height","72px")
						$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)"); 
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php print $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php print $i ?>') ;
					} else {
						$("#blockInner<?php print $i ?>").slideDown("fast", $("#blockInner<?php print $i ?>").css("display","table-row")); 
						$("#block<?php print $i ?>").css("height","auto")
						$('#show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\'"?>)"); 
						tinyMCE.execCommand('mceRemoveEditor', false, 'contents<?php print $i ?>') ;	
						tinyMCE.execCommand('mceAddEditor', false, 'contents<?php print $i ?>') ;
						tinyMCE.execCommand('mceRemoveEditor', false, 'teachersNotes<?php print $i ?>') ;	
						tinyMCE.execCommand('mceAddEditor', false, 'teachersNotes<?php print $i ?>') ;
					}
				});
			
				<?php if ($mode=="masterAdd") { ?>
					var titleClick<?php print $i ?>=false ;
					$('#title<?php print $i ?>').focus(function() {
						if (titleClick<?php print $i ?>==false) {
							$('#title<?php print $i ?>').css("color", "#000") ;
							$('#title<?php print $i ?>').val("") ;
							titleClick<?php print $i ?>=true ;
						}
					});
				
					var typeClick<?php print $i ?>=false ;
					$('#type<?php print $i ?>').focus(function() {
						if (typeClick<?php print $i ?>==false) {
							$('#type<?php print $i ?>').css("color", "#000") ;
							$('#type<?php print $i ?>').val("") ;
							typeClick<?php print $i ?>=true ;
						}
					});
				
					var lengthClick<?php print $i ?>=false ;
					$('#length<?php print $i ?>').focus(function() {
						if (lengthClick<?php print $i ?>==false) {
							$('#length<?php print $i ?>').css("color", "#000") ;
							$('#length<?php print $i ?>').val("") ;
							lengthClick<?php print $i ?>=true ;
						}
					});
				<?php } ?>
			
				$('#delete<?php print $i ?>').unbind('click').click(function() {
					if (confirm("<?php print _('Are you sure you want to delete this record?') ?>")) {
						$('#block<?php print $i ?>').fadeOut(600, function(){ $('#block<?php print $i ?>').remove(); });
					}
				});
			});
		</script>
		<?php
	}
	?>
	<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="block<?php print $i ?>" style='padding: 0px'>
		<table class='blank' cellspacing='0' style='width: 100%'>
			<tr>
				<td style='width: 50%'>
					<input name='order[]' type='hidden' value='<?php print $i ?>'>
					<input <?php if ($mode=="embed") { print "readonly" ; } ?> maxlength=100 id='title<?php print $i ?>' name='title<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="masterAdd") { print "color: #999;" ;} ?> margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php if ($mode=="masterAdd") { print sprintf(_('Block %1$s'), $i) ;} else { print htmlPrep($title) ;} ?>'><br/>
					<input <?php if ($mode=="embed") { print "readonly" ; } ?> maxlength=50 id='type<?php print $i ?>' name='type<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="masterAdd") { print "color: #999;" ;} ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php if ($mode=="masterAdd") { print _("type (e.g. discussion, outcome)") ;} else { print htmlPrep($type) ;} ?>'>
					<input <?php if ($mode=="embed") { print "readonly" ; } ?> maxlength=3 id='length<?php print $i ?>' name='length<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; <?php if ($mode=="masterAdd") { print "color: #999;" ;} ?> margin-top: 2px; font-size: 110%; font-style: italic; width: 95px' value='<?php if ($mode=="masterAdd") { print _("length (min)") ;} else { print htmlPrep($length) ;} ?>'>
				</td>
				<td style='text-align: right; width: 50%'>
					<div style='margin-bottom: 5px'>
						<?php
						print "<img id='delete$i' title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/> " ;
						print "<div title='" . _('Show/Hide Details') . "' id='show$i' style='margin-right: 3px; margin-top: -1px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\"); background-repeat: no-repeat'></div></br>" ;
						?>
					</div>
					<input type='hidden' name='freeLearningUnitBlockID<?php print $i ?>' value='<?php print $freeLearningUnitBlockID ?>'>
					<input type='hidden' name='freeLearningUnitClassBlockID<?php print $i ?>' value='<?php print $freeLearningUnitClassBlockID ?>'>
				</td>
			</tr>
			<tr id="blockInner<?php print $i ?>">
				<td colspan=2 style='vertical-align: top'>
					<?php 
					if ($mode=="masterAdd") { 
						$contents=getSettingByScope($connection2, "Planner", "smartBlockTemplate" ) ; 
					}
					print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>" . _('Block Contents') . "</div>" ;
					//Block Contents
					if ($mode!="embed") {
						print getEditor($guid, FALSE, "contents$i", $contents, 20, true, false, false, true) ;
					}
					else {
						print "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important'><p>$contents</p></div>" ;
					}
					
					//Teacher's Notes
					if ($mode!="embed") {
						print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>" . _('Teacher\'s Notes') . "</div>" ;
						print getEditor($guid, FALSE, "teachersNotes$i", $teachersNotes, 20, true, false, false, true) ;
					}
					else if ($teachersNotes!="") {
						print "<div style='text-align: left; font-weight: bold; margin-top: 15px'>" . _('Teacher\'s Notes') . "</div>" ;
						print "<div style='max-width: 595px; margin-right: 0!important; padding: 5px!important; background-color: #F6CECB'><p>$teachersNotes</p></div>" ;
					}
					?>
				</td>
			</tr>
		</table>
	</div>
	<?php
	if ($outerBlock) {
		print "</div>" ;
	}
}


//Make the display for a block, according to the input provided, where $i is a unique number appended to the block's field ids.
function makeBlockOutcome($guid,  $i, $type="", $gibbonOutcomeID="", $title="", $category="", $contents="", $id="", $outerBlock=TRUE, $allowOutcomeEditing="Y") {	
	if ($outerBlock) {
		print "<div id='" . $type . "blockOuter$i'>" ;
	}
	?>
		<script>
			$(function() {
				$( "#<?php print $type ?>" ).sortable({
					placeholder: "<?php print $type ?>-ui-state-highlight"
				});
				
				$( "#<?php print $type ?>" ).bind( "sortstart", function(event, ui) { 
					$("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","none");
					$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
					$('#<?php print $type ?>show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)");  
					tinyMCE.execCommand('mceRemoveEditor', false, '<?php print $type ?>contents<?php print $i ?>') ;
					$("#<?php print $type ?>").sortable( "refreshPositions" ) ;
				});
				
				$( "#<?php print $type ?>" ).bind( "sortstop", function(event, ui) {
					//This line has been removed to improve performance with long lists
					//tinyMCE.execCommand('mceAddEditor', false, '<?php print $type ?>contents<?php print $i ?>') ;
					$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
				});
			});
		</script>
		<script type="text/javascript">
			$(document).ready(function(){
				$("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","none");
				$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
				
				//Block contents control
				$('#<?php print $type ?>show<?php print $i ?>').unbind('click').click(function() {
					if ($("#<?php print $type ?>BlockInner<?php print $i ?>").is(":visible")) {
						$("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","none");
						$("#<?php print $type ?>Block<?php print $i ?>").css("height","72px") ;
						$('#<?php print $type ?>show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\'"?>)");  
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php print $type ?>contents<?php print $i ?>') ;
					} else {
						$("#<?php print $type ?>BlockInner<?php print $i ?>").slideDown("fast", $("#<?php print $type ?>BlockInner<?php print $i ?>").css("display","table-row")); 
						$("#<?php print $type ?>Block<?php print $i ?>").css("height","auto")
						$('#<?php print $type ?>show<?php print $i ?>').css("background-image", "<?php print "url(\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/minus.png\'"?>)");  
						tinyMCE.execCommand('mceRemoveEditor', false, '<?php print $type ?>contents<?php print $i ?>') ;	
						tinyMCE.execCommand('mceAddEditor', false, '<?php print $type ?>contents<?php print $i ?>') ;
					}
				});
				
				$('#<?php print $type ?>delete<?php print $i ?>').unbind('click').click(function() {
					if (confirm("Are you sure you want to delete this record?")) {
						$('#<?php print $type ?>blockOuter<?php print $i ?>').fadeOut(600, function(){ $('#<?php print $type ?><?php print $i ?>'); });
						$('#<?php print $type ?>blockOuter<?php print $i ?>').remove();
						<?php print $type ?>Used[<?php print $type ?>Used.indexOf("<?php print $gibbonOutcomeID ?>")]="x" ;
					}
				});
				
			});
		</script>
		<div class='hiddenReveal' style='border: 1px solid #d8dcdf; margin: 0 0 5px' id="<?php print $type ?>Block<?php print $i ?>" style='padding: 0px'>
			<table class='blank' cellspacing='0' style='width: 100%'>
				<tr>
					<td style='width: 50%'>
						<input name='<?php print $type ?>order[]' type='hidden' value='<?php print $i ?>'>
						<input name='<?php print $type ?>gibbonOutcomeID<?php print $i ?>' type='hidden' value='<?php print $gibbonOutcomeID ?>'>
						<input readonly maxlength=100 id='<?php print $type ?>title<?php print $i ?>' name='<?php print $type ?>title<?php print $i ?>' type='text' style='float: none; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 0px; font-size: 140%; font-weight: bold; width: 350px' value='<?php print $title ; ?>'><br/>
						<input readonly maxlength=100 id='<?php print $type ?>category<?php print $i ?>' name='<?php print $type ?>category<?php print $i ?>' type='text' style='float: left; border: 1px dotted #aaa; background: none; margin-left: 3px; margin-top: 2px; font-size: 110%; font-style: italic; width: 250px' value='<?php print $category ; ?>'>
						<script type="text/javascript">
							if($('#<?php print $type ?>category<?php print $i ?>').val()=="") {
								$('#<?php print $type ?>category<?php print $i ?>').css("border","none") ;
							}
						</script>
					</td>
					<td style='text-align: right; width: 50%'>
						<div style='margin-bottom: 25px'>
							<?php
							print "<img id='" . $type  . "delete$i' title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/> " ;
							print "<div id='" . $type . "show$i' title='" . _('Show/Hide Details') . "' style='margin-right: 3px; margin-left: 3px; padding-right: 1px; float: right; width: 25px; height: 25px; background-image: url(\"" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png\"); background-repeat: no-repeat'></div>" ;
							?>
						</div>
						<input type='hidden' name='id<?php print $i ?>' value='<?php print $id ?>'>
					</td>
				</tr>
				<tr id="<?php print $type ?>BlockInner<?php print $i ?>">
					<td colspan=2 style='vertical-align: top'>
						<?php 
							if ($allowOutcomeEditing=="Y") {
								print getEditor($guid, FALSE, $type . "contents" . $i, $contents, 20, false, false, false, true) ;
							}
							else {
								print "<div style='padding: 5px'>$contents</div>" ;
								print "<input type='hidden' name='" . $type . "contents" . $i . "' value='" . htmlPrep($contents) . "'/>" ;
							}
						?>
					</td>
				</tr>
			</table>
		</div>
	<?php
	if ($outerBlock) {
		print "</div>" ;
	}
}
?>
