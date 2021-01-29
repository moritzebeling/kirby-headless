<?php

function flushCache( $id = false ){

	$cache = kirby()->cache('moritzebeling.headless');

	if( $id === false ){
        // flush whole cache
		$cache->flush();
	} else {
        // flush cache of this page/file and every of its parents
		$parts = explode( '/', $id );
		foreach( $parts as $part ){
			$cache->remove( implode( '/', $parts ) );
			array_pop( $parts );
		}
		$cache->remove( 'archive' );
	}
}

return array(
    'page.*:after' => function ( $event ) {
        $page = $event->page() ? $event->page() : $event->newPage();
        switch ( $event->action() ) {
            case 'create':
            case 'delete':
            case 'changeSlug':
            case 'changeStatus':
            case 'changeTitle':
            case 'duplicate':
            case 'update':
                flushCache( $page->id() );
        }
    },
    'file.*:after' => function ( $event ) {
        $file = $event->file() ? $event->file() : $event->newFile();
        switch ( $event->action() ) {
            case 'create':
            case 'delete':
            case 'changeName':
            case 'changeSort':
            case 'replace':
            case 'update':
                flushCache( $file->parentId() );
        }
    },
    'site.update:after' => function () {
        flushCache('site');
    }
);
