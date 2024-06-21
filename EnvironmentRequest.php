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

	public function withCoreRefs( array $refs ): EnvironmentRequest {
		$this->setCoreValue( "patches", $refs );
		return $this;
	}

	public function withExtension( string $extension, array $refs = null ): EnvironmentRequest {
		$extConfig = [ "enable" => true ];
		if ( $refs !== null ) {
			$extConfig += [ "patches" => $refs ];
		}
		$this->values["extensions"][$extension] = $extConfig;
		return $this;
	}

	public function withSkin( string $skin, array $refs = null ): EnvironmentRequest {
		$skinConfig = [ "enable" => true ];
		if ( $refs !== null ) {
			$skinConfig += [ "patches" => $refs ];
		}
		$this->values["skins"][$skin] = $skinConfig;
		return $this;
	}

	private function setCoreValue( string $key, mixed $value ): void {
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
