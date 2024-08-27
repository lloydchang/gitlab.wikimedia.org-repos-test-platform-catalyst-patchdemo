<?php

use Symfony\Component\HttpClient\Chunk\ServerSentEvent;
use Symfony\Component\HttpClient\EventSourceHttpClient;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

define( "BASE_CLIENT", HttpClient::create() );

class Catalyst {

	private string $baseUrl;
	private HttpClientInterface $httpClient;
	private EventSourceHttpClient $eventSourceHttpClient;

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
		$eventSourceHttpClient = new EventSourceHttpClient( $httpClient );
		$this->httpClient = $httpClient;
		$this->eventSourceHttpClient = $eventSourceHttpClient;
	}

	public function streamLogs( string $id, string $containerName, callable $handlerFn ): void {
		do {
			// keep trying in case pod is initializing
			sleep( 5 );
			$source = $this->eventSourceHttpClient->connect( "$this->baseUrl/environments/$id/logs?stream=$containerName" );
		} while ( $source->getStatusCode() === 503 );
		$finished = false;
		while ( $source && !$finished ) {
			$finished = $this->withErr(
				function () use ( $source, $handlerFn ) {
					foreach ( $this->eventSourceHttpClient->stream( $source, 300 ) as $r => $chunk ) {
						if ( $chunk instanceof ServerSentEvent ) {
							$data = $chunk->getArrayData();
							$logs = $data['logs'];
							$handlerFn( $logs );
						}

						if ( $chunk->isLast() ) {
							return true;
						}
					}
					return false;
				}
			);

		}
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

	public function deleteEnvironment( string $id ): array {
		return $this->withErr(
			function () use ( $id ) {
				return $this->httpClient->request( 'DELETE', "$this->baseUrl/environments/$id" )->toArray();
			},
			static function ( $_ ) {
				return [];
			}
		);
	}

	public function postEnvironment( EnvironmentRequest $env ): array {
		return $this->withErr(
			function () use ( $env ) {
				$envJson = json_encode( $env, JSON_THROW_ON_ERROR );
				return $this->httpClient->request( 'POST', "$this->baseUrl/environments", [
					'headers' => [ 'Content-Type' => 'application/json' ],
					'body' => $envJson,
				] )->toArray();
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
