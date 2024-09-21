<?php

namespace jmucak\wpImagePack\services;

use Exception;
use WP_Filesystem_Direct;

class DirectoryService {
	// Holds the single instance of the class
	public static array $instances = [];

	// Protected constructor to prevent direct instantiation
	protected function __construct() {
	}

	// Prevent cloning
	protected function __clone() {
	}

	// Prevent unserialization
	public function __wakeup() {
		throw new Exception( 'Cannot unserialize a singleton.' );
	}

	public static function get_instance() {
		$class = static::class; // Get the name of the current class
		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static();
		}

		return self::$instances[ $class ];
	}

	public function create_dir_if_not_exists(): string {
		$path = $this->get_image_path();
		if ( ! is_dir( $path ) ) {
			wp_mkdir_p( $path );
		}

		return $path;
	}

	public function get_image_path( string $path = '' ): string {
		$wp_upload_dir = wp_upload_dir();

		if ( empty( $wp_upload_dir['basedir'] ) ) {
			return '';
		}

		$folder_path = trailingslashit( $wp_upload_dir['basedir'] ) . 'wp-on-demand-images/';

		if ( empty( $path ) ) {
			return $folder_path;
		}

		return $folder_path . $path;
	}

	public function is_dir_writable(): bool {
		$path = $this->create_dir_if_not_exists();

		return is_dir( $path ) && is_writable( $path );
	}

	public function get_image_file_name( string $file_name, int $width, int $height, bool $crop = false ): string {
		$crop_extension = '';
		if ( $crop ) {
			$crop_extension = '-c';
		}

		return pathinfo( $file_name, PATHINFO_FILENAME ) . '-' . $width . 'x' . $height . $crop_extension . '.' . pathinfo( $file_name,
				PATHINFO_EXTENSION );
	}

	public function get_image_full_path( int $attachment_id, string $image_file_name ): string {
		$wp_upload_dir = wp_upload_dir();

		return trailingslashit( $wp_upload_dir['baseurl'] ) . 'wp-on-demand-images/' . $attachment_id . '/' . $image_file_name;
	}

	public function delete_images(): bool {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		$wp_filesystem_direct = new WP_Filesystem_Direct( false );

		return $wp_filesystem_direct->rmdir( $this->get_image_path(), true );
	}

	public function _get_image( int $attachment_id, ?array $size = array() ): string {
		// if empty or not valid size, return full size image
		if ( empty( $size ) ) {
			return wp_get_attachment_url( $attachment_id );
		}

		$image_meta_data = wp_get_attachment_metadata( $attachment_id );
		$image_file_name = $this->get_image_file_name( basename( $image_meta_data['file'] ), $size[0], $size[1], $size[2] );

		if ( ! empty( $image_meta_data['file'] ) ) {
			$image_file_path = $this->get_image_path( $attachment_id ) . DIRECTORY_SEPARATOR . $image_file_name;
		}

		// If image exists return image url
		if ( ! empty( $image_file_path ) && file_exists( $image_file_path ) ) {
			return $this->get_image_full_path( $attachment_id, $image_file_name );
		}

		// Check if images directory is writeable
		if ( ! $this->is_dir_writable() ) {
			return '';
		}

		// Get WP Image Editor Instance
		$image_path = get_attached_file( $attachment_id );
		$image_editor = wp_get_image_editor( $image_path );
		if ( ! is_wp_error( $image_editor ) && ! empty( $image_file_path ) ) {
			// Create new image
			$image_editor->resize( $size[0], $size[1], $size[2] );
			$image_editor->save( $image_file_path );

			return $this->get_image_full_path( $attachment_id, $image_file_name );
		}

		return wp_get_attachment_url( $attachment_id );
	}
}