<?php
include "Objects.php";
session_start();

if (!isset($_SESSION['user'])) {
	header("Location: login.php");
}

$message = "";

// If new user form was filled out, add new user to database
if (get_class($_SESSION['user']) == "Administrator" &&
        isset($_POST['user_type']) &&
        isset($_POST['first_name']) &&
        isset($_POST['middle_names']) &&
        isset($_POST['surname']) &&
        isset($_POST['new_password'])) {
    
    $fullName = Tools::CreateName($_POST['first_name'], $_POST['middle_names'], $_POST['surname']);
    
    switch ($_POST['user_type']) {
        case "STUDENT":
            $usernameNum = Tools::GetTotalStudents() + 1;
            $username = "std" . $usernameNum;
            print($username);
            
            $newUser = new Student($username, $fullName, $_POST['course_code_newUser'], $_POST['year_of_study']);
            $_SESSION['user']->AddUser($newUser, $_POST['new_password']);
            break;
            
        case "LECTURER":
            $usernameNum = Tools::GetTotalLecturers() + 1;
            $username = "lct" . $usernameNum;
            
            $newUser = new Lecturer($username, $fullName);
            $_SESSION['user']->AddUser($newUser, $_POST['new_password']);
            break;
    }
}

// If new course form was filled out, add new course to database
else if (get_class($_SESSION['user']) == "Administrator" &&
        isset($_POST['course_code_newCourse']) &&
        isset($_POST['course_title']) &&
        isset($_POST['course_length'])) {
    
    $addCourseQuery = "INSERT INTO courses VALUES (?, ?, ?);";
    $courseParameters = array($_POST['course_code_newCourse'], $_POST['course_title'], $_POST['course_length']);
    
    Database::ExecuteQuery($addCourseQuery, $courseParameters);
}

