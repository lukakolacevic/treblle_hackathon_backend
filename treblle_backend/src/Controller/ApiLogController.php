<?php

namespace App\Controller;

use App\Entity\ApiLog;
use App\Enum\HttpMethod;
use App\Repository\ApiLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
class ApiLogController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApiLogRepository $apiLogRepository
    ) {
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'message' => 'API is running'
        ]);
    }

    #[Route('/logs', name: 'logs_list', methods: ['GET'])]
    public function listLogs(Request $request): JsonResponse
    {
        // Pagination
        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);
        
        // Sorting
        $sortBy = $request->query->get('sort_by', 'created_at'); // 'created_at' or 'response_time'
        $sortOrder = strtoupper($request->query->get('sort_order', 'DESC')); // 'ASC' or 'DESC'
        
        // Validate sort parameters
        $allowedSortFields = ['created_at' => 'createdAt', 'response_time' => 'responseTime'];
        $sortField = $allowedSortFields[$sortBy] ?? 'createdAt';
        $sortOrder = in_array($sortOrder, ['ASC', 'DESC']) ? $sortOrder : 'DESC';
        
        // Filters
        $filterMethod = $request->query->get('filter_method'); // e.g., 'GET', 'POST'
        $filterResponse = $request->query->get('filter_response'); // e.g., '200', '404'
        $filterTimeFrom = $request->query->get('filter_time_from'); // timestamp or date
        $filterTimeTo = $request->query->get('filter_time_to'); // timestamp or date
        $filterResponseTimeMin = $request->query->get('filter_response_time_min'); // milliseconds
        $filterResponseTimeMax = $request->query->get('filter_response_time_max'); // milliseconds
        
        // Search
        $search = $request->query->get('search'); // search in path
        
        // Build query
        $qb = $this->apiLogRepository->createQueryBuilder('a');
        
        // Apply filters
        if ($filterMethod) {
            $httpMethod = HttpMethod::tryFrom(strtoupper($filterMethod));
            if ($httpMethod) {
                $qb->andWhere('a.method = :method')
                   ->setParameter('method', $httpMethod);
            }
        }
        
        if ($filterResponse) {
            $qb->andWhere('a.response = :response')
               ->setParameter('response', (int)$filterResponse);
        }
        
        if ($filterTimeFrom) {
            try {
                $qb->andWhere('a.createdAt >= :timeFrom')
                   ->setParameter('timeFrom', new \DateTimeImmutable($filterTimeFrom));
            } catch (\Exception $e) {
                // Invalid date format, skip filter
            }
        }
        
        if ($filterTimeTo) {
            try {
                $qb->andWhere('a.createdAt <= :timeTo')
                   ->setParameter('timeTo', new \DateTimeImmutable($filterTimeTo));
            } catch (\Exception $e) {
                // Invalid date format, skip filter
            }
        }
        
        if ($filterResponseTimeMin !== null && $filterResponseTimeMin !== '') {
            $qb->andWhere('a.responseTime >= :responseTimeMin')
               ->setParameter('responseTimeMin', (int)$filterResponseTimeMin);
        }
        
        if ($filterResponseTimeMax !== null && $filterResponseTimeMax !== '') {
            $qb->andWhere('a.responseTime <= :responseTimeMax')
               ->setParameter('responseTimeMax', (int)$filterResponseTimeMax);
        }
        
        // Apply search
        if ($search) {
            $qb->andWhere('a.path LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Count total matching records (before pagination)
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(a.id)')
                               ->getQuery()
                               ->getSingleScalarResult();
        
        // Apply sorting
        $qb->orderBy('a.' . $sortField, $sortOrder);
        
        // Apply pagination
        $qb->setMaxResults($limit)
           ->setFirstResult($offset);
        
        $logs = $qb->getQuery()->getResult();
        
        // Format data
        $data = array_map(function (ApiLog $log) {
            return [
                'id' => $log->getId(),
                'method' => $log->getMethod()->value,
                'response' => $log->getResponse(),
                'path' => $log->getPath(),
                'response_time' => $log->getResponseTime(),
                'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $logs);
        
        return $this->json([
            'data' => $data,
            'pagination' => [
                'count' => count($data),
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + count($data)) < $total
            ],
            'filters_applied' => array_filter([
                'method' => $filterMethod,
                'response' => $filterResponse,
                'time_from' => $filterTimeFrom,
                'time_to' => $filterTimeTo,
                'response_time_min' => $filterResponseTimeMin,
                'response_time_max' => $filterResponseTimeMax,
                'search' => $search,
            ], fn($value) => $value !== null && $value !== ''),
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    #[Route('/logs/{id}', name: 'logs_get', methods: ['GET'])]
    public function getLog(int $id): JsonResponse
    {
        $log = $this->apiLogRepository->find($id);

        if (!$log) {
            return $this->json([
                'error' => 'Log not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $log->getId(),
            'method' => $log->getMethod()->value,
            'path' => $log->getPath(),
            'response' => $log->getResponse(),
            'response_time' => $log->getResponseTime(),
            'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    #[Route('/logs', name: 'logs_create', methods: ['POST'])]
    public function createLog(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'error' => 'Invalid JSON'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        if (!isset($data['method']) || !isset($data['path']) || !isset($data['response']) || !isset($data['response_time'])) {
            return $this->json([
                'error' => 'Missing required fields: method, path, response, response_time'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Validate HTTP method
        $httpMethod = HttpMethod::tryFrom(strtoupper($data['method']));
        if (!$httpMethod) {
            return $this->json([
                'error' => 'Invalid HTTP method',
                'allowed_methods' => HttpMethod::values()
            ], Response::HTTP_BAD_REQUEST);
        }

        $log = new ApiLog();
        $log->setMethod($httpMethod);
        $log->setPath($data['path']);
        $log->setResponse((int) $data['response']);
        $log->setResponseTime((int) $data['response_time']);
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Log created successfully',
            'id' => $log->getId(),
            'data' => [
                'id' => $log->getId(),
                'method' => $log->getMethod()->value,
                'path' => $log->getPath(),
                'response' => $log->getResponse(),
                'response_time' => $log->getResponseTime(),
                'created_at' => $log->getCreatedAt()->format('Y-m-d H:i:s'),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/logs/{id}', name: 'logs_delete', methods: ['DELETE'])]
    public function deleteLog(int $id): JsonResponse
    {
        $log = $this->apiLogRepository->find($id);

        if (!$log) {
            return $this->json([
                'error' => 'Log not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($log);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Log deleted successfully'
        ]);
    }

    #[Route('/test', name: 'test', methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])]
    public function test(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        // Simulate some processing
        usleep(rand(10000, 100000)); // 10-100ms
        
        $endTime = microtime(true);
        $responseTime = (int) (($endTime - $startTime) * 1000);

        // Log this request
        $log = new ApiLog();
        $log->setMethod(HttpMethod::from($request->getMethod()));
        $log->setPath($request->getPathInfo());
        $log->setResponse(200);
        $log->setResponseTime($responseTime);
        $log->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Test endpoint',
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'response_time_ms' => $responseTime,
            'log_id' => $log->getId(),
            'timestamp' => $log->getCreatedAt()->format('Y-m-d H:i:s')
        ]);
    }
}

