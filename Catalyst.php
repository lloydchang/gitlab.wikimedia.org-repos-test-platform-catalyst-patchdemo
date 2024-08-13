<?php

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

define( "BASE_CLIENT", HttpClient::create() );

class Catalyst {

	private string $baseUrl;
	private HttpClientInterface $httpClient;

	public static function newClient( string $apiToken ): Catalyst {
		return new Catalyst( $apiToken );
	}

	private function __construct( string $apiToken ) {
		global $config;

		$baseUrl = $config['catalystApiUrl'];
		$this->baseUrl = $baseUrl . '/api';
		$httpClient = ScopingHttpClient::forBaseUri( BASE_CLIENT, $this->baseUrl, [
			'max_redirects' => 1,
			'headers' => [
				'Accept' => 'application/json',
				'Authorization' => "ApiToken $apiToken",
			],
		] );
		$this->httpClient = $httpClient;
	}

	public function getChart( string $chartName ): array {
		return $this->withErr(
			function () use ( $chartName ) {
				return $this->httpClient->request( 'GET', "$this->baseUrl/charts/$chartName" )->toArray();
			},
			static function ( $_ ) {
				return [];
			}
		);
	}

	public function getEnvironments(): array {
		return $this->withErr(
			function () {
				return $this->httpClient->request( 'GET', "$this->baseUrl/environments" )->toArray();
			},
			static function ( $_ ) {
				return [];
			}
		);
	}

	public function postEnvironment( EnvironmentRequest $env ): void {
		$this->withErr(
			function () use ( $env ) {
				$envJson = json_encode( $env, JSON_THROW_ON_ERROR );
				$res = $this->httpClient->request( 'POST', "$this->baseUrl/environments", [
					'headers' => [ 'Content-Type' => 'application/json' ],
					'body' => $envJson,
				] );
			},
			static function ( $e ) {
				throw $e;
			}
		);
	}

	public function getEnvironment( int $id ): ?array {
		return $this->withErr(
			function () use ( $id ) {
				$res = $this->httpClient->request( 'GET', "$this->baseUrl/environments/$id" )->getContent();
				return json_decode( $res, true, 32, JSON_THROW_ON_ERROR );
			},
			static function () {
				return null;
			}
		);
	}

	/**
	 * Default error handling
	 * @return mixed
	 */
	private function withErr( closure $f, closure $recover = null ) {
		$r = $recover ?? static function (){
		};
		try {
			return $f();
		} catch ( Throwable $e ) {
			$this->error( "Error when calling Catalyst: " . $e->getMessage() );
			return $r( $e );
		}
	}

	private function error( string $msg ): void {
		$console_msg = htmlspecialchars( $msg );
		echo "<script>console.error( '$console_msg' );</script>";
	}

}
