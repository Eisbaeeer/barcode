<?php
/*
   ----------------------------------------------------------------------
   GLPI - Gestionnaire Libre de Parc Informatique
   Copyright (C) 2003-2005 by the INDEPNET Development Team.

   http://indepnet.net/   http://glpi.indepnet.org
   ----------------------------------------------------------------------

   LICENSE

   This file is part of GLPI.

   GLPI is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   GLPI is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with GLPI; if not, write to the Free Software
   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
   ------------------------------------------------------------------------
 */

// Original Author of file: GRISARD Jean Marc
// Purpose of file:
// ----------------------------------------------------------------------


class plugin_barscode extends CommonDBTM {

	function plugin_barscode () {
		$this->table="glpi_plugin_barscode_config";
	}

	function title(){

		GLOBAL  $langbc,$HTMLRel;

		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"./pics/barscode.png\" alt='".$langbc["title"][0]."' title='".$langbc["title"][0]."'></td><td align ='center'><b><span class='icon_nav'>".$langbc["title"][0]."</span>";
		echo "</b><tr><td>&nbsp;</td></tr></tr></table>&nbsp;</div>";
	}

}

class plugin_barscode_Profile extends CommonDBTM {

	function plugin_barscode_Profile () {
		$this->table="glpi_plugin_barscode_profiles";
		$this->type=-1;
	}

	function post_updateItem($input,$updates,$history=1) {
		global $db;

		if (isset($input["is_default"])&&$input["is_default"]==1){
			$query="UPDATE glpi_plugin_barscode_profiles SET `is_default`='0' WHERE ID <> '".$input['ID']."'";
			$db->query($query);
		}
	}


	/**
	 * Print a good title for profiles pages
	 *
	 *
	 *
	 *
	 *@return nothing (diplays)
	 *
	 **/
	function title(){
		//titre

		global  $lang,$HTMLRel;

		echo "<div align='center'><table border='0'><tr><td>";
		echo "<img src=\"".$HTMLRel."pics/preferences.png\" alt='".$lang["Menu"][35]."' title='".$lang["Menu"][35]."'></td><td><span class='icon_sous_nav'><b>".$lang["Menu"][35]."</b></span>";
		echo "</td>";

		echo "</tr></table></div>";
	}

	function updateForUser($ID,$prof){
		global $db;
		// Get user profile
		$query = "SELECT FK_profiles, ID FROM glpi_users_profiles WHERE (FK_users = '$ID')";
		if ($result = $db->query($query)) {
			// Profile found
			if ($db->numrows($result)){
				$data=$db->fetch_array($result);
				if ($data["FK_profiles"]!=$prof){
					$query="UPDATE glpi_users_profiles SET FK_profiles='$prof' WHERE ID='".$data["ID"]."';";
					$db->query($query);
				}
			} else { // Profile not found
				$query="INSERT INTO glpi_users_profiles (FK_users, FK_profiles) VALUES ('$ID','$prof');";
				$db->query($query);
			}
		}

	}

	function getFromDBForUser($ID){

		// Make new database object and fill variables
		global $db;
		$ID_profile=0;
		// Get user profile
		$query = "SELECT FK_profiles FROM glpi_users_profiles WHERE (FK_users = '$ID')";

		if ($result = $db->query($query)) {
			if ($db->numrows($result)){
				$ID_profile = $db->result($result,0,0);
			} else {
				// Get default profile
				$query = "SELECT ID FROM glpi_plugin_barscode_profiles WHERE (`is_default` = '1')";
				$result = $db->query($query);
				if ($db->numrows($result)){
					$ID_profile = $db->result($result,0,0);
					$this->updateForUser($ID,$ID_profile);
				} else {
					// Get first helpdesk profile
					$query = "SELECT ID FROM glpi_plugin_barscode_profiles WHERE (interface = 'helpdesk')";
					$result = $db->query($query);
					if ($db->numrows($result)){
						$ID_profile = $db->result($result,0,0);
					}
				}
			}
		}
		if ($ID_profile){
			$this->updateForUser($ID,$ID_profile);
			return $this->getFromDB($ID_profile);
		} else return false;
	}
	// Unset unused rights for helpdesk


	function showprofileForm($target,$ID){
		global $lang,$cfg_glpi,$langbc;

		if (!haveRight("profile","r")) return false;

		$onfocus="";
		if ($ID){
			$this->getFromDB($ID);
		} else {
			$this->getEmpty();
			$onfocus="onfocus=\"this.value=''\"";
		}

		if (empty($this->fields["interface"])) $this->fields["interface"]="barscode";
		if (empty($this->fields["name"])) $this->fields["name"]=$lang["common"][0];


		echo "<form name='form' method='post' action=\"$target\">";
		echo "<div align='center'>";
		echo "<table class='tab_cadre'><tr>";
		echo "<th>".$lang["common"][16].":</th>";
		echo "<th><input type='text' name='name' value=\"".$this->fields["name"]."\" $onfocus></th>";
		echo "<th>".$lang["profiles"][2].":</th>";
		echo "<th><select name='interface' id='profile_interface'>";
		echo "<option value=''>----</option>";
		echo "<option value='barscode' ".($this->fields["interface"]!="barscode"?"selected":"").">".$langbc["profile"][1]."</option>";

		echo "</select></th>";
		echo "</tr></table>";
		echo "</div>";

		echo "<script type='text/javascript' >\n";
		echo "   new Form.Element.Observer('profile_interface', 1, \n";
		echo "      function(element, value) {\n";
		echo "      	new Ajax.Updater('profile_form','".$cfg_glpi["root_doc"]."/plugins/barscode/ajax/profiles.php',{asynchronous:true, evalScripts:true, \n";
		echo "           method:'post', parameters:'interface=' + value+'&ID=$ID'\n";
		echo "})});\n";
		echo "document.getElementById('profile_interface').value='".$this->fields["interface"]."';";
		echo "</script>\n";
		echo "<br>";

		echo "<div align='center' id='profile_form'>";
		echo "</div>";

		echo "</form>";

	}

	function showbarscodeForm($ID){
		global $lang,$langbc;

		if (!haveRight("profile","r")) return false;
		$canedit=haveRight("profile","w");

		if ($ID){
			$this->getFromDB($ID);
		} else {
			$this->getEmpty();
		}

		echo "<table class='tab_cadre'><tr>";

		echo "<tr><th colspan='2' align='center'><strong>".$langbc["profile"][0]."</strong></td></tr>";

		echo "<tr class='tab_bg_2'>";
		echo "<td>".$langbc["profile"][1].":</td><td>";
		dropdownNoneReadWrite("barscode",$this->fields["barscode"],1,1,0);
		echo "</td>";
		echo "</tr>";

		echo "</tr>";




		if ($canedit){
			echo "<tr class='tab_bg_1'>";
			if ($ID){
				echo "<td  align='center'>";
				echo "<input type='hidden' name='ID' value=$ID>";
				echo "<input type='submit' name='update' value=\"".$lang["buttons"][7]."\" class='submit'>";
				echo "</td><td  align='center'>";
				echo "<input type='submit' name='delete' value=\"".$lang["buttons"][6]."\" class='submit'>";
			} else {
				echo "<td colspan='2' align='center'>";
				echo "<input type='submit' name='add' value=\"".$lang["buttons"][8]."\" class='submit'>";
			}
			echo "</td></tr>";
		}
		echo "</table>";

	}

}?>
