<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadService
{
    /**
     * Tạo lead mới
     */
    public function createLead(array $data): Lead
    {
        return Lead::create([
            'school_id' => $data['school_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'interest_course' => $data['interest_course'] ?? null,
            'source' => $data['source'] ?? 'website',
            'status' => $data['status'] ?? 'new',
        ]);
    }

    /**
     * Lấy danh sách leads
     */
    public function getLeads(array $filters = []): LengthAwarePaginator
    {
        $query = Lead::query()
            ->with(['assignedUser:id,name,email,role', 'conversations.messages', 'admissionProfile:id,lead_id'])
            ->orderBy('created_at', 'desc');

        if (isset($filters['school_id'])) {
            if ($filters['school_id'] === null) {
                $query->whereNull('school_id');
            } else {
                $query->where('school_id', $filters['school_id']);
            }
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['source'])) {
            $query->where('source', $filters['source']);
        }

        if (isset($filters['assigned_to'])) {
            if ($filters['assigned_to'] === null) {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $filters['assigned_to']);
            }
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $query->where('year_of_admission', date('Y'));
        if (isset($filters['year_of_admission'])) {
            $query->where('year_of_admission', $filters['year_of_admission']);
        }
        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Lấy lead theo ID
     */
    public function getLeadById(int $id): ?Lead
    {
        return Lead::where('id', $id)
            ->with(['assignedUser:id,name,email,role', 'conversations.messages', 'admissionProfile:id,lead_id'])
            ->first();
    }

    /**
     * Cập nhật status của lead
     */
    public function updateLeadStatus(int $id, string $status): bool
    {
        $lead = Lead::where('id', $id)->first();

        if (!$lead) {
            return false;
        }

        $lead->status = $status;
        return $lead->save();
    }

    /**
     * Cập nhật thông tin lead
     */
    public function updateLead(int $id, array $data): bool
    {
        $lead = Lead::where('id', $id)->first();

        if (!$lead) {
            return false;
        }

        return $lead->update($data);
    }
}
