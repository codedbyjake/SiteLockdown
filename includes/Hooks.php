<?php

namespace MediaWiki\Extension\SiteLockdown;

use MediaWiki\Installer\DatabaseUpdater;
use MediaWiki\Title\Title;
use MediaWiki\User\User;

class Hooks {

    public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ): void {
        $updater->addExtensionTable(
            'sitelockdown_state',
            __DIR__ . '/../sql/mysql/tables-generated.sql'
        );
    }

    public static function onGetUserPermissionsErrors( Title $title, User $user, string $action, &$result ) {
        if ( !in_array( $action, [ 'edit', 'createaccount' ], true ) ) {
            return true;
        }

        if ( $user->isAllowed( 'sitelockdown-exempt' ) ) {
            return true;
        }

        if ( !LockdownState::isActive() ) {
            return true;
        }

        $result = 'sitelockdown-active';
        return false;
    }
}
