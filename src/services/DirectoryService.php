<?php

namespace jmucak\wpOnDemandImages\services;

use WP_Filesystem_Direct;

class DirectoryService {
	public function create_dir_if_not_exists(): void {
		$path = $this->get_image_path();
		if ( ! is_dir( $path ) ) {
			wp_mkdir_p( $path );
		}
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
		$path = $this->get_image_path();

		return is_dir( $path ) && is_writable( $path );
	}

	public function get_image_file_name( string $file_name, int $width, int $height ): string {
		return pathinfo( $file_name, PATHINFO_FILENAME ) . '-' . $width . 'x' . $height . pathinfo( $file_name, PATHINFO_EXTENSION );
	}

	public function get_image_full_path( string $image_path, string $image_file_name ): string {
		return trailingslashit( $image_path ) . $image_file_name;
	}

	public function delete_images(): bool {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		$wp_filesystem_direct = new WP_Filesystem_Direct( false );

		return $wp_filesystem_direct->rmdir( $this->get_image_path(), true );
	}

	public function get_attachment_image_by_size_name( int $attachment_id, string $size_name ): string {
		$image_size_name = ImageService::get_instance()->get_image_size( $size_name );

		if ( empty( $image_size_name ) ) {
			return wp_get_attachment_url( $attachment_id );
		}

		if ( empty( $image_size['size'][0] ) || empty( $image_size['size'][1] ) ) {
			return wp_get_attachment_url( $attachment_id );
		}

		return $this->get_image( $attachment_id, array(
			intval( $image_size['size'][0] ),
			intval( $image_size['size'][1] )
		) );
	}

	public function get_attachment_image_by_custom_size( int $attachment_id, array $size = array() ): string {
		if ( empty( $size[0] ) || empty( $size[1] ) ) {
			return wp_get_attachment_url( $attachment_id );
		}

		return $this->get_image( $attachment_id, $size );
	}

	public function get_image( int $attachment_id, array $size = array() ): string {
		if ( empty( $size[0] ) ) {
			return '';
		}

		$image_meta_data = wp_get_attachment_metadata( $attachment_id );

		if ( ! empty( $image_meta_data['file'] ) ) {
			$image_file_path = $this->get_image_path( $attachment_id ) . DIRECTORY_SEPARATOR . $this->get_image_file_name( basename( $image_meta_data['file'] ),
					$size[0],
					$size[1] );
		}

		// If image exists return image url
		if ( ! empty( $image_file_path ) && file_exists( $image_file_path ) ) {
			return $image_file_path;
		}

		// Check if images directory is writeable
		if ( ! $this->is_dir_writable() ) {
			return '';
		}

		// Get WP Image Editor Instance
		$image_path   = get_attached_file( $attachment_id );
		$image_editor = wp_get_image_editor( $image_path );
		if ( ! is_wp_error( $image_editor ) && ! empty( $image_file_path ) ) {
			// Create new image
			$image_editor->resize( $size[0], $size[1] );
			$image_editor->save( $image_file_path );

			return $image_file_path;
		}

		return wp_get_attachment_url( $attachment_id );
	}
}