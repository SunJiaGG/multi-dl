<?php
namespace type;
interface parser{
	function read($filename);
	function write($filename,$data);
}
