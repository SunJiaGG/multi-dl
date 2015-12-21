<?php
namespace type;
interface cache{
	function get($k);
	function set($k,$v,$e=0);
}
