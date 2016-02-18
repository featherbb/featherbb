<?php
namespace FeatherBB\Core\Interfaces;

class Input extends \Statical\BaseProxy
{
	public static function file($name)
	{
		return isset($_FILES[$name]) && $_FILES[$name]['size'] ? $_FILES[$name] : null;
	}
}
