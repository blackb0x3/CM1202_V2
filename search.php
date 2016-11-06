<?php
include "Objects.php";
session_start();

if (!isset($_SESSION['user'])) {
	header("Location: login.php");
}

// If a student tries to access this page, deny access
else if (!(get_class($_SESSION['user']) == "Administrator" || get_class($_SESSION['user']) == "Lecturer")) {
    header("Location: index.php");
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
				<?php echo(Tools::CreateNavBar($_SESSION['user'])); ?>
			</div>
			<div class='col-lg-12' id='search_box'>
				<div class='col-lg-12' id='search_input'>
					<div class='col-lg-12' id='search_instructions'>
						<h3 style='text-align: center;'>ENTER SEARCH CRITERIA</h3>
					</div>
					<div class='col-lg-12' id='search_form'>
						<form>
							<div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6'>
								<span class='input-group-addon' id='search-for-addon'>Search For:</span>
								<select class='form-control' id='search_for' name='search_for' onchange='modSearchBy();' aria-describedby='search-for-addon' required>
									<option value=''>Select an Option</option>
<?php
if (get_class($_SESSION['user']) == "Administrator") {
    echo("									<option value='STUDENT'>Students</option>
									<option value='LECTURER'>Lecturers</option>\n");
}
?>
	
									<option value='COURSE'>Courses</option>
									<option value='MODULE'>Modules</option>
									<option value='TOPIC'>Topics</option>
								</select>
							</div><br/>
							<div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6'>
								<span class='input-group-addon' id='search-by-addon'>Search By:</span>
								<select class='form-control' id='search_by' name='search_by' aria-describedby='search-by-addon' required>
									<option value=''>Select an Option</option>
								</select>
							</div><br/>
							<div class='input-group col-xs-12 col-sm-6 col-md-6 col-lg-6'>
								<span class='input-group-addon' id='search-contains-addon'>Contains:</span>
								<input class='form-control' type='text' id='search_text' name='search_text' aria-describedby='search-contains-addon' required />
							</div><br/>
							<div class='input-group'>
								<span class='input-group-btn'>
									<button style='border-radius: 4px;' class='btn btn-default' type='button' name='search_button' id='search_button'>Go!</button>
								</span>
							</div><br/>
						</form>
					</div>
				</div>
				<div id='search_output'>
				</div>
			</div>
		</div>
        <script>
            var search = document.getElementById("search_button");

            var searchFor = document.getElementById("search_for");
            var searchBy = document.getElementById("search_by");
            var searchText = document.getElementById("search_text");

            search.onclick = function() {
                // get the output div from the page
                var searchOutput = document.getElementById("search_output");
                // clear output div by default
                searchOutput.innerHTML = "";

                // if any of the values are empty
                if (searchFor.value == "" || searchBy.value == "" || searchText.value == "") {
                    bootstrapAlert("None of the search criteria can be empty!", "ERROR");
                }

                else {
                    var parameters = "search_for=" + searchFor.value + "&search_by=" + searchBy.value + "&search_text=" + searchText.value;
                    var callback = function() {
                        var results = JSON.parse(this.responseText);

                        // no results!
                        if (results.length < 1) {
                            bootstrapAlert("No results were found!");
                        }

                        // creates table and table data, displays new table inside output div
                        else {
                            var table = document.createElement("table");
                            table.className = "table table-hover table-responsive";
                            table.innerHTML = "";

                            var tableHeaders;
                            
                            switch (searchFor.value) {
                                case "LECTURER":
                                    tableHeaders = ["Username", "First Name", "Middle Names", "Surname"];
                                    break;
                                    
                                case "STUDENT":
                                    tableHeaders = ["Username", "First Name", "Middle Names", "Surname", "Course Code", "Year of Study"];
                                    break;
                                    
                                case "COURSE":
                                    tableHeaders = ["Code", "Title", "Length"];
                                    break;
                                    
                                case "MODULE":
                                    tableHeaders = ["Code", "Name", "Description", "Year Taught", "Available to Students"];
                                    break;
                                    
                                case "TOPIC":
                                    tableHeaders = ["Code", "Name", "Description", "Lesson File Name"];
                                    break;
                            }

                            var tHead = document.createElement("thead");
                            var headerRow = table.insertRow(-1);

                            for (var index = 0; index < tableHeaders.length; index++) {
                                var headerCell = document.createElement("th");
                                headerCell.innerHTML = tableHeaders[index];
                                headerRow.appendChild(headerCell);
                            }

                            tHead.appendChild(headerRow);
                            table.appendChild(tHead);

                            var tBody = document.createElement("tbody");

                            for (var i = 0; i < results.length; i++) {
                                row = table.insertRow(-1);

                                for (var property in results[i]) {
                                    var cell = document.createElement("td");
                                    cell.innerHTML = results[i][property];
                                    row.appendChild(cell);
                                }

                                tBody.appendChild(row);
                            }

                            table.appendChild(tBody);
                            table.removeChild(table.childNodes[0]);

                            searchOutput.appendChild(table);
                        }
                    };

                    AJAX("POST", "PHP_Func/returnAccounts.php", true, callback, parameters);
                }
            };
        </script>
	</body>
</html>