<span class="bm-badge bm-{{ $entry->effective_status }}"><i class="bi {{ \App\Models\BmetEntry::ICONS[$entry->effective_status] }}" aria-hidden="true"></i>{{ $entry->statusLabel() }}</span>
@if($entry->expiring_soon)<span class="mt-1 block text-xs text-amber-800"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Expires {{ $entry->ec_expiry_date->format('d-M-Y') }}</span>@endif
