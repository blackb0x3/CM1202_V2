var AJAX = function(method, file, async, callback, parameters="") {
	var request = new XMLHttpRequest();
	
	request.onreadystatechange = function() {
		if (request.readyState == 4 && request.status == 200) {
			callback.apply(request);
		}
		
		else if (request.readyState == 4 && request.status == 404) {
			bootstrapAlert("Whoops! Something went wrong! (Error code 404)", "ERROR");
		}
	};
	
	request.open(method, file, async);
	
	if (method == "POST") {
		request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		request.send(parameters);
	}
	
	else {
		request.send();
	}
};

var bootstrapAlert = function(inMessage, messageType="INFO") {
	var title = "";

	switch(messageType) {
        case "INFO":
			title = "Notification";
			break;
		case "ERROR":
			title = "Error";
			break;
		case "WARNING":
			title = "Warning";
			break;
		case "SUCCESS":
			title = "Success";
			break;
	}

	bootbox.dialog({
		message: inMessage,
		title: title,
		buttons: {
			main: {
				label: "OK",
				className: "btn-primary"
			}
		}
	});
};

var bootstrapConfirm = function(inMessage, callback, optArgs={ title: null, confirmLabel: null, cancelLabel: null }) {
    var confirmLabel = "";
    var cancelLabel = "";
    var title = "";
    
    (optArgs.title != null) ? title += optArgs.title : title += "Are you sure?";
    (optArgs.confirmLabel != null) ? confirmLabel += optArgs.confirmLabel : confirmLabel += "Confirm";
    (optArgs.cancelLabel != null) ? cancelLabel += optArgs.cancelLabel : cancelLabel += "Cancel";
    
	bootbox.dialog({
		message: inMessage,
		title: title,
		buttons: {
			main: {
				label: confirmLabel,
				className: "btn-primary",
				callback: function() {
					callback();
				}
			},
			
			cancel: {
				label: cancelLabel,
				className: "btn_secondary"
			}
		}
	});
};

// Modifies the "Search By" dropdown to allow appropriate search conditions depending on what they want to "Search For"
// e.g. By selecting Modules, the options in "Search By" will change to code, name and description
//		By selecting Lecturers, the options in "Search By" will change to username, first name, middle names and surname
function modSearchBy() {
	var searchFor = document.getElementById("search_for");
	var searchBy = document.getElementById("search_by");
	var searchText = document.getElementById("search_text");

	switch (searchFor.value) {
		case "STUDENT":
			searchBy.innerHTML = "<option value=''>Select an Option</option>" +
"<option value='USERNAME'>Username</option>" +
"<option value='FIRST NAME'>First Name</option>" +
"<option value='MIDDLE NAME'>Middle Names</option>" +
"<option value='SURNAME'>Surname</option>" +
"<option value='COURSE CODE'>Course Code</option>" +
"<option value='YEAR OF STUDY'>Year of Study</option>";
			break;

		case "LECTURER":
			searchBy.innerHTML = "<option value=''>Select an Option</option>" +
"<option value='USERNAME'>Username</option>" +
"<option value='FIRST NAME'>First Name</option>" +
"<option value='MIDDLE NAME'>Middle Names</option>" +
"<option value='SURNAME'>Surname</option>";
			break;

		case "COURSE":
			searchBy.innerHTML = "<option value=''>Select an Option</option>" +
"<option value='CODE'>Course Code</option>" +
"<option value='TITLE'>Course Title</option>" +
"<option value='LENGTH'>Course Length (Years)</option>";
			break;

		case "MODULE":
			searchBy.innerHTML = "<option value=''>Select an Option</option>" +
"<option value='CODE'>Module Code</option>" +
"<option value='TITLE'>Module Name</option>" +
"<option value='DESCRIPTION'>Module Description</option>"
"<option value='YEAR TAUGHT'>Year Taught</option>";
			break;

		case "TOPIC":
			searchBy.innerHTML = "<option value=''>Select an Option</option>" +
"<option value='CODE'>Topic Code</option>" +
"<option value='TITLE'>Topic Name</option>" +
"<option value='DESCRIPTION'>Topic Description</option>";
			break;

		default:
			searchBy.innerHTML = "<option value=''>Select an Option</option>";
	}
}