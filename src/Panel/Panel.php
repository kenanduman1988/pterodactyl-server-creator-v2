<?php

namespace BangerGames\ServerCreator\Panel;

use BangerGames\ServerCreator\Exceptions\AllocationNotFoundException;
use BangerGames\ServerCreator\Exceptions\NodeNotFoundException;
use BangerGames\ServerCreator\Models\PanelLocation;
use BangerGames\ServerCreator\Models\PanelNode;
use BangerGames\ServerCreator\Models\PanelServer;
use BangerGames\SteamGameServerLoginToken\TokenService;
use HCGCloud\Pterodactyl\Exceptions\NotFoundException;
use HCGCloud\Pterodactyl\Exceptions\ValidationException;
use HCGCloud\Pterodactyl\Managers\LocationManager;
use HCGCloud\Pterodactyl\Managers\NodeManager;
use HCGCloud\Pterodactyl\Managers\ServerManager;
use HCGCloud\Pterodactyl\Resources\Allocation;
use HCGCloud\Pterodactyl\Resources\Location;
use HCGCloud\Pterodactyl\Resources\Node;
use HCGCloud\Pterodactyl\Resources\Server;

/**
 * Class Panel
 * @package BangerGames\ServerCreator\Panel
 */
class Panel
{
    public TokenService $tokenService;

    public const DEFAULT_NEST_ID = 2;

    public const DEFAULT_EGG_ID = 7;

    public const DEFAULT_USER_ID = 1;

    public \HCGCloud\Pterodactyl\Pterodactyl $panel;

    /**
     * Panel constructor.
     * @throws \HCGCloud\Pterodactyl\Exceptions\InvaildApiTypeException
     */
    public function __construct($isClient = false)
    {
        $this->setPanel($isClient);
        $this->tokenService = new TokenService();
    }

    public function setPanel($isClient = false)
    {
        $key = $isClient ?
            env('PTERODACTYL_CLIENT_API_KEY') :
            env('PTERODACTYL_API_KEY');

        $this->panel = new \HCGCloud\Pterodactyl\Pterodactyl(
            env('PTERODACTYL_BASE_URI'),
            $key,
            $isClient ? 'client' : 'application'
        );
    }

    /**
     * @param NodeManager|NodeAllocationManager|ServerManager|LocationManager $resource
     * @param int $nodeId
     * @return array
     */
    private function mergePagination($resource, int $nodeId = null): array
    {
        $page1 = $nodeId ? $resource->paginate($nodeId, 1) : $resource->paginate(1);
        $data = $page1->all();

        $totalPages = $page1->meta()['pagination']['total_pages'];
        if ($totalPages > 1) {
            for ($page = 2; $page <= $totalPages; $page++) {
                $pageData = $nodeId ? $resource->paginate($nodeId, $page)->all() : $resource->paginate($page)->all();
                $data = array_merge($data, $pageData);
            }
        }
        return $data;
    }

