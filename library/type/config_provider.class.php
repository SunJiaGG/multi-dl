<?php
namespace type;
abstract class config_provider {

	public $PLUGIN_NAME='default';
	
    abstract public function get_config($params);
};
