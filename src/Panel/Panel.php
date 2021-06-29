<?php

namespace BangerGames\ServerCreator\Panel;

use BangerGames\ServerCreator\Exceptions\AllocationNotFoundException;
use BangerGames\ServerCreator\Exceptions\NodeNotFoundException;
use BangerGames\SteamGameServerLoginToken\TokenService;
use HCGCloud\Pterodactyl\Exceptions\NotFoundException;
use HCGCloud\Pterodactyl\Exceptions\ValidationException;
use HCGCloud\Pterodactyl\Resources\Allocation;
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
    public function __construct()
    {
        $this->panel = new \HCGCloud\Pterodactyl\Pterodactyl(env('PTERODACTYL_BASE_URI'), env('PTERODACTYL_API_KEY'));
        $this->tokenService = new TokenService();
    }

    /**
     * @param Allocation|Node|Server $resource
     * @param int $nodeId
     * @return array
     */
    private function mergePagination($resource, int $nodeId): array
    {
        $page1 = $this->panel->node_allocations->paginate($nodeId, 1);
        $data = $page1->all();

        $totalPages = $page1->meta()['pagination']['total_pages'];
        if ($totalPages > 1) {
            for ($page=2; $page<=$totalPages;$page++) {
                $pageData = $this->panel->node_allocations->paginate($nodeId, $page)->all();
                $data = array_merge($data, $pageData);
            }
        }
        return $data;
    }

    /**
     * @param int $nodeId
     * @return array
     */
    public function listAllocations(int $nodeId): array
    {
        return $this->mergePagination($this->panel->node_allocations, $nodeId);
    }

    /**
     * @param $nodeId
     * @return Allocation|null
     */
    public function getAvailableAllocation($nodeId): ?Allocation
    {
        /** @var Allocation[] $allocations */
        $allocations = $this->listAllocations($nodeId);
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
            $user = $this->panel->users->get(self::DEFAULT_USER_ID);
            $egg = $this->panel->nest_eggs->get(self::DEFAULT_NEST_ID, self::DEFAULT_EGG_ID);

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
