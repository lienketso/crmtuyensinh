<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::with('lead')
            ->where('user_id', auth()->id());

        if ($request->filled('lead_id')) {
            $query->where('lead_id', (int) $request->lead_id);
        }

        if ($request->status === 'pending') {
            $query->where('status', 'pending');
        }

        if ($request->status === 'done') {
            $query->where('status', 'done');
        }

        if ($request->status === 'overdue') {
            $query->where('status', 'pending')
                  ->where('due_at', '<', now());
        }

        $perPage = (int) $request->get('per_page', 10);
        $perPage = min(max($perPage, 1), 100);

        return response()->json($query->orderBy('due_at')->paginate($perPage));
    }

    /**
     * Thống kê số lượng theo trạng thái (cho cards: đã hoàn thành, sắp hết hạn, chưa xử lý).
     */
    public function stats(Request $request)
    {
        $userId = auth()->id();
        $now = now();
        $soonEnd = $now->copy()->addDays(7);

        $base = Task::where('user_id', $userId);

        $done = (clone $base)->where('status', 'done')->count();

        $pending = (clone $base)->where('status', 'pending')->count();

        $soonDue = (clone $base)
            ->where('status', 'pending')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$now, $soonEnd])
            ->count();

        return response()->json([
            'done' => $done,
            'pending' => $pending,
            'soon_due' => $soonDue,
        ]);
    }

    /**
     * Số công việc trạng thái pending của user hiện tại (cho badge sidebar).
     */
    public function pendingCount()
    {
        $count = Task::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->count();

        return response()->json(['count' => $count]);
    }

    public function store(Request $request)
    {
        $task = Task::create([
            'lead_id' => $request->lead_id,
            'user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'due_at' => $request->due_at,
            'status' => 'pending',
        ]);

        return response()->json($task);
    }

    /**
     * Cập nhật thông tin task (chỉ chủ task).
     */
    public function update(Request $request, int $id)
    {
        $task = Task::where('id', $id)->where('user_id', auth()->id())->first();
        if (! $task) {
            return response()->json(['message' => 'Công việc không tồn tại.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'lead_id' => 'nullable|integer|exists:leads,id',
            'title' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:2000',
            'due_at' => 'nullable|date',
            'status' => 'nullable|in:pending,done,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task->update($validator->validated());

        return response()->json([
            'message' => 'Đã cập nhật công việc.',
            'task' => $task->load('lead'),
        ]);
    }

    public function markDone(int $id)
    {
        $task = Task::where('id', $id)->where('user_id', auth()->id())->first();
        if (!$task) {
            return response()->json(['message' => 'Công việc không tồn tại.'], 404);
        }
        if ($task->status === 'done') {
                return response()->json(['message' => 'Công việc đã hoàn thành.'], 400);
            }
        $task->update(['status' => 'done']);

        return response()->json(['message' => 'Công việc đã hoàn thành.']);
    }
}