else {
    $message .= "You're not allowed to do that.";
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
		<div class='col-lg-12' id='site-container'>
			<div id='mainnav' class='col-lg-12'>
				<?php echo(Tools::CreateNavBar($_SESSION['user'])); ?>
			</div>
            <?php
            if (get_class($_SESSION['user']) == "Administrator") {
                echo("<div id='notifications'>
                    <h3>NOTIFICATIONS</h3>
                </div>");
                
                echo("                <div id='tools'>
                    <h3>TOOLS</h3>
                    <div class='input-group'>
						<span class='input-group-btn'>
							<button style='border-radius: 4px;' class='btn btn-default' type='button' name='new_user' id='new_user' data-toggle='modal' data-target='#addUserModal'>Create New User</button>
						</span>
					</div><br/>
                    <div class='input-group'>
						<span class='input-group-btn'>
							<button style='border-radius: 4px;' class='btn btn-default' type='button' name='new_course' id='new_course' data-toggle='modal' data-target='#addCourseModal'>Create New Course</button>
						</span>
					</div><br/>
                </div>");
                $newUserModalBody = "<form action='" . basename($_SERVER['PHP_SELF']) . "' method='POST' id='new_user_form'>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='user-type-addon'>User Type:</span>
                    <select class='form-control' id='user_type' name='user_type' aria-describedby='user-type-addon' onchange='addStudentBoxes()'>
                        <option value='' selected>Select an Option...</option>
                        <option value='STUDENT'>Student</option>
                        <option value='LECTURER'>Lecturer</option>
                    </select>
                </div><br/>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='first-name-addon'>First Name:</span>
                    <input class='form-control' type='text' id='first_name' name='first_name' aria-describedby='first-name-addon' />
                </div><br/>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='middle-names-addon'>Middle Names:</span>
                    <input class='form-control' type='text' id='middle_names' name='middle_names' aria-describedby='middle-names-addon' />
                </div><br/>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='surname-addon'>Surname:</span>
                    <input class='form-control' type='text' id='surname' name='surname' aria-describedby='surname-addon' />
                </div><br/>
                <div style='visibility: hidden;' class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9' id='course-code'>
                    <span class='input-group-addon' id='course-code-addon'>Course Code:</span>
                    <input class='form-control' type='text' id='course_code_newUser' name='course_code_newUser' aria-describedby='course-code-addon' />
                </div><br/>
                <div style='visibility: hidden;' class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9' id='year-of-study'>
                    <span class='input-group-addon' id='year-of-study-addon'>Year of Study:</span>
                    <input class='form-control' type='number' id='year_of_study' name='year_of_study' aria-describedby='year-of-study-addon' min='1' max='5' />
                </div><br/>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='new-password-addon'>New Password</span>
                    <input class='form-control' type='password' id='new_password' name='new_password' aria-describedby='new-password-addon' />
                </div><br/>
                <div class='input-group'>
                    <span class='input-group-btn'>
                        <button style='border-radius: 4px;' class='btn btn-default' type='button' name='create_user' id='create_user'>Create User</button>
                    </span>
                </div>
            </form>";
                $newCourseModalBody = "<form action='" . basename($_SERVER['PHP_SELF']) . "' method='POST' id='new_course_form'>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='course-code-addon'>Course Code</span>
                    <input class='form-control' type='text' id='course_code_newCourse' name='course_code_newCourse' aria-describedby='course-code-addon' />
                </div><br/>
                
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='course-title-addon'>Course Title</span>
                    <input class='form-control' type='text' id='course_title' name='course_title' aria-describedby='course-title-addon' />
                </div><br/>
                
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='course-length-addon'>Course Length</span>
                    <input class='form-control' type='text' id='course_length' name='course_length' aria-describedby='course-length-addon' />
                </div><br/>
                
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='module-name-addon'>Module Name</span>
                    <input class='form-control' type='text' id='module_name_newModule' name='module_name_newModule' aria-describedby='module-name-addon' />
                </div><br/>
                
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='module-courseList-addon'>Modules Added</span>
                    <textarea class='form-control' id='module_courseList_newModule' name='module_courseList_newModule' aria-describedby='module-courseList-addon' cols='20' rows='10'></textarea>
                </div><br/>
                
                <div class='input-group'>
                    <span class='input-group-btn'>
                        <button style='border-radius: 4px;' class='btn btn-default' type='button' name='create_course' id='create_course'>Create Course</button>
                    </span>
                </div>
            </form>";
                echo(Tools::CreateModal("addUserModal", "Create New User", $newUserModalBody));
                echo(Tools::CreateModal("addCourseModal", "Create New Course", $newCourseModalBody));
                echo("<script>
                    var addUser = document.getElementById('create_user');
                    var addCourse = document.getElementById('create_course');
                    
                    function addStudentBoxes() {
                        var userType = document.getElementById('user_type');
                        var courseCode = document.getElementById('course-code');
                        var yearOfStudy = document.getElementById('year-of-study');
                    
                        if (userType.value == 'STUDENT') {
                            courseCode.style.visibility = 'visible';
                            yearOfStudy.style.visibility = 'visible';
                        }

                        else {
                            courseCode.style.visibility = 'hidden';
                            yearOfStudy.style.visibility = 'hidden';
                        }
                    }

                    addUser.onclick = function() {
                        var userType = document.getElementById('user_type');
                        var courseCode = document.getElementById('course_code_newUser');
                        var yearOfStudy = document.getElementById('year_of_study');
                        var firstName = document.getElementById('first_name');
                        var middleNames = document.getElementById('middle_names');
                        var surname = document.getElementById('surname');
                        var newPassword = document.getElementById('new_password');
                        
                        if (userType.value == '') {
                            bootstrapAlert('You have not selected the type of user you want to create.', 'ERROR');
                        }
                        
                        else if (firstName.value == '' || middleNames.value == '' || surname.value == '' || newPassword.value == '') {
                            bootstrapAlert('You have not given all of the compulsory user information.', 'ERROR');
                        }
                        
                        else if (userType.value == 'STUDENT' && (courseCode.value == '' || yearOfStudy.value == '')) {
                            bootstrapAlert('You have not given all of the necessary student information.', 'ERROR');
                        }
                        
                        else {
                            var callback = function() {
                                document.getElementById('new_user_form').submit();
                            };
                            
                            bootstrapConfirm('Are you sure you want to add this user?', callback, { title: 'Confirm User Registration?', confirmLabel: 'Yes', cancelLabel: 'No' });
                        }
                    };
                    
                    addCourse.onclick = function() {
                        var courseCode = document.getElementById('course_code_newCourse');
                        var courseTitle = document.getElementById('course_title');
                        var courseLength = document.getElementById('course_length');
                        
                        if (courseCode.value == '' || courseTitle.value == '' || courseLength.value == '') {
                            bootstrapAlert('You have not given all of the necessary course information.', 'ERROR');
                        }
                        
                        else {
                            var callback = function() {
                                document.getElementById('new_course_form').submit();
                            }
                            
                            bootstrapConfirm('Are you sure you want to create this new course?', callback, { title: 'Confirm Course Creation?', confirmLabel: 'Yes', cancelLabel: 'No' });
                        }
                    };
                </script>");
            }
            
            else if (get_class($_SESSION['user']) == "Lecturer") {
                echo("<div id='notifications'>
                    <h3>NOTIFICATIONS</h3>
                </div>");
                
                echo("                <div id='tools'>
                    <h3>TOOLS</h3>
                    <div class='input-group'>
						<span class='input-group-btn'>
							<button style='border-radius: 4px;' class='btn btn-default' type='button' name='' id='' data-toggle='modal' data-target='#addModuleModal'>Create New Module</button>
						</span>
					</div><br/>
                </div>");
                
                $newModuleModalBody = "<form action='" . basename($_SERVER['PHP_SELF']) . "' method='POST' id='new_module_form'>
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='module-code-addon'>Module Code</span>
                    <input class='form-control' type='text' id='module_code_newModule' name='module_code_newModule' aria-describedby='module-code-addon' />
                </div><br/>
                
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='module-name-addon'>Module Name</span>
                    <input class='form-control' type='text' id='module_name_newModule' name='module_name_newModule' aria-describedby='module-name-addon' />
                </div><br/>
                
                <div class='input-group col-xs-12 col-sm-9 col-md-9 col-lg-9'>
                    <span class='input-group-addon' id='module-description-addon'>Module Description</span>
                    <textarea class='form-control' id='module_description_newModule' name='module_description_newModule' aria-describedby='module-description-addon' cols='20' rows='10'></textarea>
                </div><br/>
                
                <div class='input-group'>
                    <span class='input-group-btn'>
                        <button style='border-radius: 4px;' class='btn btn-default' type='button' name='create_module' id='create_module'>Create Module</button>
                    </span>
                </div>
            </form>";
                
                echo(Tools::CreateModal("addModuleModal", "Create New Module", $newModuleModalBody));
                
                echo("<script>
                    window.onload = function() {
                    }
                    
                    var modulesAdded = document.getElementById('module_courseList_newModule');
                    modulesAdded.readOnly = true;
                    
                    var addModule = document.getElementById('create_module');
                    
                    addModule.onclick = function() {
                        var moduleCode = document.getElementById('module_code_newModule');
                        var moduleName = document.getElementById('module_name_newModule');
                        var moduleDesc = document.getElementById('module_description_newModule');
                        
                        if (moduleCode.value == '' || moduleName.value == '' || moduleDesc.value == '') {
                            bootstrapAlert('You have not given all of the necessary module information.', 'ERROR');
                        }
                        
                        else {
                            var callback = function() {
                                document.getElementById('new_module_form').submit();
                            }
                            
                            bootstrapConfirm('Are you sure you want to add this new module?', callback, { title: 'Confirm Module Creation?', confirmLabel: 'Yes', cancelLabel: 'No' });
                        }
                    }
                </script>");
                
                // Finish creating text box for entering course codes for new module modal
            }
            ?>
		</div>
	</body>
</html>

