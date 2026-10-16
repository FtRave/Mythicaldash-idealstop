<?php

namespace MythicalDash\Addons\idlestop\Cron;

use MythicalDash\Cli\App;
use MythicalDash\Cron\TimeTask;
use MythicalDash\Chat\Database;
use MythicalDash\Hooks\Pterodactyl\Admin\Servers;

require_once dirname(__DIR__) . "/MinecraftStatus.php";

class IdleStopCron implements TimeTask
{
    public function run(): void
    {
        $base = dirname(__DIR__);
        $config = json_decode((string) @file_get_contents($base . "/idle-config.json"), true);

        if (!is_array($config) || empty($config["enabled"])) {
            App::sendOutputWithNewLine("[idlestop] Disabled or invalid config.");
            return;
        }

        $idleMinutes = max(1, (int) ($config["idle_minutes"] ?? 10));
        $timeout = max(1, (int) ($config["minecraft_query_timeout_seconds"] ?? 3));
        $stateFile = $base . "/idle-state.json";
        $state = json_decode((string) @file_get_contents($stateFile), true);

        if (!is_array($state)) {
            $state = [];
        }

        try {
            $pdo = Database::getPdoConnection();
            $stmt = $pdo->query(
                "SELECT pterodactyl_id FROM mythicaldash_servers
                 WHERE deleted = 'false' AND pterodactyl_id IS NOT NULL"
            );
            $ids = array_unique(array_map("intval", $stmt->fetchAll(\PDO::FETCH_COLUMN)));
        } catch (\Throwable $e) {
            App::sendOutputWithNewLine("[idlestop] Cannot read managed servers; skipping this run.");
            return;
        }

        $now = time();

        foreach ($ids as $id) {
            if ($id < 1) {
                continue;
            }

            try {
                $server = Servers::getServerPterodactylDetails($id);
                $attributes = $server["attributes"] ?? [];
                $allocation = $attributes["allocation"] ?? null;
                $relations = $attributes["relationships"]["allocations"]["data"] ?? [];
                $primary = is_array($relations) ? ($relations[0]["attributes"] ?? []) : [];

                $host = is_array($allocation) ? ($allocation["ip"] ?? null) : null;
                $port = is_array($allocation) ? ($allocation["port"] ?? null) : null;

                if (!$host && is_array($primary)) {
                    $host = $primary["ip"] ?? null;
                    $port = $primary["port"] ?? $port;
                }

                if (!is_string($host) || $host === "" || !is_numeric($port)) {
                    unset($state[(string) $id]);
                    App::sendOutputWithNewLine("[idlestop] Server {$id}: allocation unknown; idle timer reset.");
                    continue;
                }

                $players = \MythicalDash\Addons\idlestop\MinecraftStatus::query(
                    $host,
                    (int) $port,
                    $timeout
                );

                if ($players === null) {
                    unset($state[(string) $id]);
                    App::sendOutputWithNewLine("[idlestop] Server {$id}: Minecraft status unknown; idle timer reset.");
                    continue;
                }

                if ($players > 0) {
                    unset($state[(string) $id]);
                    App::sendOutputWithNewLine("[idlestop] Server {$id}: {$players} player(s) online; idle timer reset.");
                    continue;
                }

                if (!isset($state[(string) $id]["idle_since"])) {
                    $state[(string) $id] = ["idle_since" => $now];
                    App::sendOutputWithNewLine("[idlestop] Server {$id}: zero players confirmed; idle timer started.");
                    continue;
                }

                $elapsed = max(0, $now - (int) $state[(string) $id]["idle_since"]);

                if ($elapsed >= $idleMinutes * 60) {
                    if (!empty($config["dry_run"])) {
                        App::sendOutputWithNewLine(
                            "[idlestop] DRY RUN: Server {$id} has had zero players for "
                            . (int) floor($elapsed / 60)
                            . " minute(s); would send stop signal. No stop request sent."
                        );
                        continue;
                    }

                    $identifier = $attributes["identifier"] ?? "";
                    $panelUrl = rtrim((string) ($config["panel_url"] ?? ""), "/");
                    $clientKey = (string) ($config["client_api_key"] ?? "");

                    if ($identifier === "" || $panelUrl === "" || $clientKey === "") {
                        App::sendOutputWithNewLine(
                            "[idlestop] Server {$id}: missing identifier or Client API configuration; not stopped."
                        );
                        continue;
                    }

                    $context = stream_context_create([
                        "http" => [
                            "method" => "POST",
                            "header" => "Authorization: Bearer " . $clientKey . "\r\n"
                                . "Accept: application/json\r\n"
                                . "Content-Type: application/json\r\n",
                            "content" => json_encode(["signal" => "stop"]),
                            "timeout" => 15,
                            "ignore_errors" => true
                        ]
                    ]);

                    $response = @file_get_contents(
                        $panelUrl . "/api/client/servers/" . rawurlencode((string) $identifier) . "/power",
                        false,
                        $context
                    );

                    $status = 0;
                    foreach (($http_response_header ?? []) as $header) {
                        if (preg_match("/^HTTP\\/\\S+\\s+(\\d{3})/", $header, $matches)) {
                            $status = (int) $matches[1];
                        }
                    }

                    if ($status >= 200 && $status < 300) {
                        unset($state[(string) $id]);
                        App::sendOutputWithNewLine(
                            "[idlestop] Stop signal accepted for managed server {$id} (HTTP {$status})."
                        );
                    } else {
                        App::sendOutputWithNewLine(
                            "[idlestop] Stop request failed for server {$id} (HTTP {$status}); will retry."
                        );
                    }
                } else {
                    App::sendOutputWithNewLine(
                        "[idlestop] Server {$id}: zero players; "
                        . (int) floor($elapsed / 60)
                        . "/{$idleMinutes} idle minutes."
                    );
                }
            } catch (\Throwable $e) {
                unset($state[(string) $id]);
                App::sendOutputWithNewLine("[idlestop] Server {$id}: check failed; skipping.");
            }
        }

        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json !== false) {
            $tmp = $stateFile . ".tmp";
            if (@file_put_contents($tmp, $json, LOCK_EX) !== false) {
                @rename($tmp, $stateFile);
            }
        }

        App::sendOutputWithNewLine("[idlestop] Idle check complete; no servers were stopped.");
    }
}
