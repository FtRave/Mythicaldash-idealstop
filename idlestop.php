<?php

namespace MythicalDash\Addons\idlestop;

use MythicalDash\Plugins\Events\Events\AppEvent;
use MythicalDash\Plugins\Events\Events\AuthEvent;
use MythicalDash\Plugins\MythicalDashPlugin;

class idlestop implements MythicalDashPlugin
{
	/**
	 * @inheritDoc
	 */
	public static function processEvents(\MythicalDash\Plugins\PluginEvents $event): void
	{

	}

	/**
	 * @inheritDoc
	 */
	public static function pluginInstall(): void
	{
	}

	/**
	 * @inheritDoc
	 */
	public static function pluginUninstall(): void
	{
	}
}