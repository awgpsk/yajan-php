<?php
require_once("$LIB_PATH/php/ui/event.php");
require_once("$LIB_PATH/php/ui/object.php");
class Span extends UIObject
{
	var $events;
	function __construct($id)
	{
		parent::__construct();
		$this->id=$id;
		$this->type="";
		$this->tag="span";
		$this->events = array();
	}
}
?>