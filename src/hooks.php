<?php

return array(
    'page.*:after' => function ( $event ) {
        switch ( $event->action() ) {
            case 'changeNum':
            case 'changeSlug':
            case 'changeStatus':
            case 'changeTemplate':
            case 'changeTitle':
            case 'update':
                $event->newPage()->clearCache();
                break;
            case 'create':
            case 'delete':
                $event->page()->clearCache();
                break;
        }
    },
    'file.*:after' => function ( $event ) {
        switch ( $event->action() ) {
            case 'changeName':
            case 'changeSort':
            case 'replace':
            case 'update':
                $event->newFile()->clearCache();
                break;
            case 'create':
            case 'delete':
                $event->file()->clearCache();
                break;
        }
    },
    'site.*:after' => function ( $event ) {
        switch ( $event->action() ) {
            case 'changeTitle':
            case 'update':
                $event->newSite()->clearCache();
                break;
        }
    }
);
