<?php

class jsonResponse {

	protected $request;
	protected $data;
	protected $status;
	protected $cached;

	public function __construct( string $request = '', ?array $data = null, int $status = 200, bool $cached = false )
	{
		$this->request = $request;
		$this->data = $data;
		$this->status = $status;
		$this->cached = $cached;
	}

	public function data( ?array $data = null, ?bool $cached = null ){
		if( $data !== null ){
			$this->data = $data;
		}
		if( $cached !== null ){
			$this->cached = $cached;
		}
		return $this->data;
	}

	public function status( ?int $status = null ){
		if( $status !== null ){
			$this->status = $status;
		}
		return $this->status;
	}

	public function cached( ?bool $cached = null ){
		if( $cached !== null ){
			$this->cached = $cached;
		}
		return $this->cached;
	}

	public function json(): array
	{
		return [
			'status' => $this->status,
			'request' => $this->request,
			'cached' => $this->cached,
			'data' => $this->data,
		];
	}

}

Kirby::plugin('moritzebeling/headless', [

	'options' => [
		'cache' => true,
		'expires' => 1440
	],

	'routes' => [
		[
			'pattern' => 'json/(:all)',
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
					// site
					$response->data( $kirby->site()->json( true ) );

				} else if( $page = $kirby->page( $id ) ){
					// page
					$response->data( $page->json( true ) );

				} else {
					// 404
					$response->status( 404 );
					return $response->json();

				}

				if( option('moritzebeling.headless.cache', false) ){
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
				'image' => $hasImages ? $this->image()->json() : null
			];

			if( $full === true && $hasImages === true ){
				$json['images'] = $this->images()->json();
			}

			if( $full === true && $this->hasListedChildren() ){
				$json['pages'] = $this->children()->listed()->json();
			}

			return $json;
        }
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
