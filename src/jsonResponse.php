<?php

namespace moritzebeling\headless;

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
