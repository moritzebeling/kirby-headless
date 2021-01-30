<?php

load([ 'moritzebeling\\headless\\jsonResponse' => 'src/jsonResponse.php' ], __DIR__);
use moritzebeling\headless\jsonResponse as jsonResponse;

Kirby::plugin('moritzebeling/headless', [

	'options' => [
		'cache' => true,
		'expires' => 1440
	],

	'controllers' => [
        'site' => require 'controllers/site.php',
    ],

	'routes' => [
		[
			'pattern' => 'json/(:all)',
			'method' => 'GET',
			'action'  => function ( $id ) use ($kirby) {

				// if $id is empty, return site data
				$id = $id ? $id : 'site';

				$response = new jsonResponse( $id );

				if( option('moritzebeling.headless.cache', false) ){
					$cache = $kirby->cache('moritzebeling.headless');

					if( $data = $cache->get( $id ) ){
						// response is cached and ready to go
						$response->data( $data, true );
						return $response->json();
					}
				}

				$page = $id === 'site' ? $kirby->site() : $kirby->page( $id );

				if( !$page ){
					// page not found
					$response->status( 404 );
					return $response->json();
				}

				// create dataset from controller
				$response->data(
					$kirby->controller( 'imprint', compact('page') )
				);

				if( option('moritzebeling.headless.cache', false) ){
					// save to cache
					$cache->set(
						$id,
						$response->data(),
						option('moritzebeling.headless.expires',1440)
					);
				}

				return $response->json();
			}
		],
	],

	// clear cache with hooks
	'hooks'  => require_once __DIR__ . '/src/hooks.php',

	// json() methods for $site $page $pages $file $files
	// extend them to your needs here or using mage models

	'siteMethods' => [
        'json' => function ( bool $full = false ): array {

            $json = [
				'title' => $this->title()->value(),
				'pages' => $this->children()->listed()->json()
			];

			return $json;
        }
	],

	'pageMethods' => [
		'json' => function ( bool $full = false ): array {

			$hasImages = $this->hasImages();

			$json = [
				'title' => $this->title()->value(),
				'path' => $this->id(),
				'template' => $this->intendedTemplate()->name(),
				'image' => $hasImages ? $this->image()->json() : null
			];

			if( $full === true && $hasImages === true ){
				$json['images'] = $this->images()->json();
			}

			if( $full === true && $this->hasListedChildren() ){
				$json['pages'] = $this->children()->listed()->json();
			}

			return $json;
		},
	],

	'pagesMethods' => [
        'json' => function ( bool $full = false ): array {
			$json = [];

			foreach($this as $page) {
				$json[] = $page->json( $full );
			}

			return $json;
        }
	],

	'fileMethods' => [
		'json' => function ( bool $includeParent = false ): array {

			$json = [
				'url' => $this->url(),
				'orientation' => $this->orientation(),
				'alt' => $this->title()->value(),
			];

			if( $includeParent === true ){
				$json['parent'] = $this->parent()->id();
			}

			return $json;
        }
	],

	'filesMethods' => [
        'json' => function ( bool $includeParent = false ): array {
			$json = [];

			foreach($this as $file) {
				$json[] = $file->json( $includeParent );
			}

			return $json;
        }
	],

]);
