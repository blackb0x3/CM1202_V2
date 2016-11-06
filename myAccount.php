<?php
include "Objects.php";
session_start();

if (!isset($_SESSION['user'])) {
	header("Location: login.php");
}

$currentUser = $_SESSION['user'];
$message = "";

if (isset($_POST['first_name']) || isset($_POST['middle_names']) || isset($_POST['surname'])) {
    $newfirstname = $_POST['first_name'];
    $newmiddlenames = $_POST['middle_names'];
    $newsurname = $_POST['surname'];
    
    if ($newfirstname != "") {
        $currentUser->UpdateFirstName($newfirstname);
    }
    
    if ($newmiddlenames != "") {
        $currentUser->UpdateMiddleNames($newmiddlenames);
    }
    
    if ($newsurname != "") {
        $currentUser->UpdateSurname($newsurname);
    }

    $message .= "<div class='alert alert-success fade in'>
    <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
    <strong>Success!</strong> Your details have been updated.
  </div>";
}

else if (isset($_POST['old_password']) && isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    
    if ($oldPassword == $newPassword) {
        $message .= "<div class='alert alert-danger fade in'>
        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
        <strong>Error!</strong> The new password you entered is the same as your old password.
      </div>";
    }
    
    else {
    
        $success = $currentUser->UpdatePassword($oldPassword, $newPassword);

        if (!$success) {
            $message .= "<div class='alert alert-danger fade in'>
        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
        <strong>Error!</strong> The old password you entered was incorrect.
      </div>";
        }

        else {
            $message .= "<div class='alert alert-success fade in'>
        <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
        <strong>Success!</strong> Your password was changed.
      </div>";
        }
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
		<script src='cm1202.js'></script>
		<style>
			* {
				font-family: Verdana, Ubuntu, Arial, sans-serif;
			}
			
			.button {
				border-radius: 8px;
			}
			
			body {
				background-color: cornflowerblue;
			}
			
			#mainnav {
				text-align: center;
			}
		</style>
	</head>
	<body>
		<div class='col-lg-12' id='site_container'>
			<div id='mainnav' class='col-lg-12'>
				<?php echo(Tools::CreateNavBar($currentUser)); ?>
			</div><br/>
			<div class='col-lg-12' id='userData'>
                <div class='col-lg-12' id='yourAccountGreeting'>
				    <h3 style='text-align: center;'>YOUR ACCOUNT</h3>
				</div><br/>
                <div class='col-lg-12' id='name_form_div'>
                    <form action='<?php echo(basename($_SERVER['PHP_SELF'])); ?>' method="POST" id='name_form'>
                        <div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6'>
                            <span class='input-group-addon' id='first-name-addon'>First Name:</span>
                            <input class='form-control' type='text' id='first_name' name='first_name' aria-describedby='first-name-addon' placeholder='<?php echo($currentUser->GetFirstName()); ?>' />
                        </div><br/>
                        <div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6'>
                            <span class='input-group-addon' id='middle-names-addon'>Middle Names:</span>
                            <input class='form-control' type='text' id='middle_names' name='middle_names' aria-describedby='middle-names-addon' placeholder='<?php echo($currentUser->GetMiddleNames()); ?>' />
                        </div><br/>
                        <div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6'>
                            <span class='input-group-addon' id='surname-addon'>Surname:</span>
                            <input class='form-control' type='text' id='surname' name='surname' aria-describedby='surname-addon' placeholder='<?php echo($currentUser->GetSurname()); ?>' />
                        </div><br/>
                        <div class='input-group'>
                            <span class='input-group-btn'>
                                <button style='border-radius: 4px;' class='btn btn-default' type='button' name='save_name_changes' id='save_name_changes'>Confirm Name Changes</button>
                            </span>
                        </div><br/>
                        <div class='input-group'>
                            <span class='input-group-btn'>
                                <button style='border-radius: 4px;' class='btn btn-default' type='reset' name='clear' id='clear'>Reset</button>
                            </span>
                        </div><br/>
                    </form>
                </div>
                <div class='col-lg-12' id='otherOptions'>
                    <div class='col-lg-12' id='yourAccountGreeting'>
                        <h3 style='text-align: center;'>OTHER OPTIONS</h3>
                    </div><br/>
                    <div class='input-group'>
						<span class='input-group-btn'>
							<button style='border-radius: 4px;' class='btn btn-default' type='button' name='change_password' id='change_password' data-toggle='modal' data-target='#passwordModal'>Change Password</button>
						</span>
					</div><br/>
                </div>
			</div>
            <div class='col-lg-12' id='message'>
                <?php echo($message); ?>
            </div>
            <?php
            $passwordModalBody = "<p>Tips for choosing a new password:</p>
            <ul id='password_tips'>
                <li>Make sure the length of your new password is quite long. Aim for <b>at least 10</b> characters.</li>
                <li>Make sure your password uses both <b>uppercase and lowercase</b> letters.</li>
                <li>Make sure your password uses some <b>numeric digits</b>.</li>
                <li>Make sure your password uses <b>interesting symbols</b> (e.g. $, &amp, ?, &gt)</li>
            </ul><br/>
            <form action='" . basename($_SERVER['PHP_SELF']) . "' method='POST' id='password_form'>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='old-password-addon'>Old Password</span>
                    <input class='form-control' type='password' id='old_password' name='old_password' aria-describedby='old-password-addon' />
                </div><br/>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='new-password-addon'>New Password</span>
                    <input class='form-control' type='password' id='new_password' name='new_password' aria-describedby='new-password-addon' />
                </div><br/>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='confirm-password-addon'>Confirm New Password</span>
                    <input class='form-control' type='password' id='confirm_password' name='confirm_password' aria-describedby='confirm-password-addon' />
                </div><br/>
                <div class='input-group'>
                    <span class='input-group-btn'>
                        <button style='border-radius: 4px;' class='btn btn-default' type='reset' name='clear' id='clear'>Reset</button>
                    </span>
                </div>
                <div class='input-group'>
                    <span class='input-group-btn'>
                        <button style='border-radius: 4px;' class='btn btn-default' type='button' name='save_password_change' id='save_password_change'>Change Password</button>
                    </span>
                </div>
            </form>";
            echo(Tools::CreateModal("passwordModal", "Change Password", $passwordModalBody));
            ?>
		</div>
        <script>
            var openPasswordModal = document.getElementById("change_password");
            var changeName = document.getElementById("save_name_changes");
            var changePassword = document.getElementById("save_password_change");

            var oldfirstname = document.getElementById("first_name").getAttribute("placeholder");
            var oldmiddlenames = document.getElementById("middle_names").getAttribute("placeholder");
            var oldsurname = document.getElementById("surname").getAttribute("placeholder");

            changeName.onclick = function() {
                var newfirstname = document.getElementById("first_name").value;
                var newmiddlenames = document.getElementById("middle_names").value;
                var newsurname = document.getElementById("surname").value;

                if (newfirstname == "" && newmiddlenames == "" && newsurname == "") {
                    bootstrapAlert("You haven't entered a new name for any of the fields! <br/> Please enter a new first name, middle name or surname that you want to change.", "ERROR");
                }

                else {
                    var message = "The following new names entered are the same as your current name:<br/><br/><ul>";
                    if (newfirstname == oldfirstname) {
                        message += "<li>'<b>" + newfirstname + "</b>' is the same as '<b>" + oldfirstname + "</b>'</li>";
                    }

                    if (newmiddlenames == oldmiddlenames) {
                        message += "<li>'<b>" + newmiddlenames + "</b>' is the same as '<b>" + oldmiddlenames + "</b>'</li>";
                    }

                    if (newsurname == oldsurname) {
                        message += "<li>'<b>" + newsurname + "</b>' is the same as '<b>" + oldsurname + "</b>'</li>";
                    }

                    message += "</ul>"

                    if (message != "The following new names entered are the same as your current name:<br/><br/><ul></ul>") {
                        bootstrapAlert(message, "ERROR");
                    }

                    else {
                        var confirmMessage = "Are you sure you want to make these name changes to your account: <br/><ul>";

                        (newfirstname != "") ? confirmMessage += "<li>Change '<b>" + oldfirstname + "</b>' to '<b>" + newfirstname + "</b>'</li>" : confirmMessage += "<li>Keep your first name the same.</li>";

                        (newmiddlenames != "") ? confirmMessage += "<li>Change '<b>" + oldmiddlenames + "</b>' to '<b>" + newmiddlenames + "</b>'</li>" : confirmMessage += "<li>Keep your middle name&#47s the same.</li>";

                        (newsurname != "") ? confirmMessage += "<li>Change '<b>" + oldsurname + "</b>' to '<b>" + newsurname + "</b>'</li>" : confirmMessage += "<li>Keep your surname the same.</li>";

                        confirmMessage += "</ul>";
                        var callback = function() {
                            document.getElementById("name_form").submit();
                        }

                        bootstrapConfirm(confirmMessage, callback, { title: "Confirm name changes?"});
                    }
                }
            };

            openPasswordModal.onclick = function() {
                document.getElementById("old_password").value = "";
                document.getElementById("new_password").value = "";
                document.getElementById("confirm_password").value = "";
            };

            changePassword.onclick = function() {
                var oldpassword = document.getElementById("old_password").value;
                var newpassword = document.getElementById("new_password").value;
                var confirmpassword = document.getElementById("confirm_password").value;

                if (oldpassword == "" || newpassword == "" || confirmpassword == "") {
                    bootstrapAlert("You haven't entered all the password information we need! <br/> Please enter your old password and your new password, then confirm your new password so we can verify you want to change your password.", "ERROR");
                }

                else if (newpassword != confirmpassword) {
                    bootstrapAlert("The new password <b>doesn't match</b> the confirmed new password. Please make sure that the new password and confirm password values <b>are the same</b>.", "ERROR");
                }

                else {
                    var callback = function() {
                        document.getElementById("password_form").submit();
                    };

                    bootstrapConfirm("Are you sure you want to change your password?", callback, { title: "Confirm Password Change?", confirmLabel: "Yes, change my password.", cancelLabel: "No, don't make any changes." });
                }
            };
        </script>
	</body>
</html>