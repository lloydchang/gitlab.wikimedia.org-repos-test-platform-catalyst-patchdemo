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

	public function withBranch( string $branch ): EnvironmentRequest {
		$this->setCoreValue( "branch", $branch );
		return $this;
	}

	public function withIngress( string $ingress ): EnvironmentRequest {
		$this->setCoreValue( "ingress", $ingress );
		return $this;
	}

	public function withCoreRefs( array $refs ): EnvironmentRequest {
		$this->setCoreValue( "patches", $refs );
		return $this;
	}

	public function useProxy(): EnvironmentRequest {
		$this->setCoreValue( "useProxy", true );
		return $this;
	}

	public function withExtension( string $extension, string $branch, array $refs = [] ): EnvironmentRequest {
		return $this->withComponent( "extensions", $extension, $branch, $refs );
	}

	public function withSkin( string $skin, string $branch, array $refs = [] ): EnvironmentRequest {
		return $this->withComponent( "skins", $skin, $branch, $refs );
	}

	public function withModule( string $module, string $branch, array $refs = [] ): EnvironmentRequest {
		return $this->withComponent( "otherModules", $module, $branch, $refs );
	}

	public function useRepositoryPool( string $poolPath ): EnvironmentRequest {
		$this->values["reposCache"] = [ "use" => true, "wikiRepos" => $poolPath ];
		return $this;
	}

	private function setCoreValue( string $key, mixed $value ): void {
		$this->values["mediawikiCore"][$key] = $value;
	}

	private function withComponent( string $type, string $component, string $branch, array $refs ): EnvironmentRequest {
		$compConfig = [ "enable" => true, "branch" => $branch ];
		if ( $refs ) {
			$compConfig += [ "patches" => $refs ];
		}
		$this->values[$type][$component] = $compConfig;
		return $this;
	}

	public function jsonSerialize(): mixed {
		return [
			"name" => $this->name,
			"chartName" => $this->chartName,
			"values" => $this->values,
		];
	}
}
