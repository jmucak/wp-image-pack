<?php

namespace jmucak\wpImagePack\services;

use Exception;

class ImageService {
	protected ImageDirectoryService $directory_service;
	private array $image_sizes = array();
	private array $default_image_sizes = array();
	private static $instance = null;

	// Protected constructor to prevent direct instantiation
	private function __construct() {
		$this->directory_service   = new ImageDirectoryService();
		$this->default_image_sizes = wp_get_registered_image_subsizes();
	}

	public static function get_instance(): ?ImageService {
		if ( self::$instance === null ) {
			self::$instance = new ImageService();
		}

		return self::$instance;
	}

	// Prevent cloning
	protected function __clone() {
	}

	// Prevent unserialization
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize a singleton.' );
	}


	// Register only one size
	public function add_image_size( string $size_name, array $size = array() ): void {
		$size_array = $this->validate_size( $size );

		if ( ! empty( $size_array ) ) {
			$this->image_sizes[ $size_name ] = $size_array;
		}
	}

	// validate entered size array
	public function validate_size( array $size ): ?array {
		if ( empty( $size ) || empty( $size[0] ) || ! is_int( $size[0] ) ) {
			return null;
		}

		if ( empty( $size[1] ) || ! is_int( $size[1] ) ) {
			$size[1] = 0;
		}

		// set crop
		if ( empty( $size[2] ) ) {
			$size[2] = false;
		}

		return $size;
	}

	public function get_size_by_image_name( string $size_name = '' ): ?array {
		if ( empty( $size_name ) || ! isset( $this->image_sizes[ $size_name ] ) ) {
			return null;
		}

		return $this->validate_size( $this->image_sizes[ $size_name ] );
	}

	// return custom image sizes + WP registered image sizes
	public function get_all_image_sizes(): array {
		return array_merge( $this->image_sizes, $this->default_image_sizes );
	}

	public function get_attachment_image_by_size_name( int $attachment_id, string $size_name ): string {
		if ( ! empty( $this->default_image_sizes[ $size_name ] ) ) {
			// return wp image from db
			$image_src = wp_get_attachment_image_src( $attachment_id, $size_name );

			return ! empty( $image_src[0] ) ? $image_src[0] : '';
		}

		return $this->directory_service->get_image( $attachment_id, $this->get_size_by_image_name( $size_name ) );
	}

	public function get_attachment_image_by_custom_size( int $attachment_id, array $size = array() ): string {
		return $this->directory_service->get_image( $attachment_id, $this->validate_size( $size ) );
	}

	public function get_image_url( int $attachment_id, string|array $size ): string {
		if ( is_string( $size ) ) {
			return $this->get_attachment_image_by_size_name( $attachment_id, $size );
		}

		return $this->get_attachment_image_by_custom_size( $attachment_id, $size );
	}

	public function get_image_data( int $attachment_id, string|array $size ): array {
		$alt = trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );

		return array(
			'url' => $this->get_image_url( $attachment_id, $size ),
			'alt' => ! empty( $alt ) ? $alt : sanitize_title( get_the_title( $attachment_id ) ),
		);
	}
}