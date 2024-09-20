<?php

namespace jmucak\wpOnDemandImages\services;

class ImageService {
	private static ?ImageService $instance = null;

	private array $image_sizes = array();

	private function __construct() {
	}

	public static function get_instance(): ?ImageService {
		if ( self::$instance === null ) {
			self::$instance = new ImageService();
		}

		return self::$instance;
	}

	public function add_image_size( string $size_name = '', array $size = array() ): bool {
		if ( empty( $size_name ) || empty( $size[0] || empty( $size[1] ) ) ) {
			return false;
		}

		$this->image_sizes[ $size_name ] = $size;

		return true;
	}

	public function get_image_size( string $size_name = '' ): ?array {
		if ( empty( $size_name ) || ! isset( $this->image_sizes[ $size_name ] ) ) {
			return null;
		}

		return $this->image_sizes[ $size_name ];
	}

	public function get_all_image_sizes(): array {
		return $this->image_sizes;
	}
}