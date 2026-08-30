<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\SmartNote;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SmartNoteController extends Controller
{
    /** Views (tabs) the notes list supports. */
    private const VIEWS = ['active', 'reminders', 'archived', 'trash'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', SmartNote::class);

        $agencyId = auth()->user()->agency_id;

        $view = in_array($request->input('view'), self::VIEWS, true) ? $request->input('view') : 'active';

        $query = SmartNote::forAgency($agencyId)->with('user:id,name');

        // Tab scoping.
        switch ($view) {
            case 'trash':
                $query->onlyTrashed();
                break;
            case 'archived':
                $query->whereNotNull('archived_at');
                break;
            case 'reminders':
                $query->whereNull('archived_at')->whereNotNull('reminder_at');
                break;
            case 'active':
            default:
                $query->whereNull('archived_at');
                break;
        }

        // Filters (ignored on trash view to keep it a plain recovery bin).
        if ($view !== 'trash') {
            if (($cat = $request->input('category')) && array_key_exists($cat, SmartNote::CATEGORIES)) {
                $query->where('category', $cat);
            }
            if (($pri = $request->input('priority')) && array_key_exists($pri, SmartNote::PRIORITIES)) {
                $query->where('priority', $pri);
            }
            if (in_array($request->input('status'), ['pending', 'completed'], true)) {
                $query->where('status', $request->input('status'));
            }
        }

        // Pinned notes float to the top on non-trash views, then the chosen sort.
        if ($view !== 'trash') {
            $query->orderByDesc('pinned');
        }
        $this->applySort($query, $request->input('sort'));

        $notes = $query->paginate(24)->withQueryString();

        // KPIs (agency-scoped, computed on non-deleted, non-archived set where relevant).
        $base       = SmartNote::forAgency($agencyId);
        $totalNotes = (clone $base)->whereNull('archived_at')->count();
        $pinnedCnt  = (clone $base)->whereNull('archived_at')->where('pinned', true)->count();
        $reminderCnt = (clone $base)->whereNull('archived_at')
            ->whereNotNull('reminder_at')->where('reminder_at', '>=', now())->count();

        return view('agency.notes.index', [
            'notes'       => $notes,
            'view'        => $view,
            'totalNotes'  => $totalNotes,
            'pinnedCnt'   => $pinnedCnt,
            'reminderCnt' => $reminderCnt,
            'categories'  => SmartNote::CATEGORIES,
            'priorities'  => SmartNote::PRIORITIES,
            'filters'     => $request->only(['category', 'priority', 'status', 'sort']),
        ]);
    }

    /** Apply the requested secondary sort (used after the pinned-desc primary sort). */
    private function applySort($query, ?string $sort): void
    {
        switch ($sort) {
            case 'oldest':   $query->oldest(); break;
            case 'priority': $query->orderByRaw("FIELD(priority,'urgent','high','medium','low')"); break;
            case 'reminder': $query->orderByRaw('reminder_at IS NULL')->orderBy('reminder_at'); break;
            default:         $query->latest(); break;
        }
    }

    public function store(Request $request)
    {
        $this->authorize('create', SmartNote::class);

        $data = $this->validateNote($request);

        SmartNote::create($data + [
            'agency_id' => auth()->user()->agency_id,
            'user_id'   => auth()->id(),
        ]);

        return back()->with('success', 'Note created.');
    }

    public function update(Request $request, SmartNote $note)
    {
        $this->authorize('update', $note);

        $note->update($this->validateNote($request));

        return back()->with('success', 'Note updated.');
    }

    public function destroy(SmartNote $note)
    {
        $this->authorize('delete', $note);

        $note->delete(); // soft delete → Trash

        return back()->with('success', 'Note moved to Trash.');
    }

    public function restore(int $note)
    {
        $model = SmartNote::withTrashed()->findOrFail($note);
        $this->authorize('restore', $model);

        $model->restore();
        // Restoring also un-archives so it returns to the Active tab.
        $model->update(['archived_at' => null]);

        return back()->with('success', 'Note restored.');
    }

    public function forceDelete(int $note)
    {
        $model = SmartNote::withTrashed()->findOrFail($note);
        $this->authorize('forceDelete', $model);

        $model->forceDelete();

        return back()->with('success', 'Note permanently deleted.');
    }

    public function togglePin(SmartNote $note)
    {
        $this->authorize('update', $note);
        $note->update(['pinned' => ! $note->pinned]);

        return back();
    }

    public function toggleComplete(SmartNote $note)
    {
        $this->authorize('update', $note);
        $note->update(['status' => $note->status === 'completed' ? 'pending' : 'completed']);

        return back();
    }

    public function toggleArchive(SmartNote $note)
    {
        $this->authorize('update', $note);
        $note->update(['archived_at' => $note->archived_at ? null : now()]);

        return back();
    }

    private function validateNote(Request $request): array
    {
        return $request->validate([
            'title'       => 'required|string|max:200',
            'body'        => 'nullable|string|max:5000',
            'category'    => ['nullable', Rule::in(array_keys(SmartNote::CATEGORIES))],
            'priority'    => ['nullable', Rule::in(array_keys(SmartNote::PRIORITIES))],
            'status'      => ['nullable', Rule::in(['pending', 'completed'])],
            'is_private'  => 'nullable|boolean',
            'reminder_at' => 'nullable|date',
        ]);
    }
}
