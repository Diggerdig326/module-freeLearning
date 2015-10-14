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

include "../../functions.php" ;
include "../../config.php" ;

include "./moduleFunctions.php" ;

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$id=$_GET["id"] ;
$type=$_GET["type"] ;
$gibbonOutcomeID=$_GET["gibbonOutcomeID"] ;
$title=$_GET["title"] ;
$category=$_GET["category"] ;
$contents=$_GET["contents"] ;
$allowOutcomeEditing=$_GET["allowOutcomeEditing"] ;

makeBlockOutcome($guid,  $id, $type, $gibbonOutcomeID, $title, $category, $contents, "", FALSE, $allowOutcomeEditing) ;
?>