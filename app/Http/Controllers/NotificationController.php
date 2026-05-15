<?php

namespace App\Http\Controllers;

use App\Http\Requests\BatchCreateNotificationRequest;
use App\Http\Requests\CreateNotificationRequest;
use App\Http\Requests\ListNotificationsRequest;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    protected $service;

    public function __construct(
        NotificationService $service
    ) {
        $this->service = $service;
    }

    public function store(CreateNotificationRequest $request): JsonResponse
    {
        $notification = $this->service->createNotification($request->validated());

        return response()->json([
            'success' => true,
            'data' => $notification,
        ], 201);
    }

    public function batchStore(BatchCreateNotificationRequest $request): JsonResponse
    {
        $result = $this->service->createBatch($request->validated()['notifications']);

        return response()->json([
            'success' => true,
            'data' => $result,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $notification = $this->service->getNotification($id);

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    public function showBatch(string $batchId): JsonResponse
    {
        $notifications = $this->service->getByBatchId($batchId);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'total' => $notifications->count(),
        ]);
    }

    public function cancel(string $id): JsonResponse
    {
        $cancelled = $this->service->cancelNotification($id);

        return response()->json([
            'success' => $cancelled,
            'message' => $cancelled ? 'Notification cancelled successfully' : 'Notification cannot be cancelled',
        ], $cancelled ? 200 : 400);
    }

    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $query = Notification::query();

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('channel')) {
            $query->byChannel($request->channel);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        $query->orderBy('created_at', 'desc');

        $notifications = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
        ]);
    }
}
