<?php

declare(strict_types=1);

namespace OCA\ExternalFolderRefresh\Listener;

use OCA\ExternalFolderRefresh\AppInfo\Application;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadAdditionalScriptsListener implements IEventListener {
    public function handle(Event $event): void {
        if (!$event instanceof LoadAdditionalScriptsEvent) {
            return;
        }

        Util::addScript(Application::APP_ID, 'external-folder-refresh');
        Util::addStyle(Application::APP_ID, 'external-folder-refresh');
    }
}
