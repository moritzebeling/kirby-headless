<?php

load([ 'moritzebeling\\headless\\jsonResponse' => 'src/jsonResponse.php' ], __DIR__);
use moritzebeling\headless\jsonResponse as jsonResponse;

Kirby::plugin('moritzebeling/headless', [

	'options' => [
		'cache' => false,
		'expires' => 1440,
		'thumbs' => [
			'thumb' => ['width' => 426],
			'srcset' => [640, 854, 1280, 1920],
		],
	],

	'controllers' => [
        'global' => require 'controllers/global.php',
        'site' => require 'controllers/site.php',
    ],

	'routes' => [
		[
			'pattern' => 'json/(:all)',
			'method' => 'GET',
			'action'  => function ( $id ) {

				// if $id is empty, return site data
				$id = $id ? $id : 'site';
				$kirby = kirby();

				$response = new jsonResponse( $id );

				if( option('moritzebeling.headless.cache', false) ){
					$cache = $kirby->cache('moritzebeling.headless');

					if( $data = $cache->get( $id ) ){
						// response is cached and ready to go
						$response->data( $data, true );
						return $response->json();
					}
				}

				if( $id === 'site' ){
					// global site information
					$data = $kirby->site()->json( true );

				} else if ( $page = $kirby->page( $id ) ){
					// page
					$data = $page->json( true );

				} else {
					// page not found
					$response->status( 404 );
					return $response->json();

				}

				if( option('moritzebeling.headless.cache', false) ){
					// save to cache
					$cache->set(
						$id,
						$data,
						option('moritzebeling.headless.expires',1440)
					);
				}

				$response->data( $data );
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
				'listed' => $this->children()->listed()->json(),
				'unlisted' => $this->children()->unlisted()->json()
			];

			return $json;
        },
		'clearCache' => function ( Kirby\Cache\Cache $cache = null ) {
            if( $cache === null ){
                $cache = $this->kirby()->cache('encodinggroup.jsonApi');
            }
            $cache->flush();
        },
	],

	'pageMethods' => [
		'json' => function ( bool $full = false ): array {

			$json = [
				'title' => $this->title()->value(),
				'path' => $this->id(),
				'template' => $this->intendedTemplate()->name()
			];

			return $json;
		},
		'clearCache' => function ( Kirby\Cache\Cache $cache = null, bool $populate = true ) {
            if( $cache === null ){
                $cache = $this->kirby()->cache('encodinggroup.jsonApi');
            }
            $cache->remove( $this->id() );
            if( $populate && $parent = $this->parent() ){
                $parent->clearCache( $cache );
            }
        },
	],

	'pagesMethods' => [
        'json' => function ( bool $full = false ): array {
			$json = [];
			foreach($this as $page) {
				$json[] = $page->json( $full );
			}
			return $json;
        },
		'clearCache' => function ( Kirby\Cache\Cache $cache = null, bool $populate = true ) {
            if( $cache === null ){
                $cache = $this->kirby()->cache('encodinggroup.jsonApi');
            }
            foreach( $this as $page ){
                $page->clearCache( $cache, $populate );
                $populate = false;
            }
        },
	],

	'fileMethods' => [
		'json' => function ( string $size = 'l' ): array {

			$json = [
				'alt' => $this->alt()->value(),
				'url' => $this->url(),
			];

			if( !$size || !$this->isResizable() || $this->extension() === 'gif' ){
				return $json;
			}

			$srcset = [];
			foreach( option('moritzebeling.headless.thumbs.srcset') as $width ){
				$srcset[] = [
					'width' => $width,
					'url' => $this->thumb(['width' => $width])->url(),
				];
			}

			return array_merge($json,[
				'url' => $this->thumb( option('moritzebeling.headless.thumbs.thumb') )->url(),
				'caption' => $this->caption()->kirbytextinline()->value(),
				'srcset' => $srcset
			]);
        },
		'clearCache' => function () {
            $this->parent()->clearCache();
        },
	],

	'filesMethods' => [
        'json' => function ( string $size = 'l' ): array {
			$json = [];
			foreach($this as $file) {
				$json[] = $file->json( $size );
			}
			return $json;
        }
	],

	'fieldMethods' => [
        'json' => function ( $field, string $type = 'text' ) {
			if( $field->isEmpty() ){
				return false;
			}
			switch ($type) {
				case 'image':
				case 'file':
					if( $file = $field->parent()->file( $field->yaml()[0] ) ){
						$file = $file->json();
					}
					return $file;
					break;
				case 'images':
				case 'files':
					$files = [];
					foreach( $field->yaml() as $file ){
						if( $file = $field->parent()->file( $file ) ){
							$files[] = $file->json();
						}
					}
					return $files;
					break;
				case 'kirbytext':
					return $field->kirbytext()->value();
					break;
				case 'blocks':
					return $field->toBlocks()->toHtml();
					break;
				default:
					return $field->value();
					break;
			}
        },
	],

]);
