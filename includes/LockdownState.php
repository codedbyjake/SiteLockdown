<?php

namespace MediaWiki\Extension\SiteLockdown;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;

class LockdownState {

    private const CACHE_TTL = 15;

    public static function isActive(): bool {
        return self::getState()['active'];
    }

    public static function getState(): array {
        $cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
        return $cache->getWithSetCallback(
            $cache->makeKey( 'sitelockdown', 'state' ),
            self::CACHE_TTL,
            static function () {
                return self::loadState();
            }
        );
    }

    private static function loadState(): array {
        $dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
        $row = $dbr->newSelectQueryBuilder()
            ->select( [ 'sld_active', 'sld_activated_by_actor', 'sld_activated_at', 'sld_reason' ] )
            ->from( 'sitelockdown_state' )
            ->where( [ 'sld_id' => 1 ] )
            ->caller( __METHOD__ )
            ->fetchRow();

        if ( !$row ) {
            return [ 'active' => false, 'activatedByActor' => null, 'activatedAt' => null, 'reason' => null ];
        }

        return [
            'active' => (bool)$row->sld_active,
            'activatedByActor' => $row->sld_activated_by_actor,
            'activatedAt' => $row->sld_activated_at,
            'reason' => $row->sld_reason,
        ];
    }

    public static function activate( UserIdentity $performer, string $reason ): void {
        self::write( true, $performer, $reason );
    }

    public static function deactivate( UserIdentity $performer ): void {
        self::write( false, $performer, '' );
    }

    private static function write( bool $active, UserIdentity $performer, string $reason ): void {
        $services = MediaWikiServices::getInstance();
        $dbw = $services->getConnectionProvider()->getPrimaryDatabase();
        $actorId = $services->getActorNormalization()->acquireActorId( $performer, $dbw );

        $dbw->upsert(
            'sitelockdown_state',
            [
                'sld_id' => 1,
                'sld_active' => $active ? 1 : 0,
                'sld_activated_by_actor' => $actorId,
                'sld_activated_at' => $dbw->timestamp(),
                'sld_reason' => $reason,
            ],
            [ 'sld_id' ],
            [
                'sld_active' => $active ? 1 : 0,
                'sld_activated_by_actor' => $actorId,
                'sld_activated_at' => $dbw->timestamp(),
                'sld_reason' => $reason,
            ],
            __METHOD__
        );

        $cache = $services->getMainWANObjectCache();
        $cache->delete( $cache->makeKey( 'sitelockdown', 'state' ) );
    }
}
