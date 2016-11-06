<?php
class User implements JsonSerializable {
	protected $username;
	protected $name; // associatative array with name types (first name, midle names and surname) as keys
	// Middle names is a numerically indexed array with each part of the middle name split into separate values
	
	public function __construct($inUser, $inName) {
		$this->username = $inUser;
		$this->name = $inName;
	}
	
	public function GetUsername() {
		return $this->username;
	}
	
	public function GetFirstName() {
		return $this->name['firstname'];
	}
	
	public function GetMiddleNames() {
		// joins the elements of the array into a string and adds a " " in between each element
		return join(" ", $this->name['middlenames']);
	}
	
	public function GetSurname() {
		return $this->name['surname'];
	}
	
	// $userType is the key for the returned JSON object
	public function jsonSerialize() {
		return [
			"type" => get_class($this),
			"username" => $this->GetUsername(),
			"firstname" => $this->GetFirstName(),
			"middlenames" => $this->GetMiddleNames(),
			"surname" => $this->GetSurname()
		];
	}
	
	public function UpdateFirstName($newName) {
		$this->name['firstname'] = $newName;
		
		$query = "UPDATE users SET firstname = ? WHERE username = ?;";
		$parameters = array($newName, $this->GetUsername());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdateMiddleNames($newNames) {
        $this->name['middlenames'] = explode(" ", $newNames);
		
		$query = "UPDATE users SET middlenames = ? WHERE username = ?;";
		$parameters = array($newNames, $this->GetUsername());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdateSurname($newName) {
		$this->name['surname'] = $newName;
		
		$query = "UPDATE users SET surname = ? WHERE username = ?;";
		$parameters = array($newName, $this->GetUsername());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdatePassword($oldPass, $newPass) {
		$passwordCorrect = Tools::CheckPassword($this->GetUsername(), $oldPass);
		
		// if the password isn't correct or a new password has not been given, return false;
		if (!$passwordCorrect || $newPass == "") {
			return false;
		}
		
		$newHash = Tools::CreatePasswordHash($newPass);
		
		$query = "UPDATE users SET hash = ? WHERE username = ?;";
		$parameters = array($newHash, $this->GetUsername());
		
		Database::ExecuteQuery($query, $parameters);
		
		return true;
	}
}

class Student extends User implements JsonSerializable {
	private $course; // Course object representing the course the student is registered for
	private $yearOfStudy;
	
	public function __construct($inUser, $inName, $inCourseCode, $inYear) {
		parent::__construct($inUser, $inName);
		$this->yearOfStudy = $inYear;
        
        $selectCourse = "SELECT * FROM courses WHERE code = ?;";
        $parameters = array($inCourseCode);
        
        $result = Database::ExecuteQuery($selectCourse, $parameters)[0];
        /*
        if (count($result < 1)) {
            throw new Exception("ERROR! The course <b>" . $inCourseCode . "</b> doesn't exist!");
        }
        */
        
        $this->course = new Course($result['code'], $result['title'], $result['years']);
	}
	
	public function GetCourse() {
		return $this->course;
	}
	
	public function GetCurrentYear() {
		return $this->yearOfStudy;
	}
	
	public function jsonSerialize() {
		$jsonObj = parent::jsonSerialize();
		$jsonObj["course"] = $this->GetCourse()->jsonSerialize();
		$jsonObj["year_of_study"] = $this->GetCurrentYear();
		
		return $jsonObj;
	}
	
	public function ChangeCourse($newCourse) {
		$this->course = $newCourse;
		
		$updateCourseQuery = "UPDATE users SET course_code = ? WHERE username = ?;";
		$updateCourseParameters = array($newCourse->GetCode(), $this->GetUsername());
		Database::ExecuteQuery($updateCourseQuery, $updateCourseParameters);
		
		$updateTaskResultsQuery = "DELETE FROM task_results WHERE task_results.username = ? AND task_results.task_code NOT IN (SELECT tasks.code FROM tasks WHERE topic_tasks.task_code = tasks.code AND topic_tasks.topic_code IN (SELECT topics.code FROM topics WHERE module_topics.topic_code = topics.code AND module_topics.module_code IN (SELECT modules.code FROM modules WHERE course_modules.module_code = modules.code AND `course_modules`.`course_code` = ?)));";
		$updateTaskResultsParameters = array($this->GetUsername(), $newCourse->GetCode());
		Database::ExecuteQuery($updateTestResultsQuery, $updateTestResultsParameters);
		// Update SQL database with new course information for student, and remove test scores from modules not in common with new course
	}
	
	public function UpdateCurrentYear($newYear) {
		$this->yearOfStudy = $newYear;
		
		$query = "UPDATE users SET year_of_study = ? WHERE username = ?;";
		$parameters = array($newYear, $this->GetUsername());
		
		Database::ExecuteQuery($query, $parameters);
	}
}

// For a lecturer, $course represents a course
class Lecturer extends User implements JsonSerializable {
	private $modules; // Lists of modules that are taught by a lecturer
	
	public function __construct($inUser, $inName) {
		parent::__construct($inUser, $inName);
        
        $this->modules = array();
        
        $selectModules = "SELECT code, name, description, year_taught, available FROM modules, lecturer_modules WHERE lecturer_modules.lecturer_username = ? AND modules.code = lecturer_modules.module_code;";
        $parameters = array($this->GetUsername());
        
        $results = Database::ExecuteQuery($selectModules, $parameters);
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                $module = new Module($result['code'], $result['name'], $result['description'], $result['year_taught'], $result['available']);
                array_push($this->modules, $module);
            }
        }
	}
	
	public function SetModuleAvailability($moduleCode, $isAvailable) {
		$query = "UPDATE modules SET available = ? WHERE code = ?;";
		$parameters = array($isAvailable, $moduleCode);
		
		Database::ExecuteQuery($query, $parameters);
	}
    
    public function SetTaskAvailability($taskCode, $isAvailable) {
        $query = "UPDATE tasks SET available = ? WHERE code = ?;";
		$parameters = array($isAvailable, $taskCode);
		
		Database::ExecuteQuery($query, $parameters);
    }
	
	public function MarkCoursework($student, $courseworkFilename) {
		// TO DO!
		// Should create a mark for a given student and call the Submit() function to transfer information to database
	}
	
	public function GetModules() {
		return $this->modules;
	}
	
	public function jsonSerialize() {
		$jsonObj = parent::jsonSerialize();
		
		$jsonObj["modules"] = array();
		
		foreach ($this->GetModules() as $module) {
			array_push($jsonObj["modules"], $module->jsonSerialize());
		}
		
		return $jsonObj;
	}
	
	public function AddModule($module) {
		// Register lecturer to teach new module
		array_push($this->modules, $module);
		
		$query = "INSERT INTO lecturer_modules VALUES (?, ?) WHERE NOT EXISTS (SELECT * FROM lecturer_modules WHERE lecturer_username = ? AND module_code = ?;";
		$parameters = array($this->GetUsername(), $module->GetCode(), $this->GetUsername(), $module->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function RemoveModule($module) {
		$this->modules = array_diff($this->modules, array($module));
		
		$query = "DELETE FROM lecturer_modules WHERE lecturer_username = ? AND module_code = ?;";
		$parameters = array($this->GetUsername(), $module->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
}

class Administrator extends User implements JsonSerializable {
	public function __construct($inUser, $inName) {
		parent::__construct($inUser, $inName);
	}
	
	public function jsonSerialize() {
		return parent::jsonSerialize();
	}
	
	public function AddUser($newUser, $password) {
		switch (get_class($newUser)) {
            case "Student":
                $query = "INSERT INTO users VALUES (?, ?, ?, ?, ?, ?, ?);";
                $parameters = array($newUser->GetUsername(), $newUser->GetFirstName(), $newUser->GetMiddleNames(), $newUser->GetSurname(), $newUser->GetCourse()->GetCode(), $newUser->GetCurrentYear(), Tools::CreatePasswordHash($password));
                Database::ExecuteQuery($query, $parameters);
                break;
                
            case "Lecturer":
                $query = "INSERT INTO users (username, firstname, middlenames, surname, hash) VALUES (?, ?, ?, ?, ?);";
                $parameters = array($newUser->GetUsername(), $newUser->GetFirstName(), $newUser->GetMiddleNames(), $newUser->GetSurname(), Tools::CreatePasswordHash($password));
                Database::ExecuteQuery($query, $parameters);
                break;
        }
	}
	
	public function RemoveUser($user) {
		// Remove user from database - temporary object instantiation required from where this is called
	}
}

class Question implements JsonSerializable {
	protected $questionCode;
	protected $title;
	protected $answer;
	protected $marks;
	
	protected function __construct($inCode, $inName, $inAnswer, $inMarks) {
		$this->questionCode = $inCode;
		$this->title = $inName;
		$this->answer = $inAnswer;
		$this->marks = $inMarks;
	}
	
	public function GetCode() {
		return $this->questionCode;
	}
	
	public function GetTitle() {
		return $this->title;
	}
	
	public function GetAnswer() {
		return $this->answer;
	}
	
	public function GetMarks() {
		return $this->marks;
	}
	
	public function jsonSerialize() {
		return [
			"type" => get_class($this),
			"code" => $this->GetCode(),
			"title" => $this->GetTitle(),
			"answer" => $this->GetAnswer(),
			"marks" => $this->GetMarks()
		];
	}
	
	public function UpdateTitle($newTitle) {
		$this->title = $newTitle;
		
		$query = "UPDATE questions SET title = ? WHERE code = ?;";
		$parameters = array($newTitle, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdateAnswer($newAnswer) {
		$this->answer = $newAnswer;
		
		$query = "UPDATE questions SET answer = ? WHERE code = ?;";
		$parameters = array($newAnswer, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdateMarks($newMarks) {
		$this->marks = $newMarks;
		
		$query = "UPDATE questions SET marks = ? WHERE code = ?;";
		$parameters = array($newMarks, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
}

class OpenEnded extends Question implements JsonSerializable {
	public function __construct($inCode, $inName, $inAnswer, $inMarks) {
		parent::__construct($inCode, $inName, $inAnswer, $inMarks);
	}
	
	public function jsonSerialize() {
		return parent::jsonSerialize();
	}
}

class Option implements JsonSerializable {
	private $code;
	private $name;
	
	public function __construct($inCode, $inName) {
		$this->code = $inCode;
		$this->name = $inName;
	}
	
	public function GetCode() {
		return $this->code;
	}
	
	public function GetName() {
		return $this->name;
	}
	
	public function jsonSerialize() {
		return [
			"code" => $this->GetCode(),
			"name" => $this->GetName()
		];
	}
	
	public function UpdateName($newName) {
		$this->name = $newName;
		
		$query = "UPDATE options SET name = ? WHERE code = ?;";
		$parameters = array($newName, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
}

class MultipleChoice extends Question implements JsonSerializable {
	private $options; // list of instances of option class
	
	public function __construct($inCode, $inName, $inAnswer, $inMarks) {
		parent::__construct($inCode, $inName, $inAnswer, $inMarks);
        $this->options = array();
		
        $selectOptions = "SELECT * FROM options WHERE question_options.question_code = ? AND options.code = question_options.option_code;";
        
        $parameters = array($this->GetCode());
        
        $results = Database::ExecuteQuery($selectOptions, $parameters);
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                $option = new Option($result['code'], $result['name']);
                array_push($this->options, $option);
            }
        }
	}
	
	public function GetOptions() {
		return $this->options;
	}
	
	public function jsonSerialize() {
		$jsonObj = parent::jsonSerialize();
		
		$jsonObj["options"] = array();
		
		foreach ($this->GetOptions() as $option) {
			array_push($jsonObj["options"], $option->jsonSerialize());
		}
		
		return $jsonObj;
	}
	
	public function AddOption($option) {
		array_push($this->options, $option);
		
		// Update database with new option if no such option exists...
		$query = "INSERT INTO options VALUES (?, ?) WHERE NOT EXISTS (SELECT * FROM options WHERE code = ?);";
		$parameters = array($option->GetCode(), $option->GetName(), $option->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// ...and / or the question it relates to if it isn't already a part of the multiple choice question
		$query2 = "INSERT INTO question_options VALUES (?, ?) WHERE NOT EXISTS (SELECT * FROM question_options WHERE question_code = ? AND option_code = ?);";
		$parameters2 = array($this->GetCode(), $option->GetCode(), $this->GetCode(), $option->GetCode());
		
		Database::ExecuteQuery($query2, $parameters2);
	}
	
	public function RemoveOption($option) {
		$this->options = array_diff($this->options, array($option));
		
		// Update database by deleting the option's related question...
		$query = "DELETE FROM question_options WHERE question_code = ? AND option_code = ?;";
		$parameters = array($this->GetCode(), $option->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// ...and the option itself from the 'options' table
		$query2	= "DELETE FROM options WHERE code = ?;";
		$parameter2 = array($option->GetCode());
		
		Database::ExecuteQuery($query2, $parameter2);
	}
}

class Task implements JsonSerializable {
	protected $taskCode;
    protected $available;
	protected $passMark;
	protected $maxMark;
	protected $dueDate;
	
	protected function __construct($inCode, $inDate, $availability) {
		$this->taskCode = $inCode;
        $this->available = $availability;
		$this->maxMark = 0;
		$this->dueDate = $inDate;
	}
	
	public function GetCode() {
		$this->taskCode;
	}
	
	public function GetPassMark() {
		return $this->passMark;
	}
	
	public function GetMaxMark() {
		return $this->maxMark;
	}
	
	public function GetDueDate() {
		return $this->dueDate;
	}
    
    public function IsAvailable() {
        return $this->available;
    }
	
	public function jsonSerialize() {
		return [
			"type" => get_class($this),
			"code" => $this->GetCode(),
			"pass_mark" => $this->GetPassMark(),
			"max_mark" => $this->GetMaxMark(),
			"due_date" => $this->GetDueDate()
		];
	}
	
	public function UpdateDueDate($newDate) {
		$currentDate = getDate(date("d/m/y"));
		$temp = explode("_", $newDate);
		$newDate = date("d/m/y", mktime(0, 0, 0, $tempArr[1], $tempArr[0], $tempArr[2])); // [1], [0], [2] for day month year format
		
		// Is the date entered by a lecturer BEFORE the current date?
		if (strtotime($newDate) < strtotime($currentDate)) {
			throw new Exception("WARNING! The date you entered is a past date!");
		}
		
		// Updates object and database info
		else {
			$this->dueDate = $currentDate;
			// Update SQL database with new date info
		}
	}
	
	// $student is an instance of the Student class
	// comments is an optional argument, mainly if a lecturer is marking coursework and is returning feedback to a student
	public function Submit($student, $marksReceived, $comments="") {
		// Adds test/coursework results to database
	}
	
	protected function CalculatePassMark() {
		$this->passMark = ceil($this->maxMark * 0.4);
	}
}

class Test extends Task implements JsonSerializable {
	private $questions;
	
	public function __construct($inCode, $inDate, $availability) {
		parent::__construct($inCode, $inDate, $availability);
        
        $this->questions = array();
        
        $selectQuestions = "SELECT * FROM questions WHERE task_questions.task_code = ? AND questions.code = task_questions.question_code;";
        
        $parameters = array($this->GetCode());
        
        $results = Database::ExecuteQuery($selectQuestions, $parameters);
        
        if (count($results) > 0) {
            foreach ($results as $result) {            
                if ($result['type'] == "open") {
                    $question = new OpenEnded($result['code'], $result['title'], $result['answer'], $result['marks']);
                    array_push($this->questions, $question);
                }

                else if ($result['type'] == "choice") {
                    $question = new MultipleChoice($result['code'], $result['title'], $result['answer'], $result['marks']);
                    array_push($this->questions, $question);
                }

                $this->maxMark += $question->GetMarks();
            }
        }
		
		$this->CalculatePassMark();
	}
	
	public function GetQuestions() {
		return $this->questions;
	}
	
	public function jsonSerialize() {
		$jsonObj = parent::jsonSerialize();
		
		$jsonObj["questions"] = array();
		
		foreach ($this->GetQuestions() as $question) {
			array_push($jsonObj["questions"], $question->jsonSerialize());
		}
		
		return $jsonObj;
	}
	
	// $type being either "choice" for multiple choice, or "open" for open-ended questions
	public function AddQuestion($newQuestion, $type) {
		array_push($this->questions, $newQuestion);
		
		$this->maxMark += $newQuestion->GetMarks();
		$this->CalculatePassMark();
		
		// Inserts new question into database if it doesn't exist
		$query = "INSERT INTO questions VALUES (?, ?, ?, ?, ?) WHERE NOT EXISTS (SELECT * FROM questions WHERE code = ?);";
		$parameters = array($newQuestion->GetCode(), $newQuestion->GetTitle(), $type, $newQuestion->GetAnswer(), $newQuestion->GetMarks());
		
		Database::ExecuteQuery($query, $parameters);
		
		// Updates the database with the test the new question relates to
		$query2 = "INSERT INTO task_questions VALUES (?, ?) WHERE NOT EXISTS (SELECT * FROM task_questions WHERE task_code = ? AND question_code = ?);";
		$parameters2 = array($this->GetCode(), $newQuestion->GetCode(), $this->GetCode(), $newQuestion->GetCode());
		
		Database::ExecuteQuery($query2, $parameters2);
	}
	
	public function RemoveQuestion($question) {
		$this->questions = array_diff($this->questions, $question);
		
		$this->maxMark -= $question->GetMarks();
		$this->CalculatePassMark();
		
		// Removes the question from the related task...
		$query = "DELETE FROM task_questions WHERE task_code = ? AND question_code = ?";
		$parameters = array($this->GetCode(), $question->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// ...and then the entire question altogether
		$query2 = "DELETE FROM questions WHERE code = ?;";
		$parameter2 = array($question->GetCode());
		
		Database::ExecuteQuery($query2, $parameter2);
	}
}

class Coursework extends Task implements JsonSerializable {
	public function __construct($inCode, $inDate, $inMaxMark, $availability) {
		parent::__construct($inCode, $inDate, $availability);
		$this->maxMark = $inMaxMark;
		$this->CalculatePassMark();
	}
	
	public function jsonSerialize() {
		return parent::jsonSerialize();
	}
	
	public function UpdateMaxMark($newMaxMark) {
		$this->maxMark = $newMaxMark;
		$this->CalculatePassMark();
		
		$query = "UPDATE tasks SET max_mark = ? WHERE code = ?;";
		$parameters = array($newMaxMark, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		// Update maxMark object info and database
		// Recalculate pass mark
	}
}

class Topic implements JsonSerializable {
	private $topicCode;
	private $name;
	private $description;
	private $lessonFilename;
	private $tasks; // instances of the Test or Coursework classes
	
	public function __construct($inTopicCode, $inName, $inDescription) {
		$this->topicCode = $inTopicCode;
		$this->name = $inName;
		$this->description= $inDescription;
        
        $tasks = array();
        
        $selectTasks = "SELECT * FROM tasks WHERE topic_tasks.topic_code = ? AND tasks.code = topic_tasks.task_code;";
        $parameters = array($this->GetCode());
        
        $results = Database::ExecuteQuery($selectTasks, $parameters);
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                if ($result['max_mark'] == null) {
                    $task = new Test($result['code'], $result['due_date'], $result['available']);
                    array_push($this->tasks, $task);
                }

                else {
                    $task = new Coursework($result['code'], $result['due_date'], $result['max_mark'], $result['available']);
                    array_push($this->tasks, $task);
                }
            }
        }
	}
	
	public function GetCode() {
		return $this->topicCode;
	}
	
	public function GetName() {
		return $this->name;
	}
	
	public function GetDescription() {
		return $this->description;
	}
	
	public function GetLessonFilename() {
		return $this->lessonFilename;
	}
	
	public function GetTasks() {
		return $this->tasks;
	}
	
	public function jsonSerialize() {
		$jsonObj = [
			"code" => $this->GetCode(),
			"name" => $this->GetName(),
			"description" => $this->GetDescription(),
			"lesson_filename" => $this->GetLessonFilename()
		];
		
		$jsonObj["tasks"] = array();
		
		foreach ($this->GetTasks() as $task) {
			array_push($jsonObj["tasks"], $task->jsonSerialize());
		}
		
		return $jsonObj;
	}
	
	public function UpdateName($newName) {
		$this->name = $newName;
		
		$query = "UPDATE topics SET name = ? WHERE code = ?;";
		$parameters = array($newName, $this->GetCode());
		
		Database::Execute($query, $parameters);
	}
	
	public function UpdateDescription($newDescription) {
		$this->description = $newDescription;
		
		$query = "UPDATE topics SET description = ? WHERE code = ?;";
		$parameters = array($newDescription, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function AddTask($newTask) {
		array_push($this->tasks, $newTask);
		
		$query = "";
		$parameters = "";
		
		// Second parameter for INSERT INTO specifies the maximum attainable mark for a task
		// For tests, this is calculated within PHP
		// For coursework, this is stored in the database
		switch(get_class($newTask)) {
			case "Test":
				$query = "INSERT INTO tasks VALUES (?, null, ?, 0) WHERE NOT EXISTS (SELECT * FROM tasks WHERE code = ?);";
				$parameters = array($newTask->GetCode(), $newTask->GetDueDate(), $newTask->GetCode());
				break;
				
			case "Coursework":
				$query = "INSERT INTO tasks VALUES (?, ?, ?, 0) WHERE NOT EXISTS (SELECT * FROM tasks WHERE code = ?);";
				$parameters = array($newTask->GetCode(), $newTask->GetMaxMark(), $newTask->GetDueDate(), $newTask->GetCode());
				break;
		}
		
		$query2 = "INSERT INTO topic_tasks VALUES(?, ?) WHERE NOT EXISTS (SELECT * FROM topic_tasks WHERE topic_code = ? AND task_code = ?);";
		$parameters2 = array($newTask->GetCode(), $this->GetCode(), $this->GetCode(), $newTask->GetCode());
		
		Database::Execute($query, $parameters);
		Database::ExecuteQuery($query2, $parameters2);
		// Add test id and extra info to database using mySQL
	}
	
	public function RemoveTask($task) {
		$this->tasks = array_diff($this->tasks, array($task));
		
		// Remove task's link to the topic
		$query = "DELETE FROM topic_tasks WHERE topic_code = ? AND task_code = ?;";
		$parameters = array($this->GetCode(), $task->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// Remove the topic altogether from the database
		$query2 = "DELETE FROM tasks WHERE code = ?;";
		$parameters2 = array($task->GetCode());
		
		Database::ExecuteQuery($query2, $parameters2);
	}
	
	public function AddLesson(/*Insert argument here*/) {
		// Upload lesson file to server
		// Update lesson filename in database with mySQL
	}
}

class Module implements JsonSerializable {
	private $moduleCode;
	private $name;
	private $description;
	private $yearTaught;
    private $available;
	private $topics; // list of instances of Topic class
	
	public function __construct($inModuleCode, $inName, $inDescription, $inYear, $availability) {
		$this->moduleCode = $inModuleCode;
		$this->name = $inName;
		$this->description = $inDescription;
		$this->yearTaught = $inYear;
        $this->available = $availability;
        
        $this->topics = array();
        
        $selectTopics = "SELECT * FROM topics WHERE module_topics.module_code = ? AND topics.code = module_topics.module_code;";
        $parameters = array($this->GetCode());
        
        $results = Database::ExecuteQuery($selectTopics, $parameters);
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                $topic = new Topic($result['code'], $result['name'], $result['description']);
                array_push($this->topics, $topic);
            }
        }
	}
	
	public function GetCode() {
		return $this->moduleCode;
	}
	
	public function GetName() {
		return $this->name;
	}
	
	public function GetDescription() {
		return $this->description;
	}
	
	public function GetYearTaught() {
		return $this->yearTaught;
	}
    
    public function IsAvailable() {
        return $this->available;
    }
	
	public function GetTopics() {
		return $this->topics;
	}
	
	public function jsonSerialize() {
		$jsonObj = [
			"code" => $this->GetCode(),
			"name" => $this->GetName(),
			"description" => $this->GetDescription(),
			"year_taught" => $this->GetYearTaught()
		];
		
		$jsonObj["topics"] = array();
		
		foreach ($this->GetTopics() as $topic) {
			array_push($jsonObj["topics"], $topic->jsonSerialize());
		}
		
		return $jsonObj;
	}
	
	// $topic being an instance of the Topic class
	public function AddTopic($topic) {
		array_push($this->topics, $topic);
		
		// INSERT the new topic into the database if it doesn't exist...
		$query = "INSERT INTO topics VALUES (?, ?, ?) WHERE NOT EXISTS (SELECT * FROM topics WHERE code = ?);";
		$parameters = array($topic->GetCode(), $topic->GetName(), $topic->GetDescription(), $topic->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// ...and INSERT the link for the topic to '$this' module
		$query2 = "INSERT INTO module_topics VALUES (?, ?) WHERE NOT EXISTS (SELECT * FROM module_topics WHERE module_code = ? AND topic_code = ?);";
		$parameters2 = array($this->GetCode(), $topic->GetCode(), $this->GetCode(), $topic->GetCode());
		
		Database::ExecuteQuery($query2, $parameters2);
	}
	
	public function RemoveTopic($topic) {
		$this->topics = array_diff($this->topics, array($topic));
		
		// Delete the link from $this module to the given topic...
		$query = "DELETE FROM module_topics WHERE module_code = ? AND topic_code = ?;";
		$parameters = array($this->GetCode(), $topic->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// ...and delete the topic from the database altogether
		$query2 = "DELETE FROM topics WHERE code = ?;";
		$parameters2 = array($topic->GetCode());
		
		Database::ExecuteQuery($query2, $parameters2);
	}
	
	public function UpdateName($newName) {
		$this->name = $newName;
		
		$query = "UPDATE modules SET name = ? WHERE code = ?;";
		$parameters = array($newName, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdateDescription($newDescription) {
		$this->description = $newDescription;
		
		$query = "UPDATE modules SET description = ? WHERE code = ?;";
		$parameters = array($newDescription, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
	
	public function UpdateYearTaught($newYear) {
		$this->yearTaught = $newYear;
		
		$query = "UPDATE modules SET year_taught = ? WHERE code = ?;";
		$parameters = array($newYear, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
}

class Course implements JsonSerializable {
	private $courseCode;
	private $title;
	private $years; // INT representing years of study until course completion
	private $modules; // Modules that are taught on the course
	
	public function __construct($inCode, $inTitle, $inYears) {
		$this->courseCode = $inCode;
		$this->title = $inTitle;
		$this->years = $inYears;
        
        $this->modules = array();
        
        $selectModules = "SELECT `code`, `name`, `description`, `year_taught` FROM `modules`, `course_modules` WHERE `modules`.`code` = `course_modules`.`module_code` AND `course_modules`.`course_code` = ?;";
        $parameters = array($this->GetCode());
        
        $results = Database::ExecuteQuery($selectModules, $parameters);
        
        if (count($results) > 0) {
            foreach ($results as $result) {
                $module = new Module($result['code'], $result['name'], $result['description'], $result['year_taught'], $result['available']);
                array_push($this->modules, $module);
            }
        }
	}
	
	public function GetCode() {
		return $this->courseCode;
	}
	
	public function GetTitle() {
		return $this->title;
	}
	
	public function GetYears() {
		return $this->years;
	}
	
	public function GetModules() {
		return $this->modules;
	}
	
	public function jsonSerialize() {
		$jsonObj = [
			"code" => $this->GetCode(),
			"title" => $this->GetTitle(),
			"years" => $this->GetYears()
		];
		
		$jsonObj["modules"] = array();
		
		foreach ($this->GetModules() as $module) {
			array_push($jsonObj["modules"], $module->jsonSerialize());
		}
		
		return $jsonObj;
	}
	
	public function AddModule($module) {
		array_push($this->modules, $module);
		
		// WHERE NOT EXISTS stops new module from being recorded if it's data is already in the table
		// last value in VALUES represents the availability of modules to students, set to 0 by default
		$query = "INSERT INTO modules VALUES(?, ?, ?, ?, 0) WHERE NOT EXISTS (SELECT code FROM modules WHERE code = ?);";
		$parameters = array($module->GetCode(), $module->GetName(), $module->GetDescription(), $module->GetYearTaught(), $module->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// Inserts new module into the `course_modules` table
		$query2 = "INSERT INTO course_modules VALUES(?, ?) WHERE NOT EXISTS(SELECT * FROM modules WHERE course_code = ? AND module_code = ?);";
		$parameters2 = array($this->GetCode(), $module->GetCode(), $this->GetCode(), $module->GetCode());
		
		Database::ExecuteQuery($query2, $parameters2);
	}
	
	public function RemoveModule($module, $removeLinkOnly) {
		$this->modules = array_diff($this->modules, array($module));
		
		// Removes the link the given module has to this course
		$query = "DELETE FROM course_modules WHERE course_code = ? AND module_code = ?;";
		$parameters = array($this->GetCode(), $module->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
		
		// If the user wants the entire module to be removed and not just it's link to this course
		if (!$removeLinkOnly) {
			$query2 = "DELETE FROM modules WHERE code = ?;";
			$parameters2 = array($module->GetCode());
			
			Database::ExecuteQuery($query2, $parameters2);
		}
		// Remove existing module from this course
		// Update SQL database - then remove module from list of Module instances
	}
	
	public function UpdateTitle($newTitle) {
		$this->title = $newTitle;
		
		$query = "UPDATE courses SET title = ? WHERE code = ?;";
		$parameters = array($newTitle, $this->GetCode());
		
		Database:ExecuteQuery($query, $parameters);
	}
	
	public function UpdateYears($newYears) {
		$this->years = $newYears;
		
		$query = "UPDATE courses SET years = ? WHERE code = ?;";
		$parameters = array($newYears, $this->GetCode());
		
		Database::ExecuteQuery($query, $parameters);
	}
}

class Tools {
	private static $cost = 15;
	private static $system = "$2y$%02d$";
	
	public static function CheckPassword($username, $password) {
		$query = "SELECT hash FROM users WHERE username = ?";
		$parameter = array($username);
		
		// if no results are found in the database
		if (count(Database::ExecuteQuery($query, $parameter)) < 1) {
			return false;
		}
		
		$correctHash = Database::ExecuteQuery($query, $parameter)[0]["hash"];
		
		// Returns boolean comparison between inputted and correct passwords
		// To check: hashing the password with the hash from the database AS THE SALT should return the same hash
		return hash_equals($correctHash, crypt($password, $correctHash));
	}
	
	public static function CreatePasswordHash($password) {
		// Create a random salt
		$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');

		// Prefix information about the hash so PHP knows how to verify it later.
		// "$2y$" Means we're using the Blowfish algorithm.
		// $cost refers to the number of repetitions made by the algorithm
		$salt = sprintf(self::$system, self::$cost) . $salt;

		// Hash the password with the salt
		$hash = crypt($password, $salt);
		
		return $hash;
	}
	
	// Used to create the name for a new instantiated User (Student / Lecturer / Administrator)
	public static function CreateName($inFirst, $inMiddle, $inLast) {
		$firstName = $inFirst;
		$middleNames = explode(" ", $inMiddle);
		$surname = $inLast;
		
		return array('firstname'=>$firstName, 'middlenames'=>$middleNames, 'surname'=>$surname);
	}
    
    public static function GetTotalStudents() {
        // Get the user added most recently
        $query = "SELECT username FROM users WHERE username LIKE ? ORDER BY username DESC LIMIT 1;";
        $parameters = array("std%");

        $latestStudent = Database::ExecuteQuery($query, $parameters)[0]['username'];
        
        // Get the value after "std" in their username
        $number = substr($latestStudent, 3, count(count_chars($latestStudent)));
        
        return $number;
    }

    public static function GetTotalLecturers() {
        $query = "SELECT username FROM users WHERE username LIKE ? ORDER BY username DESC LIMIT 1;";
        $parameters = array("lct%");
        
        $results = Database::ExecuteQuery($query, $parameters);
        
        if (count($results) > 0) {
            $latestLecturer = $results[0]['username'];
            $number = substr($latestLecturer, 3, count(count_chars($latestLecturer)));
        }

        else {
            $number = 0;
        }
        
        return $number;
    }
	
	public static function CreateModal($modalID, $header="", $body="") {
		$modal = "<!-- Modal -->
<div id='$modalID' class='modal fade' role='dialog'>
	<div class='modal-dialog modal-lg'>
		<!-- Modal content-->
		<div class='modal-content'>
			<div class='modal-header'>
				<button type='button' class='close' data-dismiss='modal'>&times;</button>
				<h4>$header</h4>
			</div>
			<div class='modal-body' style='height: calc(100vh - 210px); overflow-y: auto;'>
				$body
			</div>
			<div class='modal-footer'>
				<button type='button' class='btn btn-default' data-dismiss='modal'>Close</button>
			</div>
		</div>
	</div>
</div>";
        
        return $modal;
	}
	
	public static function CreateNavBar($userObj) {		
		$greeting = $userObj->GetFirstName();
		
		$navbar = "<nav class='navbar navbar-default navbar-fixed-top'>
	<div class='container'>
		<div class='navbar-header'>
			<button type='button' class='navbar-toggle collapsed' data-toggle='collapse' data-target='#navbar' aria-expanded='false' aria-controls='navbar'>
				<span class='sr-only'>Toggle navigation</span>
				<span class='icon-bar'></span>
				<span class='icon-bar'></span>
				<span class='icon-bar'></span>
			</button>
			<span class='navbar-brand'>Hello, $greeting</span>
		</div>
		<div id='navbar' class='navbar-collapse collapse'>
			<ul class='nav navbar-nav navbar-right'>
				<li></li>";
		
		switch(get_class($userObj)) {
			case "Student":
                $items = array("<a href='index.php'>HOME</a>", "<a href='myModules.php'>MY MODULES</a>", "<a href='myAccount.php'>MY ACCOUNT</a>");
				
				for ($i = 0; $i < count($items); $i++) {
					$navbar .= "<li>$items[$i]</li>";
				}
                
				break;
				
			case "Lecturer":
                $items = array("<a href='index.php'>DASHBOARD</a>", "<a href='myModules.php'>MY MODULES</a>", "<a href='search.php'>SEARCH</a>", "<a href='myAccount.php'>MY ACCOUNT</a>");
				
				for ($i = 0; $i < count($items); $i++) {
					$navbar .= "<li>$items[$i]</li>";
				}
                
				break;
				
			case "Administrator":
				$items = array("<a href='index.php'>DASHBOARD</a>", "<a href='search.php'>SEARCH</a>", "<a href='myAccount.php'>MY ACCOUNT</a>");
				
				for ($i = 0; $i < count($items); $i++) {
					$navbar .= "<li>$items[$i]</li>";
				}
				
				break;
				
			default:
				break;
		}

		$navbar .= "<li><a href='logout.php'>LOGOUT</a></li>
			</ul>
		</div><!--/.nav-collapse -->
	</div>
</nav>
<br/>
<br/>
<br/>";
		
		return $navbar;
	}
}

class Database {
	// Change database details as necessary
	private static $host = "127.0.0.1";
	private static $username = "root";
	private static $password = "alicebluetiger220272";
	private static $schema = "cm1202"; // i.e. the database to be used

	public static function ExecuteQuery($query, $parameters) {
		
		// array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
		// Above argument sets the drivers and tools to connect to mySQL using PDO
		
		try {
			$db = new PDO("mysql:host=".self::$host.";dbname=".self::$schema.";charset=utf8mb4", self::$username, self::$password, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
			$stmt = $db->prepare($query);
			
			// Replaces appropriate '?' in query with each parameter in order from left -> right
			for ($i = 0; $i < count($parameters); $i++) {
				$stmt->bindValue($i + 1, $parameters[$i], PDO::PARAM_STR);
			}
			
			$stmt->execute();
			
			// Returns fetched data if SELECT query is used
			if (explode(" ", $query)[0] == "SELECT") {
				$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$db = null;
				return $results;
			}
			
			$db = null;
		}
		
		catch (PDOException $exception) {
			echo("Whoops! Something went wrong! <br/>");
			self::ShowError($exception);
            echo("QUERY: " . $query . "<br/> PARAMETERS: " . var_dump($parameters));
		}
	}

	private static function ShowError($exception) {
		echo($exception->getMessage());
	}
}
?>