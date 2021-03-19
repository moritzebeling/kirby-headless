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

	'api' => [
		'routes' => function ($kirby) {
			return [
				[
					'pattern' => 'json(:all)',
					'method' => 'GET',
					'action'  => function ( $request ) use( $kirby ) {

						$request = trim( $request, '/' );
						$request = !$request ? 'site' : $request;
						$target = $request === 'site' ? $kirby->site() : $kirby->page( $request );

						$response = [
							'status' => 404,
							'request' => $request,
							'language' => $kirby->language()->code(),
							'cached' => false,
							'data' => []
						];

						if( !$target ){
							// return 404
							return $response;
						}

						if( option('moritzebeling.headless.cache', false) ){
							$id = $response['language'] . '/' . $request;
							$cache = $kirby->cache('moritzebeling.headless');
							if( $data = $cache->get( $id ) ){
								// return from cache
								return array_merge( $response, [
									'status' => 200,
									'cached' => true,
									'data' => $data
								]);
							}
						}

						// get fresh data
						$data = $target->json( true );

						if( option('moritzebeling.headless.cache', false) ){
							// save to cache
							$cache->set( $id, $data,
								option('moritzebeling.headless.expires',1440)
							);
						}

						return array_merge( $response, [
							'status' => 200,
							'data' => $data
						]);
					}
				]
			];
		}
	],

	// clear cache with hooks
	'hooks'  => require_once __DIR__ . '/src/hooks.php',

	// json() methods for $site $page $pages $file $files
	// extend them to your needs here or using mage models

	'siteMethods' => [
        'json' => function ( bool $full = false ): array {

            $json = [
				'url' => '/'.$this->kirby()->language()->code(),
				'title' => $this->title()->value(),
				'listed' => $this->children()->listed()->json(),
				'unlisted' => $this->footerMenu()->toPages()->json()
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

			$lang = $this->kirby()->language();
			$json = [
				'title' => $this->title()->value(),
				'path' => $lang->code() .'/'. $this->uri( $lang->code() ),
				'template' => $this->intendedTemplate()->name()
			];
			if( $full ){

				$json['text'] = $this->text()->json('kirbytext');

				$other = $this->kirby()->languages()->not( $lang )->first();
				$json['translation'] = [
					'lang' => $other->code(),
					'url' => $this->urlForLanguage( $other->code() )
				];

			}

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
