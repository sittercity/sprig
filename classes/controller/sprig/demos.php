<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Sprig_Demos extends Controller_Template {

	public $template = 'sprig/template';

	public function action_index()
	{
		$this->template->content = View::factory('sprig/demos/index')
			->bind('students', $students);

		$students = Sprig::factory('student')->load(NULL, FALSE);
	}

	public function action_student($id = NULL)
	{
		$this->template->content = View::factory('sprig/demos/student')
			->bind('student', $student);

		$student = Sprig::factory('student', array('id' => $id))
			->load()
			->values($_POST);

		$student->classes = array(1, 3);

		

		echo Kohana::debug($student->classes);exit;

		if ($_POST)
		{
			try
			{
				$student->values($_POST)->save();

				$this->request->redirect($this->request->uri());
			}
			catch (Validate_Exception $e)
			{
				$errors = $e->array->errors('student/edit');
			}
		}
	}

	public function action_clubs()
	{
		
		
		echo Kohana::debug('Clubs');

		$clubs = Sprig::factory('club')->load(NULL, FALSE);

		foreach ($clubs as $club)
		{
			echo Kohana::debug($club->as_array());
		}
	}

} // End Sprig_Test
