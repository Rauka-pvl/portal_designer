<?php

namespace App\Http\Controllers;

use App\Models\ProjectStageStep;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;

class ChecklistStepController extends Controller
{
    public function show(Request $request, int $stepId)
    {
        $step = ProjectStageStep::query()
            ->whereHas('stage.project', function ($q) use ($request) {
                $q->where('user_id', (int) $request->user()->id);
            })
            ->with([
                'stage:id,project_id,stage_type',
                'stage.project:id,user_id,name',
            ])
            ->findOrFail($stepId);

        $responsible = null;
        if ((int) $step->responsible_id > 0) {
            $responsible = User::query()->find((int) $step->responsible_id);
        }

        return view('checklist-steps.show', [
            'step' => $step,
            'project' => $step->stage?->project,
            'stage_type' => $step->stage?->stage_type,
            'responsible' => $responsible,
        ]);
    }

    public function update(Request $request, int $stepId)
    {
        $data = $request->validate([
            'result_status' => ['required', Rule::in(['pending', 'done'])],
            'result_comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $step = ProjectStageStep::query()
            ->whereHas('stage.project', function ($q) use ($request) {
                $q->where('user_id', (int) $request->user()->id);
            })
            ->findOrFail($stepId);

        $step->result_status = $data['result_status'];
        $step->result_comment = isset($data['result_comment']) && is_string($data['result_comment'])
            ? (trim($data['result_comment']) !== '' ? $data['result_comment'] : null)
            : null;
        $step->save();

        return redirect()->route('checklist-steps.show', $step->id);
    }
}