    /**
     * @param $nodeId
     * @return Allocation|null
     */
    public function getAvailableAllocation($nodeId): ?Allocation
    {
        /** @var Allocation[] $allocations */
        $allocations = $this->mergePagination($this->panel->node_allocations, $nodeId);
        foreach ($allocations as $allocation) {
            if (!$allocation->assigned) {
                return $allocation;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getSteamToken(): ?string
    {
        $createToken = $this->tokenService->createAccount(730, $name);
        return $createToken->response->login_token ?? null;
    }

    public function syncLocations()
    {
        PanelLocation::truncate();
        $locations = $this->mergePagination($this->panel->locations);
        /** @var Location $location */
        foreach ($locations as $location) {
            PanelLocation::updateOrCreate([
                'external_id' => $location->id
            ], [
                'external_id' => $location->id,
                'short_code' => $location->short,
                'description' => $location->long,
                'data' => $location->all(),
            ]);
        }
    }

    public function syncServers()
    {
        $servers = $this->mergePagination($this->panel->servers);
        /** @var Server $location */
        foreach ($servers as $server) {
            $panelNode = PanelNode::firstWhere('external_id', $server->node);
            PanelServer::updateOrCreate([
                'server_id' => $server->id
            ], [
                'server_id' => $server->id,
                'panel_node_id' => $panelNode->id,
                'name' => $server->name,
                'uuid' => $server->uuid,
                'status' => $server->status ?? '-',
                'suspended' => $server->suspended,
                'data' => $server->all(),
            ]);
        }
    }

    public function deleteServer(PanelServer $panelServer): void
    {
        if (!$panelServer->server_id) {
            return;
        }
        try {
            $check = $this->panel->servers->get($panelServer->server_id);
            if ($check) {
                $this->panel->servers->forceDelete($panelServer->server_id);
                if ($panelServer->steam_id) {
                    $delete = $this->tokenService->deleteAccount($panelServer->steam_id);
                }
            }
        } catch (\Exception $e) {
            // TODO: send slack notification
        }
    }

    public function powerServer(PanelServer $panelServer, $signal): void
    {
        if (!$panelServer->server_id) {
            return;
        }
        try {
            $this->setPanel();
            /** @var Server $check */
            $check = $this->panel->servers->get($panelServer->server_id);
            if ($check) {
                $this->setPanel(true);
                $power = $this->panel->servers->power($check->identifier, $signal);
            }
        } catch (\Exception $e) {
            // TODO: send slack notification
        }
    }


    public function syncNodes()
    {
        PanelNode::truncate();
        $nodes = $this->mergePagination($this->panel->nodes);
        /** @var Node $node */
        foreach ($nodes as $node) {
            $panelLocation = PanelLocation::firstWhere('external_id', $node->location_id);
            PanelNode::updateOrCreate([
                'external_id' => $node->id
            ], [
                'panel_location_id' => $panelLocation->id,
                'external_id' => $node->id,
                'external_location_id' => $node->location_id,
                'name' => $node->name,
                'uuid' => $node->uuid,
                'description' => $node->description,
                'data' => $node->all(),
            ]);
        }
    }

    /**
     * @param int $nodeId
     * @param array $extraData
     * @return Server|null
     */
    public function createServer(int $nodeId, array $extraData): ?Server
    {
        try {
            $node = $this->panel->nodes->get($nodeId);
        } catch (NotFoundException $e) {
            throw new NodeNotFoundException();
        }
        $allocation = $this->getAvailableAllocation($nodeId);
        if (!$allocation) {
            throw new AllocationNotFoundException();
        }
        try {
            $panelServer = PanelServer::create();

            $user = $this->panel->users->get(self::DEFAULT_USER_ID);
            $egg = $this->panel->nest_eggs->get(self::DEFAULT_NEST_ID, self::DEFAULT_EGG_ID);

            $name = sprintf('%s-%s', $node->name, $allocation->port);
            $createToken = $this->tokenService->createAccount(730, $name);
            $steamAcc = $createToken->response->login_token;
            $steamid = $createToken->response->steamid;
            $data = [
                "name" => $name,
                'external_id' => (string)$panelServer->id,
                "user" => $user->id,
                "egg" => $egg->id,
                "docker_image" => $egg->docker_image,
                "skip_scripts" => true,
                "environment" => [
                    "SRCDS_MAP" => "de_dust2",
                    "STEAM_ACC" => $steamAcc,
                    "SRCDS_APPID" => "740",
                    "GOTV_PORT" => 28 . substr($allocation->port, 2),
                    "STARTUP" => $egg->startup,
                ],
                "limits" => [
                    "memory" => 0,
                    "swap" => 0,
                    "disk" => 0,
                    "io" => 1000,
                    "cpu" => 0,
                    "backups" => 0
                ],
                "feature_limits" => [
                    "databases" => 0,
                    "backups" => 0
                ],
                "allocation" => [
                    "default" => $allocation->id
                ],
                "startup" => $egg->startup,
                "description" => sprintf('server with %s port on %s node', $allocation->port, $node->name),
                "start_on_completion" => false
            ];

            $data = array_merge($data, $extraData);
            if ($data['skip_scripts'] === false) {
                $data['name'] = $data['name'] . '-installed';
            }
//            dd(json_encode($data));
            $newServer = $this->panel->servers->create($data);
            $panelNode = PanelNode::firstWhere('external_id', $newServer->node);

            $panelServer->update([
                'steam_id' => $steamid,
                'server_id' => $newServer->id,
                'name' => $newServer->name,
                'panel_node_id' => $panelNode->id,
                'uuid' => $newServer->uuid,
                'status' => $newServer->status,
                'suspended' => $newServer->suspended,
                'data' => $newServer->all()
            ]);

            return $newServer;

        } catch (ValidationException $e) {
            dd($e->errors());
            return null;
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    private function isSteamIdBusy(int $steamId)
    {
        $panelServer = PanelServer::where('steam_id', $steamId)->first();
        return $panelServer ? true : false;
    }

    public function syncSteamTokens()
    {
        $tokenService = new TokenService();
        $accountList = $tokenService->getAccountList();
        $servers = $accountList->response->servers ?? [];
        foreach ($servers as $server) {
            $steamId = $server->steamid;
            if (!$this->isSteamIdBusy($steamId)) {
                $tokenService->deleteAccount($steamId);
            }
        }
    }
}
