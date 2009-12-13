<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Sprig_Tests extends Controller {

	public function action_index()
	{
		$this->request->response = View::factory('profiler/stats');

		$student = Sprig::factory('student')->load();

		echo Kohana::debug('Student', $student->as_array());
		echo Kohana::debug('Car', $student->car->load()->as_array());
		echo Kohana::debug('Classes', $student->classes->as_array('id', 'name'));
		echo Kohana::debug('Memberships', count($student->memberships));
		echo Kohana::debug('Clubs', $student->clubs->as_array('id', 'name'));

	}

} // End Sprig_Test
