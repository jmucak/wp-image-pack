<?php

namespace jmucak\wpImagePack\services;

use Exception;

class ImageService {
	protected DirectoryService $directory_service;
	public static array $instances = [];

	// Protected constructor to prevent direct instantiation
	protected function __construct() {
		$this->directory_service = new DirectoryService();
	}

	public static function get_instance() {
		$class = static::class; // Get the name of the current class
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}

	// Prevent cloning
	protected function __clone() {
	}

	// Prevent unserialization
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize a singleton.' );
	}

	private array $image_sizes = array();

	// Register multiple sizes
	public function register_image_sizes( array $sizes ): void {
		$sizes = array_merge( $sizes, wp_get_registered_image_subsizes() );

		if ( ! empty( $sizes ) ) {
			foreach ( $sizes as $size_name => $size ) {
				$this->add_image_size( $size_name, array_values( $size ) );
			}
		}
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

	// return all image sizes
	public function get_all_image_sizes(): array {
		return $this->image_sizes;
	}

	public function get_attachment_image_by_size_name( int $attachment_id, string $size_name ): string {
		return $this->_get_image( $attachment_id, $this->get_size_by_image_name( $size_name ) );
	}

	public function get_attachment_image_by_custom_size( int $attachment_id, array $size = array() ): string {
		return $this->_get_image( $attachment_id, $this->validate_size( $size ) );
	}

	public function get_image_url( int $attachment_id, string|array $size ): string {
		if ( is_string( $size ) ) {
			return $this->get_attachment_image_by_size_name( $attachment_id, $size );
		}

		return $this->get_attachment_image_by_custom_size( $attachment_id, $size );
	}

	public function get_image( int $attachment_id, string|array $size ): array {
		$url             = $this->get_image_url( $attachment_id, $size );
		$image_meta_data = wp_get_attachment_metadata( $attachment_id );

		return array(
			'url' => $url,
		);
	}

	public function _get_image( int $attachment_id, ?array $size = array() ): string {
		// if empty or not valid size, return full size image
		if ( empty( $size ) ) {
			return wp_get_attachment_url( $attachment_id );
		}

		$image_meta_data = wp_get_attachment_metadata( $attachment_id );
		$image_file_name = $this->directory_service->get_image_file_name( basename( $image_meta_data['file'] ), $size );

		if ( empty( $image_meta_data['file'] ) ) {
			return '';
		}

		$image_file_path = $this->directory_service->get_image_path( $attachment_id ) . DIRECTORY_SEPARATOR . $image_file_name;

		// If image exists return image url
		if ( file_exists( $image_file_path ) ) {
			return $this->directory_service->get_image_full_path( $attachment_id, $image_file_name );
		}

		// Check if images directory is writeable
		if ( ! $this->directory_service->is_dir_writable() ) {
			return '';
		}

		// Get WP Image Editor Instance
		$image_path   = get_attached_file( $attachment_id );
		$image_editor = wp_get_image_editor( $image_path );

		if ( empty( $image_editor ) || is_wp_error() ) {
			return wp_get_attachment_url( $attachment_id );
		}


		// Create new image
		$image_editor->resize( $size[0], $size[1], $size[2] );
		$image_editor->save( $image_file_path );

		return $this->directory_service->get_image_full_path( $attachment_id, $image_file_name );
	}
}