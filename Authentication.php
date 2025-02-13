<?php

use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\ClientConfig;
use MediaWiki\OAuthClient\Consumer;
use MediaWiki\OAuthClient\Token;

class Authentication {
	private static Authentication $instance;

	/**
	 * Get the singleton instance. This potentially creates/manipulates the session,
	 * so call it only when necessary to avoid filling users’ and our disks with
	 * unnecessary session data.
	 */
	public static function getInstance(): Authentication {
		self::$instance ??= new Authentication;
		return self::$instance;
	}

	private array $config;
	private ?string $authUrl = null;
	private ?Exception $authErr = null;
	private ?stdClass $user = null;

	private function __construct() {
		global $config, $is404;

		$this->config = $config[ 'oauth' ] ?? [];
		if ( $this->useOAuth() && !$is404 ) {
			$this->setUp();
		}
	}

	/** Whether this instance is configured to use OAuth, or anyone can do anything. */
	public function useOAuth(): bool {
		return !empty( $this->config[ 'url' ] );
	}

	/** Whether the user is signed in. */
	public function isSignedIn(): bool {
		return $this->user !== null;
	}

	/** Get the user name of the currently signed in user, if any. */
	public function getUserName(): ?string {
		return $this->user ? $this->user->username : null;
	}

	/**
	 * Whether the user can sign in.
	 * @return bool True if the instance is configured to use OAuth and there is no signed-in user
	 */
	public function canSignIn(): bool {
		return $this->useOAuth() && !$this->isSignedIn();
	}

	/**
	 * Get the sign in prompt.
	 * @return string HTML
	 */
	public function signInPrompt(): string {
		if ( $this->authErr ) {
			return "<div class='signIn'>OAuth error:<br>" . htmlentities( $this->authErr->getMessage() ) . "</div>";
		} else {
			return "<div class='signIn'><a href='{$this->authUrl}'>Sign in with OAuth</a> to create and manage wikis.</div>";
		}
	}

	public function getCsrfToken(): string {
		if ( !$this->useOAuth() ) {
			return '';
		}

		return $_SESSION['csrf_token'];
	}

	public function checkCsrfToken( string $token ): bool {
		if ( !$this->useOAuth() ) {
			return true;
		}
		if ( empty( $_SESSION['csrf_token'] ) ) {
			return false;
		}
		return $_SESSION['csrf_token'] === $token;
	}

	/** Whether the current user can access the admin features. */
	public function canAdmin(): bool {
		if ( !$this->useOAuth() ) {
			// Unauthenticated site
			return true;
		}
		$username = $this->getUserName();
		$admins = $this->config[ 'admins' ];
		return $username && in_array( $username, $admins, true );
	}

	/**
	 * Whether the current user can delete a wiki created by the given user.
	 * @param string|null $creator The wiki creator. If null, only admins can delete the wiki.
	 */
	public function canDelete( ?string $creator ): bool {
		if ( !$this->useOAuth() ) {
			// Unauthenticated site
			return true;
		}
		$username = $this->getUserName();
		return ( $username && $username === $creator ) || $this->canAdmin();
	}

	private function logout(): void {
		unset( $_SESSION['access_key'], $_SESSION['access_secret'] );
		unset( $_SESSION['request_key'], $_SESSION['request_secret'] );
	}

	private function setUp(): void {
		session_start();
		$conf = new ClientConfig( $this->config[ 'url' ] );
		$conf->setConsumer( new Consumer( $this->config[ 'key' ], $this->config[ 'secret' ] ) );
		$client = new Client( $conf );

		if ( isset( $_GET['logout'] ) ) {
			$this->logout();
		}

		if ( isset( $_GET[ 'oauth_verifier' ] ) && isset( $_SESSION['request_key'] ) ) {
			$requestToken = new Token( $_SESSION['request_key'], $_SESSION['request_secret'] );
			$accessToken = $client->complete( $requestToken, $_GET['oauth_verifier'] );

			$_SESSION['access_key'] = $accessToken->key;
			$_SESSION['access_secret'] = $accessToken->secret;

			unset( $_SESSION['request_key'], $_SESSION['request_secret'] );
		}

		if ( !empty( $_SESSION['access_key'] ) ) {
			$accessToken = new Token( $_SESSION['access_key'], $_SESSION['access_secret'] );
			$this->user = $client->identify( $accessToken );
		} else {
			$client->setCallback( $this->config[ 'callback' ] );

			try {
				list( $this->authUrl, $token ) = $client->initiate();
			} catch ( Exception $e ) {
				// e.g. Invalid signature error
				$this->logout();
				$token = null;
				$this->authErr = $e;
			}

			if ( $token ) {
				$_SESSION['request_key'] = $token->key;
				$_SESSION['request_secret'] = $token->secret;
			}
		}
		if ( empty( $_SESSION['csrf_token'] ) ) {
			$_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
		}
		session_write_close();
	}
}
