<?php

namespace BangerGames\ServerCreator\Panel;

use App\Jobs\PanelServerPowerJob;
use BangerGames\ServerCreator\Exceptions\AllocationNotFoundException;
use BangerGames\ServerCreator\Exceptions\NodeNotFoundException;
use BangerGames\ServerCreator\Models\PanelLocation;
use BangerGames\ServerCreator\Models\PanelNode;
use BangerGames\ServerCreator\Models\PanelServer;
use BangerGames\SteamGameServerLoginToken\TokenService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use HCGCloud\Pterodactyl\Exceptions\NotFoundException;
use HCGCloud\Pterodactyl\Exceptions\ValidationException;
use HCGCloud\Pterodactyl\Managers\LocationManager;
use HCGCloud\Pterodactyl\Managers\NodeManager;
use HCGCloud\Pterodactyl\Managers\ServerManager;
use HCGCloud\Pterodactyl\Resources\Allocation;
use HCGCloud\Pterodactyl\Resources\Location;
use HCGCloud\Pterodactyl\Resources\Node;
use HCGCloud\Pterodactyl\Resources\Resource;
use HCGCloud\Pterodactyl\Resources\Server;

/**
 * Class Panel
 * @package BangerGames\ServerCreator\Panel
 */
class Panel
{
    public const DEFAULT_NEST_ID = 5;
    public const DEFAULT_EGG_ID = 15;
    public const DEFAULT_USER_ID = 1;

    public TokenService $tokenService;
    public \HCGCloud\Pterodactyl\Pterodactyl $panel;
    private Client $httpClient;
    private int $ownerId;

    /**
     * Panel constructor.
     * @throws \HCGCloud\Pterodactyl\Exceptions\InvaildApiTypeException
     */
    public function __construct($isClient = false)
    {
        $this->isClient = $isClient;
        $this->setPanel($isClient);
        $this->tokenService = new TokenService();
        if(!$isClient)
           $this->ownerId = $this->setOwner();
        else
           $this->ownerId = 0;
    }

