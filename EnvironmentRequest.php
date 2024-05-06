<?php

class EnvironmentRequest implements JsonSerializable {

	private string $name;
	private string $chartName;
	private array $values;

	public function __construct( string $name, string $chart ) {
		$this->name = $name;
		$this->chartName = $chart;

		$this->values = [];
	}

	public function withIngress( string $ingress ): EnvironmentRequest {
		$this->setCoreValue( "ingress", $ingress );
		return $this;
	}

	public function withCoreRef( string $ref ): EnvironmentRequest {
		$this->setCoreValue( "ref", $ref );
		return $this;
	}

	public function withExtension( string $extension, string $ref = null ): EnvironmentRequest {
		$extConfig = [ "enable" => true ];
		if ( $ref !== null ) {
			$extConfig += [ "ref" => $ref ];
		}
		$this->values["extensions"][$extension] = $extConfig;
		return $this;
	}

	private function setCoreValue( string $key, string $value ): void {
		$this->values["mediawikiCore"][$key] = $value;
	}

	public function jsonSerialize(): mixed {
		return [
			"name" => $this->name,
			"chartName" => $this->chartName,
			"values" => $this->values,
		];
	}
}
