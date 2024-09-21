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
	}

	public function deregister(): void {
		if ( ! empty( $this->config['deregister_image_sizes'] ) ) {
			add_action( 'intermediate_image_sizes_advanced', array( $this, 'deregister_image_sizes' ) );
		}
	}

	public function deregister_image_sizes( array $sizes ): array {
		foreach ( $sizes as $size ) {
			if ( in_array( $size, $this->config['deregister_image_sizes'] ) ) {
				unset( $sizes[ $size ] );
			}
		}

		return $sizes;
	}


}