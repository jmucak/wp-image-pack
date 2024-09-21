<?php

namespace jmucak\wpImagePack\services;

use WP_Filesystem_Direct;

class ImageDirectoryService {

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

		$folder_path = trailingslashit( $wp_upload_dir['basedir'] ) . 'wp-image-pack/';

		if ( empty( $path ) ) {
			return $folder_path;
		}

		return $folder_path . $path;
	}

	public function is_dir_writable(): bool {
		$path = $this->create_dir_if_not_exists();

		return is_dir( $path ) && is_writable( $path );
	}

	public function get_image_file_name( string $file_name, array $size ): string {
		$crop_extension = '';
		if ( ! empty( $size[2] ) ) {
			$crop_extension = '-c';
		}

		return pathinfo( $file_name, PATHINFO_FILENAME ) . '-' . $size[0] . 'x' . $size[1] . $crop_extension . '.' . pathinfo( $file_name,
				PATHINFO_EXTENSION );
	}

	public function get_image_full_path( int $attachment_id, string $image_file_name ): string {
		$wp_upload_dir = wp_upload_dir();

		return trailingslashit( $wp_upload_dir['baseurl'] ) . 'wp-image-pack/' . $attachment_id . '/' . $image_file_name;
	}

	public function delete_images(): bool {
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
		$wp_filesystem_direct = new WP_Filesystem_Direct( false );

		return $wp_filesystem_direct->rmdir( $this->get_image_path(), true );
	}

	public function get_image( int $attachment_id, ?array $size = array() ): string {
		// if empty or not valid size, return full size image
		if ( empty( $size ) ) {
			return wp_get_attachment_url( $attachment_id );
		}

		$image_meta_data = wp_get_attachment_metadata( $attachment_id );
		$image_file_name = $this->get_image_file_name( basename( $image_meta_data['file'] ), $size );

		if ( empty( $image_meta_data['file'] ) ) {
			return '';
		}

		$image_file_path = $this->get_image_path( $attachment_id ) . DIRECTORY_SEPARATOR . $image_file_name;

		// If image exists return image url
		if ( file_exists( $image_file_path ) ) {
			return $this->get_image_full_path( $attachment_id, $image_file_name );
		}

		// Check if images directory is writeable
		if ( ! $this->is_dir_writable() ) {
			return '';
		}

		// Get WP Image Editor Instance
		$image_path   = get_attached_file( $attachment_id );
		$image_editor = wp_get_image_editor( $image_path );

		if ( empty( $image_editor ) || is_wp_error($image_editor) ) {
			return wp_get_attachment_url( $attachment_id );
		}


		// Create new image
		$image_editor->resize( $size[0], $size[1], $size[2] );
		$image_editor->save( $image_file_path );

		return $this->get_image_full_path( $attachment_id, $image_file_name );
	}
}