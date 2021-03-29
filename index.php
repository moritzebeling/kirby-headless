<?php

Kirby::plugin('moritzebeling/headless', [

	'options' => [
		'cache' => false,
		'expires' => 1440,
		'thumbs' => [
			'srcset' => [640,854,1280,1920]
		]
	],

	'blueprints' => [
        'users/apiclient' => __DIR__ . '/blueprints/apiclient.yml',
    ],

	'hooks'  => require_once __DIR__ . '/hooks/hooks.php',

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
	'routes' => [
		[
			'pattern' => '(:all).json',
			'method' => 'GET',
			'language' => '*',
			'action'  => function ( $language, $path ){

				if( !option('debug') ){
					return [];
				}

				if( $path === 'site' ){
					return kirby()->site()->json( true );
				} else if( $page = page( $path ) ){
					return $page->json( true );
				} else {
					return [];
				}

			}
		]
	],

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
				'path' => '/'. $lang->code() .'/'. $this->uri( $lang->code() ),
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
				'title' => $this->title()->value(),
				'url' => $this->url(),
			];

			if( !$size || !$this->isResizable() || $this->extension() === 'gif' ){
				return $json;
			}

			$sizes = option( 'thumbs.srcsets.'.$size,
				option('moritzebeling.headless.thumbs.srcset') );
			$srcset = [];
			foreach( $sizes as $width ){
				if( is_array( $width ) ){
					$url = $this->thumb( $width )->url();
					$width = $width['width'];
				} else {
					$url = $this->thumb(['width' => $width])->url();
				}
				$srcset[] = [
					'width' => $width,
					'url' => $url
				];
			}

			return array_merge($json,[
				'url' => $this->thumb( $size )->url(),
				'caption' => $this->caption()->kirbytextinline()->value(),
				'srcset' => $srcset
			]);
        },
		'clearCache' => function () {
            $this->parent()->clearCache();
        },
	],

	'filesMethods' => [
        'json' => function ( string $size = 'l' ) {
			$json = [];
			foreach($this as $file) {
				$json[] = $file->json( $size );
			}
			return count($json) > 0 ? $json : false;
        }
	],

	'fieldMethods' => [
        'urlHost' => function ( $field ): string {
			if( $host = parse_url( $field->value, PHP_URL_HOST ) ){
				return $host;
			}
			return '';
		},
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
					$files = [];
					foreach( $field->yaml() as $file ){
						if( $file = $field->parent()->file( $file ) ){
							$files[] = $file->json();
						}
					}
					return count($files) > 0 ? $files : false;
					break;
				case 'files':
					$files = [];
					foreach( $field->yaml() as $file ){
						if( $file = $field->parent()->file( $file ) ){
							$f = array_merge( $file->json(), [
								'size' => F::niceSize( F::size( $file->root() ) )
							]);
							unset( $f['caption'] );
							unset( $f['srcset'] );
							$files[] = $f;
						}
					}
					return count($files) > 0 ? $files : false;
					break;
				case 'kirbytext':
					return $field->kirbytext()->value();
					break;
				case 'yaml':
					$yaml = $field->yaml();
					return count($yaml) > 0 ? $yaml : false;
					break;
				case 'blocks':
					return $field->toBlocks()->toHtml();
					break;
				case 'link':
					return [
						'href' => $field->value(),
						'title' => $field->urlHost()
					];
					break;
				case 'links':
					$links = [];
					foreach( $field->toStructure() as $link ){
						if( $field->href()->isEmpty() ){
							continue;
						}
						$links[] = [
							'title' => $link->title()->isNotEmpty() ? $link->title()->value() : $link->href()->urlHost(),
							'href' => $link->href()->value(),
						];
					}
					return count($links) > 0 ? $links : false;
					break;
				default:
					return $field->value();
					break;
			}
        },
	],

]);
