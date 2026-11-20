@extends('layouts.super-admin')
@section('title', 'Global Settings')
@section('page-title', 'Global Settings')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">

        <div class="card">
            <div class="card-header py-2">
                <i class="bi bi-gear me-1"></i> Global System Settings
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('super-admin.settings.update') }}">
                    @csrf @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">System Name <span class="text-danger">*</span></label>
                        <input type="text" name="system_name" class="form-control form-control-sm @error('system_name') is-invalid @enderror"
                            value="{{ old('system_name', $systemName) }}">
                        <div class="form-text">Displayed in page titles and email notifications.</div>
                        @error('system_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Default Currency <span class="text-danger">*</span></label>
                        <select name="default_currency" class="form-select form-select-sm @error('default_currency') is-invalid @enderror">
                            @foreach(\App\Support\Money::options() as $code => $label)
                                <option value="{{ $code }}" @selected(old('default_currency', $defaultCurrency) === $code)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Used for the license renewal amount and as the fallback when a plan has no currency. Each plan keeps its own currency; amounts in different currencies are never added together.</div>
                        @error('default_currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Support Email</label>
                        <input type="email" name="support_email" class="form-control form-control-sm @error('support_email') is-invalid @enderror"
                            value="{{ old('support_email', $supportEmail) }}"
                            placeholder="support@example.com">
                        <div class="form-text">Shown to agencies on expired/suspended pages.</div>
                        @error('support_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Default Plan for New Agencies</label>
                        <select name="default_plan_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ (string)$defaultPlanId === (string)$plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — {{ $plan->priceLabel('') }}
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text">Pre-selected when creating a new subscription for an agency.</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode"
                                id="maintenanceMode" value="1"
                                {{ $maintenanceMode === '1' ? 'checked' : '' }}>
                            <label class="form-check-label small" for="maintenanceMode">
                                <span class="fw-semibold text-danger">Maintenance Mode</span><br>
                                <span class="text-muted">When enabled, agency users see a maintenance notice. Super admin access is unaffected.</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-floppy me-1"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>

        {{-- Public website: WhatsApp, contact, social, Facebook Pixel, hero text, trust numbers --}}
        <div class="card mt-4">
            <div class="card-header py-2">
                <i class="bi bi-megaphone me-1"></i> Website &amp; Marketing (Home, Login, Register)
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Controls the public home page. Leave WhatsApp / social / pixel blank to hide them.
                    Blank text fields fall back to the default wording.
                </p>
                <form method="POST" action="{{ route('super-admin.settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="section" value="website">

                    <div class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.04em;">Contact &amp; Social</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">WhatsApp Number</label>
                            <input type="text" name="site_whatsapp_number" class="form-control form-control-sm @error('site_whatsapp_number') is-invalid @enderror" value="{{ old('site_whatsapp_number', $site['site_whatsapp_number']) }}" placeholder="8801XXXXXXXXX">
                            <div class="form-text">With country code, digits only. Shows a WhatsApp button + floating chat button.</div>
                            @error('site_whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">WhatsApp Pre-filled Message</label>
                            <input type="text" name="site_whatsapp_message" class="form-control form-control-sm @error('site_whatsapp_message') is-invalid @enderror" value="{{ old('site_whatsapp_message', $site['site_whatsapp_message']) }}" placeholder="">
                            
                            @error('site_whatsapp_message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Phone (optional)</label>
                            <input type="text" name="site_phone" class="form-control form-control-sm @error('site_phone') is-invalid @enderror" value="{{ old('site_phone', $site['site_phone']) }}" placeholder="+8801XXXXXXXXX">
                            
                            @error('site_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Business Hours</label>
                            <input type="text" name="site_business_hours" class="form-control form-control-sm @error('site_business_hours') is-invalid @enderror" value="{{ old('site_business_hours', $site['site_business_hours']) }}" placeholder="">
                            
                            @error('site_business_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Facebook Page URL</label>
                            <input type="text" name="site_facebook_url" class="form-control form-control-sm @error('site_facebook_url') is-invalid @enderror" value="{{ old('site_facebook_url', $site['site_facebook_url']) }}" placeholder="https://facebook.com/yourpage">
                            
                            @error('site_facebook_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">YouTube URL</label>
                            <input type="text" name="site_youtube_url" class="form-control form-control-sm @error('site_youtube_url') is-invalid @enderror" value="{{ old('site_youtube_url', $site['site_youtube_url']) }}" placeholder="https://youtube.com/@yourchannel">
                            
                            @error('site_youtube_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="text-uppercase text-muted fw-semibold mb-2 mt-2" style="font-size:.68rem;letter-spacing:.04em;">Facebook Ads Tracking</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Meta (Facebook) Pixel ID</label>
                            <input type="text" name="site_meta_pixel_id" class="form-control form-control-sm @error('site_meta_pixel_id') is-invalid @enderror" value="{{ old('site_meta_pixel_id', $site['site_meta_pixel_id']) }}" placeholder="123456789012345">
                            <div class="form-text">Digits only. Tracks PageView, CTA clicks and registration Lead. Overrides META_PIXEL_ID in .env.</div>
                            @error('site_meta_pixel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="text-uppercase text-muted fw-semibold mb-2 mt-2" style="font-size:.68rem;letter-spacing:.04em;">Home Hero</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Small badge text</label>
                            <input type="text" name="site_hero_eyebrow" class="form-control form-control-sm @error('site_hero_eyebrow') is-invalid @enderror" value="{{ old('site_hero_eyebrow', $site['site_hero_eyebrow']) }}" placeholder="">
                            
                            @error('site_hero_eyebrow')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Main button text</label>
                            <input type="text" name="site_cta_text" class="form-control form-control-sm @error('site_cta_text') is-invalid @enderror" value="{{ old('site_cta_text', $site['site_cta_text']) }}" placeholder="">
                            
                            @error('site_cta_text')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Headline</label>
                            <input type="text" name="site_hero_title" class="form-control form-control-sm @error('site_hero_title') is-invalid @enderror" value="{{ old('site_hero_title', $site['site_hero_title']) }}" placeholder="">
                            
                            @error('site_hero_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Highlighted words (must be part of the headline)</label>
                            <input type="text" name="site_hero_highlight" class="form-control form-control-sm @error('site_hero_highlight') is-invalid @enderror" value="{{ old('site_hero_highlight', $site['site_hero_highlight']) }}" placeholder="">
                            
                            @error('site_hero_highlight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12 mb-3">
                            <label class="form-label small fw-semibold">Sub-headline</label>
                            <textarea name="site_hero_lead" rows="3" class="form-control form-control-sm @error('site_hero_lead') is-invalid @enderror">{{ old('site_hero_lead', $site['site_hero_lead']) }}</textarea>
                            @error('site_hero_lead')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="text-uppercase text-muted fw-semibold mb-2 mt-2" style="font-size:.68rem;letter-spacing:.04em;">Trust Numbers</div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="site_show_trust" value="1" id="site_show_trust" {{ old('site_show_trust', $site['site_show_trust']) ? 'checked' : '' }}>
                        <label class="form-check-label small" for="site_show_trust">Show "Trusted by" numbers on home page</label>
                    </div>
                    <div class="form-text mb-2">Use only real figures — shown publicly and visible to Facebook ad reviewers.</div>
                    <div class="row">
                        <div class="col-6 col-md-3 mb-3">
                            <input type="text" name="site_trust_1_value" class="form-control form-control-sm mb-1" value="{{ old('site_trust_1_value', $site['site_trust_1_value']) }}" placeholder="Number">
                            <input type="text" name="site_trust_1_label" class="form-control form-control-sm" value="{{ old('site_trust_1_label', $site['site_trust_1_label']) }}" placeholder="Label">
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <input type="text" name="site_trust_2_value" class="form-control form-control-sm mb-1" value="{{ old('site_trust_2_value', $site['site_trust_2_value']) }}" placeholder="Number">
                            <input type="text" name="site_trust_2_label" class="form-control form-control-sm" value="{{ old('site_trust_2_label', $site['site_trust_2_label']) }}" placeholder="Label">
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <input type="text" name="site_trust_3_value" class="form-control form-control-sm mb-1" value="{{ old('site_trust_3_value', $site['site_trust_3_value']) }}" placeholder="Number">
                            <input type="text" name="site_trust_3_label" class="form-control form-control-sm" value="{{ old('site_trust_3_label', $site['site_trust_3_label']) }}" placeholder="Label">
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <input type="text" name="site_trust_4_value" class="form-control form-control-sm mb-1" value="{{ old('site_trust_4_value', $site['site_trust_4_value']) }}" placeholder="Number">
                            <input type="text" name="site_trust_4_label" class="form-control form-control-sm" value="{{ old('site_trust_4_label', $site['site_trust_4_label']) }}" placeholder="Label">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-floppy me-1"></i> Save Website Settings
                    </button>
                </form>
            </div>
        </div>

        {{-- License renewal / payment details (shown read-only to agencies) --}}
        <div class="card mt-4">
            <div class="card-header py-2">
                <i class="bi bi-credit-card me-1"></i> License Renewal / Payment Details
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Your payment channels for license renewal. Agencies see these read-only on their
                    License page. Leave all blank to hide the Payment Information card entirely.
                </p>

                <form method="POST" action="{{ route('super-admin.settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="section" value="payment">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Renewal Amount</label>
                        <input type="text" name="renewal_amount" class="form-control form-control-sm @error('renewal_amount') is-invalid @enderror"
                            value="{{ old('renewal_amount', $renewalAmount) }}" placeholder="e.g. 5000">
                        <div class="form-text">Shown to agencies in the default currency ({{ \App\Support\Money::symbol($defaultCurrency) }}).</div>
                        @error('renewal_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">bKash Number <span class="text-muted fw-normal">(Bangladesh, optional)</span></label>
                            <input type="text" name="bkash_number" class="form-control form-control-sm @error('bkash_number') is-invalid @enderror"
                                value="{{ old('bkash_number', $bkashNumber) }}" placeholder="01XXXXXXXXX">
                            @error('bkash_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-semibold">Nagad Number <span class="text-muted fw-normal">(Bangladesh, optional)</span></label>
                            <input type="text" name="nagad_number" class="form-control form-control-sm @error('nagad_number') is-invalid @enderror"
                                value="{{ old('nagad_number', $nagadNumber) }}" placeholder="01XXXXXXXXX">
                            @error('nagad_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bank Account Details</label>
                        <textarea name="bank_details" rows="4" class="form-control form-control-sm @error('bank_details') is-invalid @enderror"
                            placeholder="Bank name&#10;Account name&#10;Account number&#10;Branch">{{ old('bank_details', $bankDetails) }}</textarea>
                        <div class="form-text">One block — bank name, account name, account number, branch.</div>
                        @error('bank_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Renewal Instructions</label>
                        <textarea name="renewal_instructions" rows="4" class="form-control form-control-sm @error('renewal_instructions') is-invalid @enderror"
                            placeholder="English + বাংলা instructions for how to renew">{{ old('renewal_instructions', $renewalInstructions) }}</textarea>
                        <div class="form-text">Bilingual (English + Bangla) — line breaks are preserved on the License page.</div>
                        @error('renewal_instructions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bi bi-floppy me-1"></i> Save Payment Details
                    </button>
                </form>
            </div>
        </div>

        {{-- Global default HR form field controls --}}
        <div class="card mt-4">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <span><i class="bi bi-ui-checks me-1"></i> HR Form Field Defaults</span>
                <span class="badge bg-light text-secondary border">Active / Inactive</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    These are the <strong>global defaults</strong> for the Add / Edit HR form. Each agency can override
                    them from their own Settings. Required fields are always shown and can't be turned off.
                </p>

                <form method="POST" action="{{ route('super-admin.settings.update') }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="section" value="hr_fields">

                    @foreach($hrFieldGroups as $section => $fields)
                        <div class="mb-3">
                            <div class="text-uppercase text-muted fw-semibold mb-2" style="font-size:.68rem;letter-spacing:.04em;">{{ $section }}</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted" style="font-size:.72rem;">
                                            <th style="width:55%;">Field Name</th>
                                            <th style="width:25%;">Type</th>
                                            <th style="width:20%;" class="text-end">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($fields as $field)
                                            <tr>
                                                <td class="fw-semibold text-dark" style="font-size:.85rem;">{{ $field['label'] }}</td>
                                                <td>
                                                    @if($field['required'])
                                                        <span class="badge bg-secondary-subtle text-secondary border">Required</span>
                                                    @else
                                                        <span class="text-muted small">Optional</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($field['required'])
                                                        <span class="badge bg-success-subtle text-success border"><i class="bi bi-lock-fill me-1"></i>Always on</span>
                                                    @else
                                                        <div class="form-check form-switch d-inline-block">
                                                            <input class="form-check-input" type="checkbox"
                                                                   name="fields[]" value="{{ $field['key'] }}"
                                                                   id="gf_{{ $field['key'] }}"
                                                                   {{ ($hrFieldStatuses[$field['key']] ?? true) ? 'checked' : '' }}>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary btn-sm mt-2">
                        <i class="bi bi-floppy me-1"></i> Save Field Defaults
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
