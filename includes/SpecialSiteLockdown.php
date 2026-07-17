<?php

namespace MediaWiki\Extension\SiteLockdown;

use ManualLogEntry;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use OOUI\ButtonInputWidget;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;

class SpecialSiteLockdown extends SpecialPage {

    public function __construct() {
        parent::__construct( 'SiteLockdown' );
    }

    public function getRestriction(): string {
        return 'sitelockdown';
    }

    public function doesWrites(): bool {
        return true;
    }

    public function execute( $par ): void {
        $this->setHeaders();
        $this->checkPermissions();
        $this->getOutput()->enableOOUI();
        $this->getOutput()->addModuleStyles( [ 'ext.sitelockdown.styles' ] );

        if ( $this->getRequest()->wasPosted() ) {
            $this->handleSubmit();
            return;
        }

        $this->showStatus();
    }

    private function handleSubmit(): void {
        $request = $this->getRequest();
        $out = $this->getOutput();

        if ( !$this->getUser()->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
            $out->addWikiMsg( 'sitelockdown-error-token' );
            $this->showStatus();
            return;
        }

        $performer = $this->getUser();

        if ( $request->getCheck( 'wpActivate' ) ) {
            $reason = $request->getText( 'wpReason' );
            LockdownState::activate( $performer, $reason );

            $logEntry = new ManualLogEntry( 'sitelockdown', 'activate' );
            $logEntry->setPerformer( $performer );
            $logEntry->setTarget( $this->getPageTitle() );
            $logEntry->setComment( $reason );
            $logEntry->insert();

            $out->addWikiMsg( 'sitelockdown-activated' );
        } elseif ( $request->getCheck( 'wpDeactivate' ) ) {
            LockdownState::deactivate( $performer );

            $logEntry = new ManualLogEntry( 'sitelockdown', 'deactivate' );
            $logEntry->setPerformer( $performer );
            $logEntry->setTarget( $this->getPageTitle() );
            $logEntry->insert();

            $out->addWikiMsg( 'sitelockdown-deactivated' );
        }

        $this->showStatus();
    }

    private function showStatus(): void {
        $out = $this->getOutput();
        $state = LockdownState::getState();
        $actionUrl = $this->getPageTitle()->getLocalURL();

        if ( $state['active'] ) {
            $activatedBy = $this->getActorName( $state['activatedByActor'] );
            $timestamp = $state['activatedAt']
                ? $this->getLanguage()->userTimeAndDate( $state['activatedAt'], $this->getUser() )
                : '';

            $out->addWikiMsg( 'sitelockdown-status-active', $activatedBy, $timestamp );
            if ( $state['reason'] !== null && $state['reason'] !== '' ) {
                $out->addWikiMsg( 'sitelockdown-status-reason', $state['reason'] );
            }

            $out->addHTML(
                Html::openElement( 'form', [ 'method' => 'post', 'action' => $actionUrl, 'class' => 'sitelockdown-form' ] ) .
                Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
                Html::rawElement( 'div', [ 'class' => 'sitelockdown-actions' ],
                    ( new ButtonInputWidget( [
                        'type' => 'submit',
                        'name' => 'wpDeactivate',
                        'label' => $this->msg( 'sitelockdown-deactivate-button' )->text(),
                        'flags' => [ 'primary', 'progressive' ],
                    ] ) )->toString()
                ) .
                Html::closeElement( 'form' )
            );
            return;
        }

        $out->addWikiMsg( 'sitelockdown-status-inactive' );

        $reasonField = new FieldLayout(
            new TextInputWidget( [
                'name' => 'wpReason',
                'id' => 'wpReason',
            ] ),
            [
                'label' => $this->msg( 'sitelockdown-form-reason' )->text(),
                'align' => 'top',
            ]
        );

        $out->addHTML(
            Html::openElement( 'form', [ 'method' => 'post', 'action' => $actionUrl, 'class' => 'sitelockdown-form' ] ) .
            Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
            $reasonField->toString() .
            Html::rawElement( 'div', [ 'class' => 'sitelockdown-actions' ],
                ( new ButtonInputWidget( [
                    'type' => 'submit',
                    'name' => 'wpActivate',
                    'label' => $this->msg( 'sitelockdown-activate-button' )->text(),
                    'flags' => [ 'primary', 'destructive' ],
                ] ) )->toString()
            ) .
            Html::closeElement( 'form' )
        );
    }

    private function getActorName( $actorId ): string {
        if ( !$actorId ) {
            return '';
        }
        $dbr = MediaWikiServices::getInstance()->getConnectionProvider()->getReplicaDatabase();
        $actor = MediaWikiServices::getInstance()->getActorStore()->getActorById( (int)$actorId, $dbr );
        return $actor ? $actor->getName() : '';
    }

    protected function getGroupName(): string {
        return 'users';
    }
}
