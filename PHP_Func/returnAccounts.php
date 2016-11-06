<?php
include "../Objects.php";
session_start();

$tableName = "";
$columnName = "";
$columnsToSelect = array();

// Selects the appropriate column and table names for the query
switch ($_POST['search_for']) {
	case "STUDENT": {
		$tableName .= "users";
		array_push($columnsToSelect, "username", "firstname", "middlenames", "surname", "course_code", "year_of_study");
		
		switch ($_POST['search_by']) {
			case "USERNAME":
				$columnName .= "username";
				break;

			case "FIRST NAME":
				$columnName .= "firstname";
				break;

			case "MIDDLE NAME":
				$columnName .= "middlenames";
				break;

			case "SURNAME":
				$columnName .= "surname";
				break;

			case "COURSE CODE":
				$columnName .= "course_code";
				break;

			case "YEAR OF STUDY":
				$columnName .= "year_of_study";
				break;
			
			default:
				echo("That's not valid!");
				header("Location: ../index.php");
		}
			
		break;
	}
	
	case "LECTURER": {
		$tableName .= "users";
		array_push($columnsToSelect, "username", "firstname", "middlenames", "surname");
		
		switch ($_POST['search_by']) {
			case "USERNAME":
				$columnName .= "username";
				break;

			case "FIRST NAME":
				$columnName .= "firstname";
				break;

			case "MIDDLE NAME":
				$columnName .= "middlenames";
				break;

			case "SURNAME":
				$columnName .= "surname";
				break;
			
			default:
				echo("That's not valid!");
				header("Location: ../index.php");
		}
		
		break;
	}
		
	case "COURSE": {
		$tableName .= "courses";
		
		switch ($_POST['search_by']) {
			case "CODE":
				$columnName .= "code";
				break;

			case "TITLE":
				$columnName .= "title";
				break;

			case "LENGTH":
				$columnName .= "years";
				break;
			
			default:
				echo("That's not valid!");
				header("Location: ../index.php");
		}
		
		break;
	}
	
	case "MODULE": {
		$tableName .= "modules";
		
		switch ($_POST['search_by']) {
			case "CODE":
				$columnName .= "code";
				break;

			case "TITLE":
				$columnName .= "name";
				break;

			case "DESCRIPTION":
				$columnName .= "description";
				break;
				
			case "YEAR TAUGHT":
				$columnName .= "year_taught";
				break;
			
			default:
				echo("That's not valid!");
				header("Location: ../index.php");
		}
		
		break;
	}
		
	case "TOPIC": {
		$tableName .= "topics";
		
		switch ($_POST['search_by']) {
			case "CODE":
				$columnName .= "code";
				break;

			case "TITLE":
				$columnName .= "name";
				break;

			case "DESCRIPTION":
				$columnName .= "description";
				break;
			
			default:
				echo("That's not valid!");
				header("Location: ../index.php");
		}
		
		break;
	}
		
	default:
		echo("That's not valid!");
		header("Location: ../index.php");
}

$query = "";
$parameters = array();

// If only certain columns need to be selected, select those rather than everything - used for extracting user details
if (count($columnsToSelect) > 0) {
	$columnsToSelect = implode(", ", $columnsToSelect);
	$query .= "SELECT $columnsToSelect FROM $tableName WHERE $columnName";
}

// If all else, select all columns from the appropriate table - used for extracting course / module / topic infomation
else {
	$query .= "SELECT * FROM $tableName WHERE $columnName";
}

// query changes from = to LIKE since descriptions can be quite long
if ($columnName == "description") {
	$query .= " LIKE ?;";
    array_push($parameters, "%" + $_POST['search_text'] + "%");
}

if ($tableName == "users") {
    $query .= " = ? AND lower(username) NOT LIKE ?;";
    array_push($parameters, $_POST['search_text'], "%admin%");
}


else {
	$query .= " = ?;";
    array_push($parameters, $_POST['search_text']);
}

$results = Database::ExecuteQuery($query, $parameters);

echo(json_encode($results));
?>