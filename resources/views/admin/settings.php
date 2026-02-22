<?php
$title = 'Admin Settings';
ob_start();
?>
<div class="container-fluid py-4">
    <h1 class="h4 mb-1">Admin Configuration</h1>
    <p class="text-secondary mb-4">Manage AI provider, authentication controls, and CSV onboarding in one place.</p>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>AI Configuration</strong></div>
                <div class="card-body d-grid gap-3">
                    <div>
                        <label class="form-label">Provider</label>
                        <select class="form-select">
                            <option>OpenAI</option>
                            <option>Anthropic</option>
                            <option>Azure OpenAI</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">API Key</label>
                        <input class="form-control" type="password" placeholder="••••••••••••••••">
                    </div>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="form-check"><input class="form-check-input" type="checkbox" checked id="f1"><label class="form-check-label" for="f1">Enable Grading</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" checked id="f2"><label class="form-check-label" for="f2">Enable Proctoring</label></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" id="f3"><label class="form-check-label" for="f3">Enable Analytics</label></div>
                    </div>
                    <button class="btn btn-outline-primary w-100">Test Connection</button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white"><strong>Authentication Configuration</strong></div>
                <div class="card-body d-grid gap-2">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked id="oauth"><label class="form-check-label" for="oauth">Enable SMS OAuth</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="reset"><label class="form-check-label" for="reset">Enable Email Password Reset</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" checked id="force"><label class="form-check-label" for="force">Force Password Change on Import</label></div>
                    <small class="text-secondary">Configuration changes are audit logged.</small>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>CSV Import & Password Generation</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><input type="file" class="form-control" aria-label="Upload CSV"></div>
                        <div class="col-md-8 d-flex gap-2 flex-wrap">
                            <button class="btn btn-outline-secondary">Download Template</button>
                            <button class="btn btn-outline-primary">Preview Mapping</button>
                            <button class="btn btn-primary">Generate Passwords</button>
                            <button class="btn btn-success">Run Import</button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Print credentials grouped by</label>
                        <select class="form-select w-auto">
                            <option>Class</option>
                            <option>Session</option>
                            <option>Term</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/app.php';
?>
