<?php

namespace jmucak\wpImagePack\services;

use Exception;
use WP_Filesystem_Direct;

class DirectoryService {

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
}