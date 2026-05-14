<?php
declare(strict_types=1);

namespace LuandaVAT\controllers;

class AuthController {
	private static bool $isAuthenticated = false; 
	private static AuthState $authState;
	
	public static function getState(): AuthState {
		if (isset($_SESSION['authState'])) {
			self::$authState = $_SESSION['authState'];
			self::$isAuthenticated = self::$authState->loggedIn;
		}
		
		return self::$authState;
	}
	
	public static function isAuthenticated() {
		return self::$isAuthenticated;
	}
}

class AuthState {
	public string $userName = '';
	public bool $loggedIn = false;
	public int $userRole = user_roles::GUEST;
}

class user_roles {
	public const GUEST = 0;
	public const USER = 1;
	public const ADMIN = 2;
}

class auth_req {
	public const NONE 	= 0;
	public const BASIC 	= 1;
	public const ADMIN	= 2;
	public const UNAUTH	= 4;
}

?>