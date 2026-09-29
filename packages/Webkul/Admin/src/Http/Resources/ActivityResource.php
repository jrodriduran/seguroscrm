<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Webkul\Communications\Models\CallOutcome;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request
     * @return array
     */
    public function toArray($request)
    {
        $outcome = $this->outcome && class_exists(CallOutcome::class)
            ? CallOutcome::forAgency()->firstWhere('code', $this->outcome)
            : null;

        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id ?? null,
            'title' => $this->title,
            'type' => $this->type,
            'outcome' => $this->outcome,
            'outcome_label' => $outcome?->label ?? $this->outcome,
            'outcome_tone' => $outcome?->tone,
            'comment' => $this->comment,
            'additional' => is_array($this->resource->additional) ? $this->resource->additional : json_decode($this->resource->additional, true),
            'schedule_from' => $this->schedule_from,
            'schedule_to' => $this->schedule_to,
            'is_done' => $this->is_done,
            'user' => new UserResource($this->user),
            'files' => ActivityFileResource::collection($this->files),
            'participants' => ActivityParticipantResource::collection($this->participants),
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
