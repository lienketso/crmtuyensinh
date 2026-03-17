<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Task;
use App\Models\User;
use App\Services\LeadService;
use App\Services\LeadNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LeadController extends Controller
{
    public function __construct(
        private LeadService $leadService,
        private LeadNotificationService $leadNotificationService
    ) {}

    /**
     * Lấy danh sách leads
     */
    public function index(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'source' => $request->get('source'),
            'search' => $request->get('search'),
            'year_of_admission' => $request->get('year_of_admission') ?? date('Y'),
            'per_page' => $request->get('per_page', 15),
        ];

        $user = $request->user();

        // Multi-tenant theo trường
        if ($user && ! $user->isSuperAdmin()) {
            $filters['school_id'] = $user->school_id;
        } elseif ($request->has('school_id')) {
            $filters['school_id'] = $request->get('school_id');
        }

        // Nếu advisor: chỉ xem lead được gán cho chính mình
        if ($user && $user->isAdvisor()) {
            $filters['assigned_to'] = $user->id;
        }

        $leads = $this->leadService->getLeads($filters);

        return response()->json($leads);
    }

    public function getLead(Request $request)
    {
        $filters = [
            'status' => $request->get('status'),
            'source' => $request->get('source'),
            'search' => $request->get('search'),
            'year_of_admission' => $request->get('year_of_admission') ?? date('Y'),
            'per_page' => $request->get('per_page', 15),
        ];

        $user = $request->user();
        if ($user && ! $user->isSuperAdmin()) {
            $filters['school_id'] = $user->school_id;
        } elseif ($request->has('school_id')) {
            $filters['school_id'] = $request->get('school_id');
        }

        if ($user && $user->isAdvisor()) {
            $filters['assigned_to'] = $user->id;
        }

        $leads = $this->leadService->getLeads($filters);

        return response()->json($leads);
    }

    /**
     * Tạo lead mới
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'interest_course' => 'nullable|string|max:255',
            'source' => 'nullable|in:website,facebook,zalo,manual',
            'assigned_to' => 'nullable|integer|exists:users,id',
            'message' => 'nullable|string|max:5000',
            // conversations.channel enum: web, facebook, zalo
            'channel' => 'nullable|in:web,facebook,zalo',
            // messages.sender enum (theo DB): ai, lead, advisor
            'sender' => 'nullable|in:ai,lead,advisor',
            'school_id' => 'nullable|integer|exists:schools,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Xác định school_id theo quyền
        $schoolId = null;
        if ($user && $user->isSuperAdmin()) {
            $schoolId = $request->input('school_id');
            if (! $schoolId) {
                return response()->json([
                    'message' => 'Vui lòng truyền school_id khi tạo lead (super admin).',
                ], 422);
            }
        } else {
            $schoolId = $user?->school_id;
            if (! $schoolId) {
                return response()->json([
                    'message' => 'Tài khoản hiện tại chưa được gán trường (school_id).',
                ], 422);
            }
        }

        $lead = DB::transaction(function () use ($request, $schoolId) {
            $lead = $this->leadService->createLead([
                'school_id' => $schoolId,
                'assigned_to' => $request->assigned_to,
                'name' => $request->name,
                'phone' => $request->phone,
                'email' => $request->email,
                'interest_course' => $request->interest_course,
                'source' => $request->source ?? 'manual',
            ]);

            if ($request->filled('message')) {
                $channel = $request->input('channel') ?: 'web';
                $sender = $request->input('sender') ?: 'lead';

                $conversation = Conversation::firstOrCreate(
                    [
                        'lead_id' => $lead->id,
                        'channel' => $channel,
                    ],
                    [
                        'created_at' => now(),
                    ]
                );

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender' => $sender,
                    'content' => (string) $request->input('message'),
                    'created_at' => now(),
                ]);
            }

            // Gửi mail thông báo lead mới
            $this->leadNotificationService->notifyNewLead($lead);

            return $lead;
        });

        return response()->json($lead, 201);
    }

    /**
     * Lấy chi tiết lead
     */
    public function show(Request $request, int $id)
    {
        $lead = $this->leadService->getLeadById($id);

        $user = $request->user();
        if (! $lead) {
            return response()->json(['message' => 'Lead không tồn tại'], 404);
        }

        if ($user && ! $user->isSuperAdmin() && $lead->school_id !== $user->school_id) {
            return response()->json(['message' => 'Lead không tồn tại'], 404);
        }

        return response()->json($lead);
    }
    /**
     * Cập nhật status của lead
     */
    public function updateStatus(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:new,contacted,considering,enrolled,lost',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->leadService->updateLeadStatus($id, $request->status);

        if (!$updated) {
            return response()->json(['message' => 'Lead không tồn tại'], 404);
        }

        return response()->json(['message' => 'Cập nhật trạng thái thành công']);
    }

    /**
     * Cập nhật thông tin lead
     */
    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'interest_course' => 'nullable|string|max:255',
            'status' => 'nullable|in:new,contacted,considering,enrolled,lost',
            'message' => 'nullable|string|max:5000',
            'channel' => 'nullable|in:web,facebook,zalo',
            'sender' => 'nullable|in:ai,lead,advisor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = DB::transaction(function () use ($request, $id) {
            $ok = $this->leadService->updateLead($id, $request->only([
                'name', 'phone', 'email', 'interest_course', 'status'
            ]));

            if (!$ok) {
                return false;
            }

            if ($request->filled('message')) {
                $channel = $request->input('channel') ?: 'web';
                $sender = $request->input('sender') ?: 'lead';

                $conversation = Conversation::firstOrCreate(
                    [
                        'lead_id' => $id,
                        'channel' => $channel,
                    ],
                    [
                        'created_at' => now(),
                    ]
                );

                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender' => $sender,
                    'content' => (string) $request->input('message'),
                    'created_at' => now(),
                ]);
            }

            return true;
        });

        if (!$updated) {
            return response()->json(['message' => 'Lead không tồn tại'], 404);
        }

        return response()->json(['message' => 'Cập nhật thành công']);
    }

    /**
     * Gán lead cho user
     */
    public function assign(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $assignedTo = $request->input('assigned_to');

        // Hệ thống single-tenant: không kiểm tra theo school

        $updated = $this->leadService->updateLead($id, [
            'assigned_to' => $assignedTo,
        ]);

        if (!$updated) {
            return response()->json(['message' => 'Lead không tồn tại'], 404);
        }

        return response()->json(['message' => 'Gán lead thành công']);
    }

    /**
     * Preview chia đều lead chưa gán cho advisors.
     * Chỉ admin (route đã nằm trong middleware admin).
     *
     * Params:
     * - year_of_admission (optional, mặc định năm hiện tại)
     */
    public function distributePreview(Request $request)
    {
        $year = (int) ($request->get('year_of_admission') ?? date('Y'));

        $advisors = User::query()
            ->where('role', 'advisor')
            ->where('school_id', $request->user()->school_id)
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role']);

        if ($advisors->isEmpty()) {
            return response()->json([
                'message' => 'Không có user role advisor để chia data.',
                'total_leads' => 0,
                'year_of_admission' => $year,
                'advisors' => [],
            ], 422);
        }

        $total = Lead::query()
            ->whereNull('assigned_to')
            ->where('year_of_admission', $year)
            ->count();

        $n = $advisors->count();
        $base = intdiv($total, $n);
        $rem = $total % $n;

        $plan = $advisors->values()->map(function ($u, $idx) use ($base, $rem) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'lead_count' => $base + ($idx < $rem ? 1 : 0),
            ];
        });

        return response()->json([
            'message' => 'OK',
            'year_of_admission' => $year,
            'total_leads' => $total,
            'advisors' => $plan,
        ]);
    }

    /**
     * Execute chia đều lead chưa gán cho advisors + tự động tạo task cho advisor.
     * Chỉ admin (route đã nằm trong middleware admin).
     *
     * Body:
     * - year_of_admission (optional, mặc định năm hiện tại)
     */
    public function distributeExecute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'year_of_admission' => 'nullable|integer|min:2000|max:2100',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $year = (int) ($request->input('year_of_admission') ?? date('Y'));

        $advisors = User::query()
            ->where('role', 'advisor')
            ->where('school_id', $request->user()->school_id)
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'role']);

        if ($advisors->isEmpty()) {
            return response()->json(['message' => 'Không có user role advisor để chia data.'], 422);
        }

        $createdTasks = 0;
        $assigned = 0;
        $resultPlan = [];

        DB::transaction(function () use ($year, $advisors, &$createdTasks, &$assigned, &$resultPlan) {
            $leadIds = Lead::query()
                ->whereNull('assigned_to')
                ->where('school_id', $request->user()->school_id)
                ->where('year_of_admission', $year)
                ->orderBy('created_at')
                ->pluck('id');

            $total = $leadIds->count();
            if ($total === 0) {
                $resultPlan = $advisors->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'lead_count' => 0,
                ])->all();
                return;
            }

            $advisorCount = $advisors->count();
            $idx = 0;
            $planCounts = array_fill(0, $advisorCount, 0);

            foreach ($leadIds as $leadId) {
                $advisor = $advisors[$idx % $advisorCount];

                $updated = Lead::query()
                    ->where('id', $leadId)
                    ->whereNull('assigned_to')
                    ->update(['assigned_to' => $advisor->id]);

                if ($updated) {
                    $assigned++;
                    $planCounts[$idx % $advisorCount]++;

                    $exists = Task::query()
                        ->where('lead_id', $leadId)
                        ->where('user_id', $advisor->id)
                        ->where('status', 'pending')
                        ->exists();

                    if (! $exists) {
                        $lead = Lead::query()->select(['id', 'name', 'phone'])->find($leadId);

                        Task::create([
                            'lead_id' => $leadId,
                            'user_id' => $advisor->id,
                            'title' => 'Chăm sóc ứng viên' . ($lead && ($lead->name || $lead->phone) ? (': ' . ($lead->name ?: $lead->phone)) : ''),
                            'description' => 'Task tự động tạo khi chia data ứng viên.',
                            'due_at' => now()->addDay()->toDateString(),
                            'status' => 'pending',
                        ]);
                        $createdTasks++;
                    }
                }

                $idx++;
            }

            $resultPlan = $advisors->values()->map(function ($u, $i) use ($planCounts) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'lead_count' => $planCounts[$i] ?? 0,
                ];
            })->all();
        });

        return response()->json([
            'message' => 'Đã chia data thành công.',
            'year_of_admission' => $year,
            'assigned_leads' => $assigned,
            'tasks_created' => $createdTasks,
            'plan' => $resultPlan,
        ]);
    }

    /**
     * Import leads từ Excel/CSV
     *
     * File yêu cầu có dòng header. Hỗ trợ các cột (không phân biệt hoa thường):
     * - name | tên
     * - phone | sđt | số điện thoại
     * - email
     * - interest_course | khóa học quan tâm
     * - source (website|facebook|zalo|manual)
     * - status (new|contacted|considering|enrolled|lost)
     * - assigned_to (user_id)
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:10240|mimes:xlsx,xls,csv',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('file');

        $created = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);

            if (count($rows) < 2) {
                return response()->json([
                    'message' => 'File không có dữ liệu để import.',
                    'created' => 0,
                    'skipped' => 0,
                    'failed' => 0,
                    'errors' => [],
                ], 422);
            }

            $headerRowIndex = array_key_first($rows);
            $headerRow = $rows[$headerRowIndex];

            $normalizeHeader = function ($v) {
                $v = trim((string) $v);
                $v = Str::lower($v);
                $v = preg_replace('/\s+/', ' ', $v);
                return $v;
            };

            $aliases = [
                'name' => ['name', 'tên', 'ten', 'họ tên', 'ho ten', 'full name', 'fullname'],
                'phone' => ['phone', 'sđt', 'sdt', 'số điện thoại', 'so dien thoai', 'điện thoại', 'dien thoai', 'mobile'],
                'email' => ['email', 'e-mail', 'mail'],
                'interest_course' => ['interest_course', 'khoá học quan tâm', 'khóa học quan tâm', 'khoa hoc quan tam', 'course', 'khoá học', 'khóa học'],
                'source' => ['source', 'nguồn', 'nguon'],
                'status' => ['status', 'trạng thái', 'trang thai'],
                'assigned_to' => ['assigned_to', 'gán cho', 'gan cho', 'assignee', 'user_id'],
            ];

            $colToField = [];
            foreach ($headerRow as $col => $value) {
                $h = $normalizeHeader($value);
                if ($h === '') continue;
                foreach ($aliases as $field => $names) {
                    if (in_array($h, $names, true)) {
                        $colToField[$col] = $field;
                        break;
                    }
                }
            }

            $allowedSource = ['website', 'facebook', 'zalo', 'manual'];
            $allowedStatus = ['new', 'contacted', 'considering', 'enrolled', 'lost'];

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === $headerRowIndex) continue;

                $data = [
                    'name' => null,
                    'phone' => null,
                    'email' => null,
                    'interest_course' => null,
                    'source' => 'manual',
                    'status' => 'new',
                    'assigned_to' => null,
                ];

                foreach ($row as $col => $value) {
                    if (!isset($colToField[$col])) continue;
                    $field = $colToField[$col];
                    $val = is_string($value) ? trim($value) : $value;
                    if ($val === '') $val = null;

                    $data[$field] = $val;
                }

                $isEmpty = ($data['name'] === null
                    && $data['phone'] === null
                    && $data['email'] === null
                    && $data['interest_course'] === null);
                if ($isEmpty) {
                    $skipped++;
                    continue;
                }

                if ($data['source'] !== null) {
                    $data['source'] = Str::lower((string) $data['source']);
                    if (!in_array($data['source'], $allowedSource, true)) {
                        $data['source'] = 'manual';
                    }
                }

                if ($data['status'] !== null) {
                    $data['status'] = Str::lower((string) $data['status']);
                    if (!in_array($data['status'], $allowedStatus, true)) {
                        $data['status'] = 'new';
                    }
                }

                if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $failed++;
                    $errors[] = [
                        'row' => $rowIndex,
                        'message' => 'Email không hợp lệ: ' . (string) $data['email'],
                    ];
                    continue;
                }

                if ($data['assigned_to'] !== null) {
                    $assigned = (int) $data['assigned_to'];
                    $data['assigned_to'] = $assigned > 0 ? $assigned : null;
                }

                try {
                    $this->leadService->createLead($data);
                    $created++;
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = [
                        'row' => $rowIndex,
                        'message' => $e->getMessage(),
                    ];
                }
            }
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Không thể đọc file. Vui lòng kiểm tra định dạng.',
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Import hoàn tất.',
            'created' => $created,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }
}
