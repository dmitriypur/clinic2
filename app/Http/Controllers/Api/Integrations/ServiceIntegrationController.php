<?php

namespace App\Http\Controllers\Api\Integrations;

use App\Exceptions\ServiceIntegrationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Integrations\ServiceApplyRequest;
use App\Http\Requests\Api\Integrations\ServiceSearchRequest;
use App\Services\ServiceIntegrationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceIntegrationController extends Controller
{
    public function __construct(
        protected ServiceIntegrationService $serviceIntegrationService,
    ) {}

    public function tree(Request $request): JsonResponse
    {
        return response()->json(
            $this->serviceIntegrationService->getTree(
                $request->boolean('include_inactive', true)
            )
        );
    }

    public function parents(Request $request): JsonResponse
    {
        return response()->json(
            $this->serviceIntegrationService->getParents(
                $request->boolean('include_inactive', true)
            )
        );
    }

    public function search(ServiceSearchRequest $request): JsonResponse
    {
        return response()->json([
            'query' => $request->string('q')->toString(),
            'results' => $this->serviceIntegrationService->search(
                $request->string('q')->toString(),
                (int) $request->integer('limit', 20)
            ),
        ]);
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            return response()->json([
                'service' => $this->serviceIntegrationService->getService($uuid),
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Услуга не найдена.',
            ], 404);
        }
    }

    public function children(string $uuid): JsonResponse
    {
        try {
            return response()->json(
                $this->serviceIntegrationService->getChildren($uuid)
            );
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Услуга не найдена.',
            ], 404);
        }
    }

    public function childrenByTitle(ServiceSearchRequest $request): JsonResponse
    {
        try {
            return response()->json(
                $this->serviceIntegrationService->getChildrenByParentTitle(
                    $request->string('q')->toString()
                )
            );
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'message' => 'Услуга не найдена.',
            ], 404);
        }
    }

    public function preview(ServiceApplyRequest $request): JsonResponse
    {
        try {
            return response()->json(
                $this->serviceIntegrationService->applyOperations(
                    $request->input('operations', []),
                    true,
                    $request->boolean('compact', true)
                )
            );
        } catch (ServiceIntegrationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'context' => $exception->context(),
            ], $exception->status());
        }
    }

    public function apply(ServiceApplyRequest $request): JsonResponse
    {
        try {
            return response()->json(
                $this->serviceIntegrationService->applyOperations(
                    $request->input('operations', []),
                    $request->boolean('dry_run'),
                    $request->boolean('compact', false)
                )
            );
        } catch (ServiceIntegrationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'context' => $exception->context(),
            ], $exception->status());
        }
    }
}
