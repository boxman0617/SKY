<?php
class AppRoute extends Route
{
	/**
	 * Add routes below.
	 */
	public function AppRoutes()
	{
		$this->Home('Home#Index');

		$this->NotFound('ErrorPage#NotFound');

		// $this->Match('/test', 'Home#Test', 'GET');

		// $this->Resource('Test');

		// $this->Scope('/testing', array(
		// 	array('/new/:id', 'Home#GetNew'),
		// 	array('/delete/:id', 'Home#Delete')
		// ));
	}
}
?>