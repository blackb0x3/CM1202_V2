<?php
include "Objects.php";
session_start();
$error = "";

function CredentialsCorrect($inUser, $inPass) {
	return Tools::CheckPassword($inUser, $inPass);
}

if (isset($_SESSION['user'])) {
	header("Location: index.php");
}

if (isset($_POST['username']) && isset($_POST['password'])) {
	if (CredentialsCorrect($_POST['username'], $_POST['password'])) {
		$newUser = Database::ExecuteQuery("SELECT username, firstname, middlenames, surname, course_code, year_of_study FROM users WHERE username = ?;", array($_POST['username']))[0]; // Gets the user details from the database on successful login
		
		$username = $newUser['username'];
		$fullName = Tools::CreateName($newUser['firstname'], $newUser['middlenames'], $newUser['surname']);
		$courseCode = $newUser['course_code'];
        $currentYear = $newUser['year_of_study'];
		
		// checks for course code to see if user is a student
		if ($courseCode != null && $currentYear != null) {
			$_SESSION['user'] = new Student($username, $fullName, $courseCode, $currentYear);
		}
		
		else {
			// If the word "Admin" is contained in the username, the user must be an administrator for the system
			if (strpos(strtolower($username), "admin") == true) {
				$_SESSION['user'] = new Administrator($username, $fullName);
			}
			
			else {
				$_SESSION['user'] = new Lecturer($username, $fullName);
			}
		}
		
		header("Location: index.php");
	}
	
	else {
		$error .= "INCORRECT LOGIN DETAILS ENTERED. PLEASE TRY AGAIN.";
	}
}
?>

<!DOCTYPE html>
<html>
	<head>
		<title>CM1202 Remake</title>
		<meta charset='utf-8'>
  		<meta name='viewport' content='width=device-width, initial-scale=1'>
  		<link rel='stylesheet' href='bootstrap/css/bootstrap.min.css' />
  		<script src='jquery/jquery-3.1.0.min.js'></script>
  		<script src='bootstrap/js/bootstrap.min.js'></script>
		<script src='bootstrap/js/bootbox.js'></script>
		<style>
			* {
				font-family: Verdana, Ubuntu, Arial, sans-serif;
				text-align: center;
			}
			
			body {
				background-color: cornflowerblue;
			}
			
			#login_box {
				border-top: 2px solid black;
				border-bottom: 2px solid black;
			}
		</style>
	</head>
	<body>
		<div class='col-lg-12' id='site-container'>
			<div id='title'>
				<h1><em>CM1202 REMAKE</em></h1>
			</div>
			<br/>
			<div class='col-lg-12' id='login_box'>
				<div id='login_instructions'>
					<h3><em>ENTER LOGIN DETAILS</em></h3><br/>
				</div>
				<div id='login_form'>
					<form action='<?php echo(basename($_SERVER['PHP_SELF'])); ?>' method='POST'>
                        <div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6 col-lg-offset-3 col-md-offset-3 col-sm-offset-3'>
                            <input class='form-control' type='text' name='username' placeholder='USERNAME' required />
                        </div><br/>
                        <div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6 col-lg-offset-3 col-md-offset-3 col-sm-offset-3'>
                            <input class='form-control' type='password' name='password' placeholder='PASSWORD' required />
                        </div><br/>
                        <div class='input-group'>
                            <span class='input-group-btn'>
                                <button class='btn btn-default' style='border-radius: 4px;' type='submit'>LOGIN</button>
                            </span>
					    </div><br/>
					</form>
				</div>
				<br/>
				<div id='login_error'>
					<p><?php echo($error); ?></p>
				</div>
			</div>
		</div>
	</body>
</html>