<?php

return array(
    'page.*:after' => function ( $event ) {
        $page = $event->page() ? $event->page() : $event->newPage();
        switch ( $event->action() ) {
            case 'create':
            case 'delete':
            case 'changeSlug':
            case 'changeStatus':
            case 'changeTitle':
            case 'update':
                $page->clearCache();
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
                $page->clearCache();
        }
    },
    'site.*:after' => function ( $event ) {
        $site = $site->page() ? $site->page() : $site->newSite();
        switch ( $event->action() ) {
            case 'changeTitle':
            case 'update':
                $site->clearCache();
        }
    }
);