    public function setPanel($isClient = false)
    {
        $key = $isClient ?
            env('PTERODACTYL_CLIENT_API_KEY') :
            env('PTERODACTYL_API_KEY');

        $httpClient = new Client([
            'base_uri'    => env('PTERODACTYL_BASE_URI'),
            'http_errors' => false,
            'connect_timeout' => 10,
            'timeout' => 20,
            'debug' => app()->isLocal(),
            'headers'     => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->panel = new \HCGCloud\Pterodactyl\Pterodactyl(
            env('PTERODACTYL_BASE_URI'),
            $key,
            $isClient ? 'client' : 'application',
            $httpClient
        );
    }

    /**
     * @param NodeManager|NodeAllocationManager|ServerManager|LocationManager $resource
     * @param int|null $nodeId
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
     * @param $name
     * @return string|null
     */
    public function getSteamToken($name): ?string
    {
        $createToken = $this->tokenService->createAccount(730, $name);
        return $createToken->response->login_token ?? null;
    }

    public function syncLocations()
    {
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

    private function setOwner()
    {
        try {
            $users = $this->mergePagination($this->panel->users);
            $owner = array_first($users, function ($user) {
                return $user->username === 'csgopanel-' . env('APP_ENV');
            });
            return $owner->id;
        }
        catch ( Exception $e) {
            return 0;
        }
    }

    private function getSteamIdFromToken($loginToken, $steamServers)
    {
        foreach ($steamServers as $server){
            if($loginToken === $server->login_token)
                return $server->steamid;
        }
        return null;
    }

    public function syncServers()
    {
        //get servers from csgo
        $servers = $this->mergePagination($this->panel->servers);

        //get all steam server data from account (determined by STEAM_API_KEY in env)
        $steamServers = null;
        $response = $this->tokenService->getAccountList();
        try
        {
            $steamServers = $response->response->servers;
        }
        catch ( Exception $e) {
        }

        //filter our by user at this moment
        foreach ($servers as $key => $server){
            if($server->user !== $this->ownerId){
                unset($servers[$key]);
            }
        }

        /** @var Server $location */
        foreach ($servers as $server) {
            $panelNode = PanelNode::firstWhere('external_id', $server->node);

            $steamLoginToken = null;
            $steamId = null;
            $rconPass = null;
            $ip = null;
            $port = null;
            try {
                $steamLoginToken = $server->container['environment']['STEAM_ACC'];
                $rconPass = $server->container['environment']['RCON_PASSWORD'];
                $ip = $server->allocationObject['ip_alias'];
                $port = $server->allocationObject['port'];
                if(!is_null($steamServers))
                    $steamId = $this->getSteamIdFromToken($steamLoginToken, $steamServers);
            }
            catch ( Exception $e) {
            }

            PanelServer::updateOrCreate([
                'server_id' => $server->id
            ], [
                'server_id' => $server->id,
                'panel_node_id' => $panelNode->id,
                'name' => $server->name,
                'uuid' => $server->uuid,
                'data' => $server->all(),
                'steam_login_token' => $steamLoginToken,
                'steam_id_64' => $steamId,
                'rcon_password' => $rconPass,
                'ip' => $ip,
                'port' => $port,
            ]);
        }
    }

    public function deleteNotExistsServers()
    {
        $servers = $this->mergePagination($this->panel->servers);
        $panelServers = PanelServer::all();
        foreach ($panelServers as $panelServer) {
            if (!$this->isServerExistsInPanel($servers, $panelServer->server_id)) {
                PanelServer::withoutEvents(function () use ($panelServer) {
                    $panelServer->delete();
                });
            }
        }
    }

    private function isServerExistsInPanel(array $servers, $serverId): bool
    {
        if($this->ownerId === 0)
            return false;

        /** @var Server $server */
        foreach ($servers as $server) {
            if (($server->id == $serverId) && ($server->user === $this->ownerId)) {
                return true;
            }
        }

        return false;
    }

    public function deleteUnusedNodes()
    {
        $nodes = PanelNode::all();
        $panelServerNodes = PanelServer::select('panel_node_id')->distinct()->get();
        foreach ($nodes as $node) {
            if (!$this->isNodeUsed($panelServerNodes, $node->id)) {
                $node->delete();
            }
        }
    }

    private function isNodeUsed($panelServerNodes, $nodeId): bool
    {
        foreach ($panelServerNodes as $node) {
            if ($node->panel_node_id == $nodeId) {
                return true;
            }
        }

        return false;
    }

    public function deleteUnusedLocations()
    {
        $locations = PanelLocation::all();
        $panelNodeLocations = PanelNode::select('panel_location_id')->distinct()->get();
        foreach ($locations as $location) {
            if (!$this->isLocationUsed($panelNodeLocations, $location->id)) {
                $location->delete();
            }
        }
    }

    private function isLocationUsed($panelNodeLocations, $locationId): bool
    {
        foreach ($panelNodeLocations as $location) {
            if ($location->panel_location_id == $locationId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public function deleteServer($serverId, $steamId): void
    {
        if (!$serverId) {
            return;
        }
        try {
            $check = $this->panel->servers->get($serverId);
            if ($check) {
                $this->panel->servers->forceDelete($serverId);
                if ($steamId) {
                    $delete = $this->tokenService->deleteAccount($steamId);
                }
            }
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws Exception
     */
    public function powerServer(PanelServer $panelServer, $signal, $skipWait = false): void
    {
        if ($panelServer->suspended) {
            throw new Exception("Powering server failed: panel server suspended");
            //return;
        }
        if (!$panelServer->server_id) {
            throw new Exception("Powering server failed: panel server_id empty");
            //return;
        }
        try {
            $this->setPanel();
            /** @var Server $check */
            $check = $this->panel->servers->get($panelServer->server_id);
            if ($check) {
                $this->setPanel(true);
                $power = $this->panel->servers->power($check->identifier, $signal);
                if (in_array($signal, ['restart', 'start']) && !$skipWait) {
                    $tries = 24;
                    do {
                        $tries--;
                        sleep(10);
                        /** @var Resource $resourceUsage */
                        $resourceUsage = $this->getResourceUsage($panelServer);
                        if (null === $resourceUsage) {
                            //error state
                            continue;
                        }
                        if ($resourceUsage->current_state === 'offline') {
                            //$this->suspendServer($panelServer);
                            //error state
                            continue;
                        }
                        if ($resourceUsage->current_state === 'running') {
                            return; //we succeeded
                        }
                    } while ($tries>0);
                    throw new Exception("Server failed to execute command: $signal");
                    return;
                }
            }
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws Exception
     */
    public function updateEnvironment(PanelServer $panelServer, $key, $value): void
    {
        try {
            $this->setPanel();
            $server = $this->panel->servers->get($panelServer->server_id);
            $this->setPanel(true);
            $response = $this->panel->http->put("servers/{$server->identifier}/startup/variable", [
                'key' => $key,
                'value' => $value,
            ]);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws Exception
     */
    public function sendConsoleCmd(PanelServer $panelServer, $command): void
    {
        try {
            $this->setPanel();
            $server = $this->panel->servers->get($panelServer->server_id);
            $this->setPanel(true);
            $response = $this->panel->servers->command($server->identifier, $command);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws Exception
     */
    public function suspendServer(PanelServer $panelServer): void
    {
        if (!$panelServer->server_id) {
            throw new Exception("Suspend server failed: panel server_id empty");
        }
        try {
            $this->setPanel();
            $this->panel->servers->suspend($panelServer->server_id);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    public function getResourceUsage(PanelServer $panelServer)
    {
        if (!$panelServer->server_id) {
            return null;
        }
        try {
            $this->setPanel();
            /** @var Server $server */
            $server = $this->panel->servers->get($panelServer->server_id);
            if ($server) {
                $this->setPanel(true);
                return $this->panel->servers->resources($server->identifier);
            }
        } catch (Exception $e) {
            if(strpos($e->getMessage(),"This server has not yet completed its installation process") !== false)
                return 'installing';
            else
                return null;
        }

        return null;
    }

    public function syncNodes()
    {
        $servers = $this->mergePagination($this->panel->servers);
        $nodes = $this->mergePagination($this->panel->nodes);

        foreach ($nodes as $node) {
            //calc server count on node:
            $cntServers = 0;
            foreach ($servers as $server){
                if($server->node === $node->id)
                    $cntServers++;
            }
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
                'server_count' => $cntServers,
            ]);
        }
    }

    /**
     * @param int $nodeId
     * @param array $extraData
     * @return Server|null
     * @throws NodeNotFoundException
     * @throws AllocationNotFoundException
     * @throws Exception
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

            $user = $this->panel->users->get($this->ownerId);
            $egg = $this->panel->nest_eggs->get(self::DEFAULT_NEST_ID, self::DEFAULT_EGG_ID);

            $name = sprintf('%s-%s', $node->name, $allocation->port);
            $createToken = $this->tokenService->createAccount(730, $name);
            $steamAcc = $createToken->response->login_token;
            $steamid = $createToken->response->steamid;
            $rconPassword = $this->generateRconPassword();
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
                    "GAME_MODE" => "2",
                    "GAME_TYPE" => "0",
                    "RCON_PASSWORD" => $rconPassword,
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
            $newServer = $this->panel->servers->create($data);
            $panelNode = PanelNode::firstWhere('external_id', $newServer->node);

            $panelServer->update([
                'steam_id' => $steamid,
                'rcon_password' => $rconPassword,
                'server_id' => $newServer->id,
                'name' => $newServer->name,
                'panel_node_id' => $panelNode->id,
                'uuid' => $newServer->uuid,
                'steam_login_token' => $steamAcc,
                'steam_id_64' => $steamid,
                'ip' => $allocation->alias,
                'port' => $allocation->port,
                'data' => $newServer->all()
            ]);

            return $newServer;

        } catch (ValidationException $e) {
            $error_msg = implode(";",$e->errors());
            throw new Exception($error_msg);
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

    private function isSteamIdBusy($steamId)
    {
        $panelServer = PanelServer::firstWhere('steam_id', $steamId);
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

    public function generateRconPassword($length = 16) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
}
