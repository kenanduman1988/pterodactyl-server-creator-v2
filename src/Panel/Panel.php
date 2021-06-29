<?php

namespace BangerGames\ServerCreator\Panel;

use BangerGames\SteamGameServerLoginToken\TokenService;
use HCGCloud\Pterodactyl\Exceptions\ValidationException;
use HCGCloud\Pterodactyl\Resources\Allocation;

class Panel
{
    public \HCGCloud\Pterodactyl\Pterodactyl $panel;

    public TokenService $tokenService;

    public const DEFAULT_NEST_ID = 2;

    public const DEFAULT_EGG_ID = 7;

    public const DEFAULT_USER_ID = 1;


    public function __construct()
    {
        $this->panel = new \HCGCloud\Pterodactyl\Pterodactyl(env('PTERODACTYL_BASE_URI'), env('PTERODACTYL_API_KEY'));
        $this->tokenService = new TokenService();
    }

    /**
     * @param Allocation $resource
     */
    private function mergePagination($resource, $id)
    {
        $page1 = $this->panel->node_allocations->paginate($id, 1);
        $data = $page1->all();

        $totalPages = $page1->meta()['pagination']['total_pages'];
        if ($totalPages > 1) {
            for ($page=2; $page<=$totalPages;$page++) {
                $pageData = $this->panel->node_allocations->paginate($id, $page)->all();
                $data = array_merge($data, $pageData);
            }
        }
        return $data;
    }

    public function listAllocations($nodeId)
    {
        return $this->mergePagination($this->panel->node_allocations, $nodeId);
    }

    public function getAvailableAllocation($nodeId)
    {
        /** @var Allocation[] $allocations */
        $allocations = $this->listAllocations($nodeId);
        foreach ($allocations as $allocation) {
            if (!$allocation->assigned) {
                return $allocation;
            }
        }
    }

    public function getSteamToken()
    {
        $createToken = $this->tokenService->createAccount(730, $name);
        return $createToken->response->login_token ?? null;
    }

    public function createServer(int $nodeId, array $extraData)
    {
        try {
            $node = $this->panel->nodes->get($nodeId);
            $user = $this->panel->users->get(self::DEFAULT_USER_ID);
            $egg = $this->panel->nest_eggs->get(self::DEFAULT_NEST_ID, self::DEFAULT_EGG_ID);
            $allocation = $this->getAvailableAllocation($nodeId);
            $name = sprintf('%s-%s', $node->name, $allocation->port);
            $createToken = $this->tokenService->createAccount(730, $name);
            $steamAcc = $createToken->response->login_token;
            $data = [
                "name" => $name,
                "user" => $user->id,
                "egg" => $egg->id,
                "docker_image" => $egg->docker_image,
                "skip_scripts" => true,
                "environment" => [
                    "SRCDS_MAP"=> "de_dust2",
                    "STEAM_ACC"=> $steamAcc,
                    "SRCDS_APPID"=> "740",
                    "GOTV_PORT"=> 28 . substr($allocation->port, 2),
                    "STARTUP" => $egg->startup
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
                "allocation"=> [
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
            return $this->panel->servers->create($data);

        } catch (ValidationException $exception) {
            return null;
        }

    }
}
