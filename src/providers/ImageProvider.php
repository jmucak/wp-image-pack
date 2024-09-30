<?php

namespace jmucak\wpImagePack\providers;

use jmucak\wpImagePack\services\ImageService;

class ImageProvider {
	private array $config;

	public function __construct( array $config = array() ) {
		$this->config = $config;
	}

	public function register(): void {
		if ( ! empty( $this->config['image_sizes'] ) ) {
			foreach ( $this->config['image_sizes'] as $size_name => $size ) {
				ImageService::get_instance()->add_image_size( $size_name, $size );
			}
		}

		// Deregister custom image sizes
		// NOTE: This can't remove core image sizes
		if ( ! empty( $this->config['deregister_image_sizes'] ) ) {
			foreach ( $this->config['deregister_image_sizes'] as $image_size ) {
				remove_image_size( $image_size );
			}
		}
	}
}