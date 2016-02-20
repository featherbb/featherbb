<?php
namespace FeatherBB\Core\Interfaces;

class Input extends \Statical\BaseProxy
{
	public static function file($name)
	{
		return isset($_FILES[$name]) && $_FILES[$name]['size'] ? $_FILES[$name] : null;
	}
	public static function getParam($key, $default = null, $sanitize = true)
	{
		$result = Request::getParam($key, $default);
		if ($sanitize) {
			$result = htmlspecialchars($result);
			$result = trim($result);
		}
		return $result;
	}
	public static function post($key, $default = null, $sanitize = false)
	{
		$result = Request::getParsedBodyParam($key, $default);
		if ($sanitize) {
			$result = htmlspecialchars($result);
			$result = trim($result);
		}
		return $result;
	}
	public static function query($key, $default = null, $sanitize = true)
	{
		$result = Request::getQueryParam($key, $default);
		if ($sanitize) {
			$result = htmlspecialchars($result);
			$result = trim($result);
		}
		return $result;
	}
}
