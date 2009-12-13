<?php defined('SYSPATH') or die('No direct script access.');

Route::set('sprig', 'sprig(/<action>(/<id>))')
	->defaults(array(
		'controller' => 'sprig_demos'
	));
